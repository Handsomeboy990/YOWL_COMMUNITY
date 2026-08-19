<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_follows_and_unfollows_another(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($me, 'sanctum')
            ->postJson('/api/follows', ['type' => 'user', 'id' => $other->id])
            ->assertStatus(200)
            ->assertJsonPath('data.following', true)
            ->assertJsonPath('data.followers', 1);

        $this->assertSame(1, $me->followedUsers()->count());

        $this->actingAs($me, 'sanctum')
            ->deleteJson('/api/follows', ['type' => 'user', 'id' => $other->id])
            ->assertStatus(200)
            ->assertJsonPath('data.following', false);

        $this->assertSame(0, $me->fresh()->followedUsers()->count());
    }

    public function test_following_twice_does_not_duplicate(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();

        foreach (range(1, 3) as $ignored) {
            $this->actingAs($me, 'sanctum')
                ->postJson('/api/follows', ['type' => 'user', 'id' => $other->id])
                ->assertStatus(200);
        }

        $this->assertSame(1, $me->followedUsers()->count());
    }

    public function test_a_member_cannot_follow_themselves(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me, 'sanctum')
            ->postJson('/api/follows', ['type' => 'user', 'id' => $me->id])
            ->assertStatus(422);
    }

    public function test_a_member_follows_a_tag(): void
    {
        $me = User::factory()->create();
        $tag = Tag::create(['name' => 'cinema']);

        $this->actingAs($me, 'sanctum')
            ->postJson('/api/follows', ['type' => 'tag', 'id' => $tag->id])
            ->assertStatus(200);

        $this->assertSame(1, $me->followedTags()->count());
    }

    public function test_the_personalised_feed_keeps_only_what_is_followed(): void
    {
        $me = User::factory()->create();
        $followed = User::factory()->create();
        $stranger = User::factory()->create();

        Review::factory()->create(['user_id' => $followed->id, 'content' => 'du membre suivi']);
        Review::factory()->create(['user_id' => $stranger->id, 'content' => 'de quelqu un d autre']);

        $this->actingAs($me, 'sanctum')
            ->postJson('/api/follows', ['type' => 'user', 'id' => $followed->id]);

        $contents = collect(
            $this->actingAs($me, 'sanctum')->getJson('/api/reviews?feed=following')->json('data.data')
        )->pluck('content');

        $this->assertContains('du membre suivi', $contents);
        $this->assertNotContains('de quelqu un d autre', $contents);
    }

    public function test_a_followed_tag_brings_reviews_from_anybody(): void
    {
        $me = User::factory()->create();
        $stranger = User::factory()->create();
        $tag = Tag::create(['name' => 'cinema']);

        $review = Review::factory()->create(['user_id' => $stranger->id, 'content' => 'sur un tag suivi']);
        $review->tags()->sync([$tag->id]);
        Review::factory()->create(['user_id' => $stranger->id, 'content' => 'hors sujet']);

        $this->actingAs($me, 'sanctum')->postJson('/api/follows', ['type' => 'tag', 'id' => $tag->id]);

        $contents = collect(
            $this->actingAs($me, 'sanctum')->getJson('/api/reviews?feed=following')->json('data.data')
        )->pluck('content');

        $this->assertContains('sur un tag suivi', $contents);
        $this->assertNotContains('hors sujet', $contents);
    }

    public function test_the_feed_marks_which_authors_are_followed(): void
    {
        $me = User::factory()->create();
        $followed = User::factory()->create();
        $stranger = User::factory()->create();

        Review::factory()->create(['user_id' => $followed->id]);
        Review::factory()->create(['user_id' => $stranger->id]);

        $this->actingAs($me, 'sanctum')->postJson('/api/follows', ['type' => 'user', 'id' => $followed->id]);

        $reviews = collect($this->actingAs($me, 'sanctum')->getJson('/api/reviews')->json('data.data'));

        $this->assertTrue($reviews->firstWhere('user_id', $followed->id)['author_followed']);
        $this->assertFalse($reviews->firstWhere('user_id', $stranger->id)['author_followed']);
    }

    public function test_suggestions_exclude_the_member_and_those_already_followed(): void
    {
        $me = User::factory()->create();
        $followed = User::factory()->create();
        $other = User::factory()->create();

        Review::factory()->create(['user_id' => $followed->id]);
        Review::factory()->create(['user_id' => $other->id]);
        Review::factory()->create(['user_id' => $me->id]);

        $this->actingAs($me, 'sanctum')->postJson('/api/follows', ['type' => 'user', 'id' => $followed->id]);

        $ids = collect($this->actingAs($me, 'sanctum')->getJson('/api/follows/suggestions')->json('data'))
            ->pluck('id');

        $this->assertContains($other->id, $ids);
        $this->assertNotContains($followed->id, $ids);
        $this->assertNotContains($me->id, $ids);
    }

    public function test_guests_cannot_follow(): void
    {
        $other = User::factory()->create();

        $this->postJson('/api/follows', ['type' => 'user', 'id' => $other->id])->assertStatus(401);
    }
}
