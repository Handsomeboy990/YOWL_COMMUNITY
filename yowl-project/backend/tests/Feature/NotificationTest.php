<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Review;
use App\Models\User;
use App\Notifications\CommentReceived;
use App\Notifications\ReplyReceived;
use App\Notifications\ReviewLiked;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_commenting_a_review_notifies_its_author(): void
    {
        $author = User::factory()->create();
        $review = Review::factory()->for($author)->create();
        $commenter = User::factory()->create();

        $this->actingAs($commenter, 'sanctum')->postJson('/api/comments', [
            'review_id' => $review->id,
            'content' => 'Bien vu.',
        ])->assertStatus(201);

        $this->assertCount(1, $author->fresh()->notifications);
        $this->assertSame(CommentReceived::class, $author->notifications()->first()->type);
        $this->assertSame('comment', $author->notifications()->first()->data['type']);
    }

    public function test_commenting_your_own_review_notifies_nobody(): void
    {
        $author = User::factory()->create();
        $review = Review::factory()->for($author)->create();

        $this->actingAs($author, 'sanctum')->postJson('/api/comments', [
            'review_id' => $review->id,
            'content' => 'Je complète mon propos.',
        ])->assertStatus(201);

        $this->assertCount(0, $author->fresh()->notifications);
    }

    public function test_replying_notifies_the_parent_comment_author(): void
    {
        $reviewAuthor = User::factory()->create();
        $review = Review::factory()->for($reviewAuthor)->create();
        $parentAuthor = User::factory()->create();
        $parent = Comment::factory()->for($parentAuthor)->for($review)->create();
        $responder = User::factory()->create();

        $this->actingAs($responder, 'sanctum')->postJson('/api/comments', [
            'review_id' => $review->id,
            'parent_id' => $parent->id,
            'content' => 'Je ne suis pas d\'accord.',
        ])->assertStatus(201);

        $this->assertSame(ReplyReceived::class, $parentAuthor->fresh()->notifications()->first()?->type);
        // L'auteur de la review est prévenu du commentaire, une seule fois
        $this->assertCount(1, $reviewAuthor->fresh()->notifications);
    }

    public function test_liking_a_review_notifies_its_author_once(): void
    {
        $author = User::factory()->create();
        $review = Review::factory()->for($author)->create();
        $fan = User::factory()->create();

        $this->actingAs($fan, 'sanctum')
            ->postJson("/api/reviews/{$review->id}/react", ['reaction' => 'like'])
            ->assertStatus(200);

        // Retirer puis remettre ne doit pas empiler les notifications
        $this->actingAs($fan, 'sanctum')
            ->postJson("/api/reviews/{$review->id}/react", ['reaction' => 'dislike'])
            ->assertStatus(200);

        $notifications = $author->fresh()->notifications;
        $this->assertCount(1, $notifications);
        $this->assertSame(ReviewLiked::class, $notifications->first()->type);
    }

    public function test_a_user_only_sees_their_own_notifications(): void
    {
        $author = User::factory()->create();
        $review = Review::factory()->for($author)->create();
        $commenter = User::factory()->create();

        $this->actingAs($commenter, 'sanctum')->postJson('/api/comments', [
            'review_id' => $review->id,
            'content' => 'Excellent.',
        ]);

        $this->actingAs($author, 'sanctum')->getJson('/api/notifications')
            ->assertStatus(200)
            ->assertJsonPath('unread_count', 1)
            ->assertJsonCount(1, 'data.data');

        $this->actingAs($commenter, 'sanctum')->getJson('/api/notifications')
            ->assertStatus(200)
            ->assertJsonPath('unread_count', 0)
            ->assertJsonCount(0, 'data.data');
    }

    public function test_guests_cannot_read_notifications(): void
    {
        $this->getJson('/api/notifications')->assertStatus(401);
    }

    public function test_marking_a_notification_as_read_updates_the_counter(): void
    {
        $author = $this->authorWithOneNotification();
        $id = $author->notifications()->first()->id;

        $this->actingAs($author, 'sanctum')->patchJson("/api/notifications/{$id}/read")
            ->assertStatus(200)
            ->assertJsonPath('data.unread_count', 0);

        $this->assertNotNull($author->fresh()->notifications()->first()->read_at);
    }

    public function test_a_user_cannot_mark_someone_elses_notification_as_read(): void
    {
        $author = $this->authorWithOneNotification();
        $stranger = User::factory()->create();
        $id = $author->notifications()->first()->id;

        $this->actingAs($stranger, 'sanctum')->patchJson("/api/notifications/{$id}/read")
            ->assertStatus(404);

        $this->assertNull($author->fresh()->notifications()->first()->read_at);
    }

    public function test_marking_everything_as_read_clears_the_counter(): void
    {
        $author = $this->authorWithOneNotification();

        $this->actingAs($author, 'sanctum')->patchJson('/api/notifications/read-all')
            ->assertStatus(200)
            ->assertJsonPath('data.unread_count', 0);

        $this->assertSame(0, $author->fresh()->unreadNotifications()->count());
    }

    public function test_a_user_can_delete_their_notification(): void
    {
        $author = $this->authorWithOneNotification();
        $id = $author->notifications()->first()->id;

        $this->actingAs($author, 'sanctum')->deleteJson("/api/notifications/{$id}")
            ->assertStatus(200);

        $this->assertCount(0, $author->fresh()->notifications);
    }

    public function test_a_user_cannot_delete_someone_elses_notification(): void
    {
        $author = $this->authorWithOneNotification();
        $stranger = User::factory()->create();
        $id = $author->notifications()->first()->id;

        $this->actingAs($stranger, 'sanctum')->deleteJson("/api/notifications/{$id}")
            ->assertStatus(404);

        $this->assertCount(1, $author->fresh()->notifications);
    }

    public function test_the_unread_count_endpoint_answers_without_the_list(): void
    {
        $author = $this->authorWithOneNotification();

        $this->actingAs($author, 'sanctum')->getJson('/api/notifications/unread-count')
            ->assertStatus(200)
            ->assertJsonPath('data.unread_count', 1);
    }

    /**
     * A review author holding exactly one unread notification.
     */
    private function authorWithOneNotification(): User
    {
        $author = User::factory()->create();
        $review = Review::factory()->for($author)->create();
        $commenter = User::factory()->create();

        $this->actingAs($commenter, 'sanctum')->postJson('/api/comments', [
            'review_id' => $review->id,
            'content' => 'Un commentaire.',
        ]);

        return $author->fresh();
    }
}
