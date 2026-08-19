<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Review;
use App\Models\User;
use App\Support\FeedScore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedScoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_engagement_raises_the_score(): void
    {
        $calme = Review::factory()->create(['nb_like' => 0, 'created_at' => now()]);
        $anime = Review::factory()->create(['nb_like' => 40, 'created_at' => now()]);

        $this->assertGreaterThan(FeedScore::for($calme), FeedScore::for($anime));
    }

    public function test_age_lowers_the_score(): void
    {
        $recent = Review::factory()->create(['nb_like' => 10, 'created_at' => now()]);
        $ancien = Review::factory()->create(['nb_like' => 10, 'created_at' => now()->subDays(5)]);

        $this->assertGreaterThan(FeedScore::for($ancien), FeedScore::for($recent));
    }

    public function test_a_comment_weighs_more_than_a_reaction(): void
    {
        $avecLikes = Review::factory()->create(['nb_like' => 2, 'created_at' => now()]);
        $avecCommentaires = Review::factory()->create(['nb_like' => 0, 'created_at' => now()]);
        Comment::factory()->count(2)->create(['review_id' => $avecCommentaires->id]);

        $this->assertGreaterThan(FeedScore::for($avecLikes), FeedScore::for($avecCommentaires));
    }

    public function test_dislikes_lower_the_score_without_cancelling_it(): void
    {
        $propre = Review::factory()->create(['nb_like' => 20, 'nb_dislike' => 0, 'created_at' => now()]);
        $conteste = Review::factory()->create(['nb_like' => 20, 'nb_dislike' => 15, 'created_at' => now()]);

        $this->assertGreaterThan(FeedScore::for($conteste), FeedScore::for($propre));
        // Un avis tres rejete recule, il ne disparait pas dans le negatif.
        $this->assertGreaterThanOrEqual(-1, FeedScore::for($conteste));
    }

    public function test_the_relevant_sort_puts_the_engaging_review_first(): void
    {
        $author = User::factory()->create();

        $ignore = Review::factory()->create(['content' => 'ignore', 'nb_like' => 0, 'created_at' => now()->subHours(2)]);
        $populaire = Review::factory()->create(['content' => 'populaire', 'nb_like' => 60, 'created_at' => now()->subHours(3)]);

        FeedScore::refresh($ignore);
        FeedScore::refresh($populaire);

        $contents = collect(
            $this->actingAs($author, 'sanctum')->getJson('/api/reviews?sort=relevant')->json('data.data')
        )->pluck('content');

        $this->assertSame('populaire', $contents->first());
    }

    public function test_publishing_stores_a_score(): void
    {
        $author = User::factory()->create();

        $this->actingAs($author, 'sanctum')->postJson('/api/reviews', [
            'content' => 'Un avis qui doit être classé dès sa publication.',
        ])->assertStatus(201);

        $this->assertNotNull(Review::first()->score);
    }

    public function test_reacting_updates_the_score(): void
    {
        $author = User::factory()->create();
        $review = Review::factory()->create(['created_at' => now()]);
        FeedScore::refresh($review);
        $avant = $review->fresh()->score;

        $this->actingAs($author, 'sanctum')
            ->postJson('/api/reviews/'.$review->id.'/react', ['reaction' => 'like'])
            ->assertStatus(200);

        $this->assertGreaterThan($avant, $review->fresh()->score);
    }
}
