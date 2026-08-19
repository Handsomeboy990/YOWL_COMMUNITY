<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\Tag;
use App\Models\User;
use App\Support\Cached;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_counters_are_served_from_cache(): void
    {
        Review::factory()->count(2)->create();

        $this->getJson('/api/kpi')->assertStatus(200);
        $this->assertNotNull(Cache::get(Cached::KPI));
    }

    public function test_publishing_a_review_refreshes_the_counters(): void
    {
        $author = User::factory()->create();
        Review::factory()->create();

        $before = $this->getJson('/api/kpi')->json('data.nbReviews');
        $this->assertSame(1, $before);

        // Sans invalidation, ce chiffre resterait faux pendant cinq minutes.
        $this->actingAs($author, 'sanctum')->postJson('/api/reviews', [
            'content' => 'Un avis de plus, qui doit compter tout de suite.',
        ])->assertStatus(201);

        $after = $this->getJson('/api/kpi')->json('data.nbReviews');
        $this->assertSame(2, $after);
    }

    public function test_deleting_a_review_refreshes_the_counters(): void
    {
        $author = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $author->id]);

        $this->assertSame(1, $this->getJson('/api/kpi')->json('data.nbReviews'));

        $this->actingAs($author, 'sanctum')
            ->deleteJson('/api/reviews/'.$review->id)
            ->assertStatus(200);

        $this->assertSame(0, $this->getJson('/api/kpi')->json('data.nbReviews'));
    }

    public function test_a_new_tag_appears_in_the_listing_without_waiting(): void
    {
        $author = User::factory()->create();
        Tag::create(['name' => 'existant']);

        $before = collect($this->getJson('/api/tags')->json('data'))->pluck('name');
        $this->assertNotContains('inedit', $before);

        $this->actingAs($author, 'sanctum')->postJson('/api/reviews', [
            'content' => 'Un avis qui invente un tag.',
            'tags' => ['inedit'],
        ])->assertStatus(201);

        $after = collect($this->getJson('/api/tags')->json('data'))->pluck('name');
        $this->assertContains('inedit', $after);
    }

    public function test_a_new_comment_refreshes_the_counters(): void
    {
        $author = User::factory()->create();
        $review = Review::factory()->create();

        $this->assertSame(0, $this->getJson('/api/kpi')->json('data.nbComments'));

        $this->actingAs($author, 'sanctum')->postJson('/api/comments', [
            'review_id' => $review->id,
            'content' => 'Un commentaire.',
        ])->assertStatus(201);

        $this->assertSame(1, $this->getJson('/api/kpi')->json('data.nbComments'));
    }
}
