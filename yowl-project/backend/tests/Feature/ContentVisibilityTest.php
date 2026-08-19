<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_comment_on_an_unpublished_review_is_hidden_from_the_public_listing(): void
    {
        $published = Review::factory()->create(['is_published' => true]);
        $hidden = Review::factory()->create(['is_published' => false]);

        Comment::factory()->create(['review_id' => $published->id, 'content' => 'visible one']);
        Comment::factory()->create(['review_id' => $hidden->id, 'content' => 'hidden one']);

        $response = $this->getJson('/api/comments');

        $response->assertStatus(200);
        $contents = collect($response->json('data'))->pluck('content');
        $this->assertContains('visible one', $contents);
        $this->assertNotContains('hidden one', $contents);
    }

    public function test_the_author_still_sees_the_comments_of_their_unpublished_review(): void
    {
        $author = User::factory()->create();
        $hidden = Review::factory()->create(['user_id' => $author->id, 'is_published' => false]);
        Comment::factory()->create(['review_id' => $hidden->id, 'content' => 'mine to read']);

        $response = $this->actingAs($author, 'sanctum')->getJson('/api/comments');

        $response->assertStatus(200);
        $this->assertContains('mine to read', collect($response->json('data'))->pluck('content'));
    }

    public function test_reading_one_comment_of_an_unpublished_review_answers_not_found(): void
    {
        $hidden = Review::factory()->create(['is_published' => false]);
        $comment = Comment::factory()->create(['review_id' => $hidden->id]);

        $this->getJson('/api/comments/'.$comment->id)->assertStatus(404);
    }

    public function test_commenting_an_unpublished_review_is_refused(): void
    {
        $member = User::factory()->create();
        $hidden = Review::factory()->create(['is_published' => false]);

        $this->actingAs($member, 'sanctum')->postJson('/api/comments', [
            'review_id' => $hidden->id,
            'content' => 'Let me in',
        ])->assertStatus(403);
    }

    public function test_a_reply_cannot_be_grafted_onto_another_review(): void
    {
        $member = User::factory()->create();
        $first = Review::factory()->create();
        $second = Review::factory()->create();
        $parent = Comment::factory()->create(['review_id' => $first->id]);

        $this->actingAs($member, 'sanctum')->postJson('/api/comments', [
            'review_id' => $second->id,
            'parent_id' => $parent->id,
            'content' => 'Wrong thread',
        ])->assertStatus(422);
    }
}
