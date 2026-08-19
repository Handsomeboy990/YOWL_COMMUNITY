<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_lists_all_their_reviews_not_just_a_feed_page(): void
    {
        $member = User::factory()->create();
        Review::factory()->count(14)->create(['user_id' => $member->id]);
        Review::factory()->count(5)->create();

        $response = $this->actingAs($member, 'sanctum')->getJson('/api/users/'.$member->id.'/reviews');

        $response->assertStatus(200);
        // Quatorze au total, dix sur la premiere page : la pagination porte
        // bien sur les reviews du membre, pas sur une tranche du fil global.
        $this->assertSame(14, $response->json('data.total'));
        $this->assertCount(10, $response->json('data.data'));
    }

    public function test_the_review_listing_of_a_member_excludes_other_members(): void
    {
        $member = User::factory()->create();
        $other = User::factory()->create();
        Review::factory()->count(3)->create(['user_id' => $member->id]);
        Review::factory()->count(4)->create(['user_id' => $other->id]);

        $response = $this->actingAs($member, 'sanctum')->getJson('/api/users/'.$member->id.'/reviews');

        $this->assertSame(3, $response->json('data.total'));
    }

    public function test_a_visitor_of_a_profile_does_not_see_the_unpublished_reviews(): void
    {
        $member = User::factory()->create();
        $reader = User::factory()->create();
        Review::factory()->count(2)->create(['user_id' => $member->id, 'is_published' => true]);
        Review::factory()->create(['user_id' => $member->id, 'is_published' => false]);

        $asOwner = $this->actingAs($member, 'sanctum')->getJson('/api/users/'.$member->id.'/reviews');
        $this->assertSame(3, $asOwner->json('data.total'));

        $asReader = $this->actingAs($reader, 'sanctum')->getJson('/api/users/'.$member->id.'/reviews');
        $this->assertSame(2, $asReader->json('data.total'));
    }

    public function test_statistics_are_summed_over_every_review(): void
    {
        $member = User::factory()->create();
        Review::factory()->create(['user_id' => $member->id, 'nb_views' => 100, 'nb_like' => 5, 'nb_dislike' => 1]);
        Review::factory()->create(['user_id' => $member->id, 'nb_views' => 40, 'nb_like' => 2, 'nb_dislike' => 0]);

        $response = $this->actingAs($member, 'sanctum')->getJson('/api/users/'.$member->id.'/stats');

        $response->assertStatus(200);
        $response->assertJsonPath('data.reviews', 2);
        $response->assertJsonPath('data.views', 140);
        $response->assertJsonPath('data.likes', 7);
        $response->assertJsonPath('data.dislikes', 1);
    }

    public function test_comments_received_excludes_the_member_own_comments(): void
    {
        $member = User::factory()->create();
        $someone = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $member->id]);

        Comment::factory()->count(3)->create(['review_id' => $review->id, 'user_id' => $someone->id]);
        Comment::factory()->create(['review_id' => $review->id, 'user_id' => $member->id]);

        $response = $this->actingAs($member, 'sanctum')->getJson('/api/users/'.$member->id.'/stats');

        $response->assertJsonPath('data.comments_received', 3);
        $response->assertJsonPath('data.comments_written', 1);
    }

    public function test_the_monthly_series_covers_six_months_including_the_empty_ones(): void
    {
        $member = User::factory()->create();
        Review::factory()->create(['user_id' => $member->id, 'created_at' => now()]);

        $series = $this->actingAs($member, 'sanctum')
            ->getJson('/api/users/'.$member->id.'/stats')
            ->json('data.reviews_per_month');

        $this->assertCount(6, $series);
        $this->assertSame(now()->format('Y-m'), $series[5]['month']);
        $this->assertSame(1, $series[5]['count']);
        $this->assertSame(0, $series[0]['count']);
    }

    public function test_statistics_of_another_member_are_refused(): void
    {
        $member = User::factory()->create();
        $nosy = User::factory()->create();

        $this->actingAs($nosy, 'sanctum')
            ->getJson('/api/users/'.$member->id.'/stats')
            ->assertStatus(403);
    }
}
