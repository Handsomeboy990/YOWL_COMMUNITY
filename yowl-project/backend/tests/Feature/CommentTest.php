<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_comment(): void
    {
        $review = Review::factory()->create();

        $this->postJson('/api/comments', [
            'review_id' => $review->id,
            'content' => 'Hello',
        ])->assertStatus(401);
    }

    public function test_authenticated_users_can_comment_and_reply(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $comment = $this->actingAs($user, 'sanctum')->postJson('/api/comments', [
            'review_id' => $review->id,
            'content' => 'First comment',
        ]);
        $comment->assertStatus(201);

        $reply = $this->actingAs($user, 'sanctum')->postJson('/api/comments', [
            'review_id' => $review->id,
            'parent_id' => $comment->json('data.id'),
            'content' => 'A nested reply',
        ]);
        $reply->assertStatus(201);
        $this->assertSame($comment->json('data.id'), $reply->json('data.parent_id'));
    }

    public function test_only_the_owner_can_update_or_delete_a_comment(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $comment = Comment::factory()->for($owner)->create();

        $this->actingAs($intruder, 'sanctum')
            ->patchJson("/api/comments/{$comment->id}", ['content' => 'Hacked'])
            ->assertStatus(403);

        $this->actingAs($owner, 'sanctum')
            ->patchJson("/api/comments/{$comment->id}", ['content' => 'Edited'])
            ->assertStatus(200);

        $this->actingAs($intruder, 'sanctum')
            ->deleteJson("/api/comments/{$comment->id}")
            ->assertStatus(403);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/comments/{$comment->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }
}
