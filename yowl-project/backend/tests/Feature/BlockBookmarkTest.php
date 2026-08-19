<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockBookmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_blocked_member_disappears_from_the_feed(): void
    {
        $me = User::factory()->create();
        $genant = User::factory()->create();
        $autre = User::factory()->create();

        Review::factory()->create(['user_id' => $genant->id, 'content' => 'du membre bloque']);
        Review::factory()->create(['user_id' => $autre->id, 'content' => 'de quelqu un d autre']);

        $this->actingAs($me, 'sanctum')->postJson('/api/blocks/'.$genant->id)->assertStatus(200);

        $contents = collect(
            $this->actingAs($me, 'sanctum')->getJson('/api/reviews')->json('data.data')
        )->pluck('content');

        $this->assertNotContains('du membre bloque', $contents);
        $this->assertContains('de quelqu un d autre', $contents);
    }

    public function test_the_comments_of_a_blocked_member_disappear_too(): void
    {
        $me = User::factory()->create();
        $genant = User::factory()->create();
        $review = Review::factory()->create();

        Comment::factory()->create(['user_id' => $genant->id, 'review_id' => $review->id, 'content' => 'a taire']);

        $this->actingAs($me, 'sanctum')->postJson('/api/blocks/'.$genant->id);

        $contents = collect($this->actingAs($me, 'sanctum')->getJson('/api/comments')->json('data'))
            ->pluck('content');

        $this->assertNotContains('a taire', $contents);
    }

    public function test_blocking_breaks_the_follow_in_both_directions(): void
    {
        $me = User::factory()->create();
        $genant = User::factory()->create();

        $this->actingAs($me, 'sanctum')->postJson('/api/follows', ['type' => 'user', 'id' => $genant->id]);
        $this->actingAs($genant, 'sanctum')->postJson('/api/follows', ['type' => 'user', 'id' => $me->id]);

        $this->actingAs($me, 'sanctum')->postJson('/api/blocks/'.$genant->id)->assertStatus(200);

        $this->assertSame(0, $me->fresh()->followedUsers()->count());
        $this->assertSame(0, $genant->fresh()->followedUsers()->count());
    }

    public function test_a_member_cannot_block_themselves(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me, 'sanctum')->postJson('/api/blocks/'.$me->id)->assertStatus(422);
    }

    public function test_unblocking_brings_the_content_back(): void
    {
        $me = User::factory()->create();
        $genant = User::factory()->create();
        Review::factory()->create(['user_id' => $genant->id, 'content' => 'de retour']);

        $this->actingAs($me, 'sanctum')->postJson('/api/blocks/'.$genant->id);
        $this->actingAs($me, 'sanctum')->deleteJson('/api/blocks/'.$genant->id)->assertStatus(200);

        $contents = collect($this->actingAs($me, 'sanctum')->getJson('/api/reviews')->json('data.data'))
            ->pluck('content');

        $this->assertContains('de retour', $contents);
    }

    public function test_a_member_saves_and_removes_a_review(): void
    {
        $me = User::factory()->create();
        $review = Review::factory()->create();

        $this->actingAs($me, 'sanctum')->postJson('/api/bookmarks/'.$review->id)->assertStatus(200);
        $this->assertSame(1, $this->actingAs($me, 'sanctum')->getJson('/api/bookmarks')->json('data.total'));

        $this->actingAs($me, 'sanctum')->deleteJson('/api/bookmarks/'.$review->id)->assertStatus(200);
        $this->assertSame(0, $this->actingAs($me, 'sanctum')->getJson('/api/bookmarks')->json('data.total'));
    }

    public function test_saving_twice_does_not_duplicate(): void
    {
        $me = User::factory()->create();
        $review = Review::factory()->create();

        foreach (range(1, 3) as $ignored) {
            $this->actingAs($me, 'sanctum')->postJson('/api/bookmarks/'.$review->id)->assertStatus(200);
        }

        $this->assertSame(1, $this->actingAs($me, 'sanctum')->getJson('/api/bookmarks')->json('data.total'));
    }

    public function test_the_feed_marks_the_saved_reviews(): void
    {
        $me = User::factory()->create();
        $garde = Review::factory()->create();
        $autre = Review::factory()->create();

        $this->actingAs($me, 'sanctum')->postJson('/api/bookmarks/'.$garde->id);

        $reviews = collect($this->actingAs($me, 'sanctum')->getJson('/api/reviews')->json('data.data'));

        $this->assertTrue($reviews->firstWhere('id', $garde->id)['bookmarked']);
        $this->assertFalse($reviews->firstWhere('id', $autre->id)['bookmarked']);
    }

    public function test_an_unpublished_review_of_somebody_else_is_not_listed(): void
    {
        $me = User::factory()->create();
        $review = Review::factory()->create(['is_published' => true]);
        $this->actingAs($me, 'sanctum')->postJson('/api/bookmarks/'.$review->id);

        $review->forceFill(['is_published' => false])->save();

        $this->assertSame(0, $this->actingAs($me, 'sanctum')->getJson('/api/bookmarks')->json('data.total'));
    }

    public function test_guests_cannot_block_or_save(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $this->postJson('/api/blocks/'.$user->id)->assertStatus(401);
        $this->postJson('/api/bookmarks/'.$review->id)->assertStatus(401);
    }
}
