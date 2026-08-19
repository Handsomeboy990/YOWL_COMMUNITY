<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ContentAutoHidden;
use App\Support\FeedScore;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HelpfulModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_reader_marks_a_review_as_helpful(): void
    {
        $lecteur = User::factory()->create();
        $review = Review::factory()->create();

        $this->actingAs($lecteur, 'sanctum')
            ->postJson('/api/reviews/'.$review->id.'/helpful', ['helpful' => true])
            ->assertStatus(200)
            ->assertJsonPath('data.nb_helpful', 1)
            ->assertJsonPath('data.user_helpful', true);
    }

    public function test_voting_the_same_way_twice_clears_the_vote(): void
    {
        $lecteur = User::factory()->create();
        $review = Review::factory()->create();

        $this->actingAs($lecteur, 'sanctum')->postJson('/api/reviews/'.$review->id.'/helpful', ['helpful' => true]);
        $response = $this->actingAs($lecteur, 'sanctum')
            ->postJson('/api/reviews/'.$review->id.'/helpful', ['helpful' => true]);

        $response->assertJsonPath('data.user_helpful', null);
        $response->assertJsonPath('data.nb_helpful', 0);
    }

    public function test_an_author_cannot_judge_their_own_review(): void
    {
        $auteur = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $auteur->id]);

        $this->actingAs($auteur, 'sanctum')
            ->postJson('/api/reviews/'.$review->id.'/helpful', ['helpful' => true])
            ->assertStatus(422);
    }

    public function test_usefulness_weighs_more_than_a_like_in_the_ranking(): void
    {
        $aime = Review::factory()->create(['nb_like' => 2, 'nb_helpful' => 0, 'created_at' => now()]);
        $utile = Review::factory()->create(['nb_like' => 0, 'nb_helpful' => 2, 'created_at' => now()]);

        $this->assertGreaterThan(FeedScore::for($aime), FeedScore::for($utile));
    }

    public function test_a_review_is_hidden_once_the_threshold_is_reached(): void
    {
        Notification::fake();
        Settings::forget();
        $review = Review::factory()->create(['is_published' => true]);

        // Le seuil par defaut est de trois signalements distincts.
        foreach (range(1, 3) as $ignored) {
            $signalant = User::factory()->create();
            $this->actingAs($signalant, 'sanctum')->postJson('/api/reports', [
                'type' => 'review',
                'id' => $review->id,
                'reason' => 'spam',
            ])->assertStatus(201);
        }

        $this->assertFalse($review->fresh()->is_published);
        Notification::assertSentTo($review->user, ContentAutoHidden::class);
    }

    public function test_below_the_threshold_the_review_stays_visible(): void
    {
        Settings::forget();
        $review = Review::factory()->create(['is_published' => true]);

        foreach (range(1, 2) as $ignored) {
            $signalant = User::factory()->create();
            $this->actingAs($signalant, 'sanctum')->postJson('/api/reports', [
                'type' => 'review', 'id' => $review->id, 'reason' => 'spam',
            ]);
        }

        $this->assertTrue($review->fresh()->is_published);
    }

    public function test_clearing_the_threshold_disables_the_hiding(): void
    {
        $review = Review::factory()->create(['is_published' => true]);
        Settings::set('moderation.auto_hide_threshold', null);

        foreach (range(1, 6) as $ignored) {
            $signalant = User::factory()->create();
            $this->actingAs($signalant, 'sanctum')->postJson('/api/reports', [
                'type' => 'review', 'id' => $review->id, 'reason' => 'spam',
            ]);
        }

        $this->assertTrue($review->fresh()->is_published);
    }

    public function test_the_moderation_queue_puts_the_most_reported_first(): void
    {
        Settings::set('moderation.auto_hide_threshold', null);
        $admin = User::factory()->create();
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->assignRole('admin');
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $peu = Review::factory()->create(['content' => 'peu signale']);
        $beaucoup = Review::factory()->create(['content' => 'tres signale']);

        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/reports', ['type' => 'review', 'id' => $peu->id, 'reason' => 'spam']);
        foreach (range(1, 4) as $ignored) {
            $this->actingAs(User::factory()->create(), 'sanctum')
                ->postJson('/api/reports', ['type' => 'review', 'id' => $beaucoup->id, 'reason' => 'spam']);
        }

        $premier = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/reports')->json('data.data.0');

        $this->assertSame($beaucoup->id, $premier['reportable_id']);
    }
}
