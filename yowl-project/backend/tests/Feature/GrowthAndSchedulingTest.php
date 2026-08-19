<?php

namespace Tests\Feature;

use App\Models\ActivityPing;
use App\Models\Comment;
use App\Models\Review;
use App\Models\User;
use App\Support\Growth;
use App\Support\LinkNormaliser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GrowthAndSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    // Programmation de publication

    public function test_a_scheduled_review_stays_out_of_the_feed_until_its_hour(): void
    {
        $author = User::factory()->create();

        $this->actingAs($author, 'sanctum')
            ->postJson('/api/reviews', [
                'content' => 'A publier demain matin, quand tout le monde est reveille.',
                'scheduled_for' => now()->addDay()->toIso8601String(),
            ])
            ->assertStatus(201);

        $review = Review::first();
        $this->assertFalse($review->is_published);
        $this->assertTrue($review->isScheduled());

        // Vu par quelqu'un d'autre : l'auteur, lui, voit toujours ses propres
        // avis non publies, ce qui est la regle du fil.
        $contenus = collect(
            $this->actingAs(User::factory()->create(), 'sanctum')
                ->getJson('/api/reviews')->json('data.data')
        )->pluck('content');
        $this->assertNotContains('A publier demain matin, quand tout le monde est reveille.', $contenus);
    }

    public function test_the_command_publishes_a_review_whose_hour_has_passed(): void
    {
        $review = Review::factory()->create();
        $review->forceFill([
            'is_published' => false,
            'scheduled_for' => now()->subMinute(),
        ])->save();

        $this->artisan('yowl:publish-scheduled')->assertExitCode(0);

        $review->refresh();
        $this->assertTrue($review->is_published);
        $this->assertNull($review->scheduled_for);
    }

    public function test_the_command_leaves_a_future_review_alone(): void
    {
        $review = Review::factory()->create();
        $review->forceFill([
            'is_published' => false,
            'scheduled_for' => now()->addHours(3),
        ])->save();

        $this->artisan('yowl:publish-scheduled')->assertExitCode(0);

        $this->assertFalse($review->fresh()->is_published);
    }

    public function test_the_command_never_republishes_content_hidden_by_moderation(): void
    {
        $review = Review::factory()->create();
        $review->forceFill(['is_published' => false, 'scheduled_for' => null])->save();

        $this->artisan('yowl:publish-scheduled')->assertExitCode(0);

        $this->assertFalse($review->fresh()->is_published);
    }

    public function test_a_date_in_the_past_is_refused(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->postJson('/api/reviews', [
                'content' => 'Programmer dans le passe ne veut rien dire.',
                'scheduled_for' => now()->subDay()->toIso8601String(),
            ])
            ->assertStatus(422);
    }

    // Detection de doublons

    public function test_tracking_parameters_do_not_change_the_fingerprint(): void
    {
        $nu = LinkNormaliser::fingerprint('https://www.lemonde.fr/article/2026/le-titre/');
        $suivi = LinkNormaliser::fingerprint('http://lemonde.fr/article/2026/le-titre?utm_source=x&fbclid=abc');

        $this->assertSame($nu, $suivi);
        $this->assertSame('lemonde.fr/article/2026/le-titre', $nu);
    }

    public function test_a_meaningful_parameter_is_kept(): void
    {
        $a = LinkNormaliser::fingerprint('https://site.fr/video?v=123');
        $b = LinkNormaliser::fingerprint('https://site.fr/video?v=456');

        $this->assertNotSame($a, $b);
    }

    public function test_the_fingerprint_follows_the_link_without_being_asked(): void
    {
        $review = Review::factory()->create(['link' => 'https://www.exemple.fr/page/?utm_campaign=ete']);

        $this->assertSame('exemple.fr/page', $review->fresh()->link_fingerprint);
    }

    public function test_an_existing_discussion_on_the_same_link_is_proposed(): void
    {
        $premier = User::factory()->create();
        $second = User::factory()->create();

        Review::factory()->create([
            'user_id' => $premier->id,
            'link' => 'https://www.exemple.fr/enquete?utm_source=newsletter',
            'content' => 'La discussion deja ouverte sur cette enquete.',
        ]);

        $proposes = $this->actingAs($second, 'sanctum')
            ->getJson('/api/liens/existant?link='.urlencode('http://exemple.fr/enquete/'))
            ->assertStatus(200)
            ->json('data');

        $this->assertCount(1, $proposes);
        $this->assertSame('La discussion deja ouverte sur cette enquete.', $proposes[0]['content']);
    }

    public function test_a_member_is_not_pointed_back_at_their_own_review(): void
    {
        $auteur = User::factory()->create();
        Review::factory()->create(['user_id' => $auteur->id, 'link' => 'https://exemple.fr/a']);

        $this->actingAs($auteur, 'sanctum')
            ->getJson('/api/liens/existant?link='.urlencode('https://exemple.fr/a'))
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_a_link_nobody_shared_proposes_nothing(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/liens/existant?link='.urlencode('https://inconnu.fr/page'))
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    // Export RGPD

    public function test_a_member_downloads_everything_the_platform_holds(): void
    {
        $membre = User::factory()->create(['username' => 'camille']);
        $review = Review::factory()->create(['user_id' => $membre->id, 'content' => 'Mon avis a moi.']);
        Comment::factory()->create(['user_id' => $membre->id, 'review_id' => $review->id]);

        $reponse = $this->actingAs($membre, 'sanctum')
            ->getJson('/api/mes-donnees/export')
            ->assertStatus(200)
            ->assertHeader('Content-Disposition', 'attachment; filename="yowl-camille-'.now()->format('Y-m-d').'.json"');

        $donnees = $reponse->json();
        $this->assertSame('camille', $donnees['compte']['pseudo']);
        $this->assertSame('Mon avis a moi.', $donnees['avis'][0]['contenu']);
        $this->assertCount(1, $donnees['commentaires']);
    }

    public function test_the_export_holds_nobody_elses_content(): void
    {
        $membre = User::factory()->create();
        $autre = User::factory()->create();
        Review::factory()->create(['user_id' => $autre->id, 'content' => 'Ecrit par quelqu un d autre.']);

        $donnees = $this->actingAs($membre, 'sanctum')->getJson('/api/mes-donnees/export')->json();

        $this->assertSame([], $donnees['avis']);
    }

    public function test_a_guest_exports_nothing(): void
    {
        $this->getJson('/api/mes-donnees/export')->assertStatus(401);
    }

    // Croissance

    public function test_only_an_administrator_reads_the_growth_dashboard(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/admin/croissance')
            ->assertStatus(403);

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/admin/croissance')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['mau', 'inscriptions', 'commentaires', 'engagement', 'sessions', 'retention']]);
    }

    public function test_a_presence_ping_is_recorded_once_per_minute(): void
    {
        $membre = User::factory()->create();

        foreach (range(1, 4) as $ignored) {
            $this->actingAs($membre, 'sanctum')->postJson('/api/presence')->assertStatus(200);
        }

        $this->assertSame(1, ActivityPing::where('user_id', $membre->id)->count());
    }

    public function test_a_silence_longer_than_the_threshold_opens_a_new_session(): void
    {
        $membre = User::factory()->create();

        // Une premiere visite de dix minutes, puis une seconde deux heures
        // plus tard : deux sessions, pas une de deux heures dix.
        foreach ([0, 5, 10] as $minute) {
            ActivityPing::create(['user_id' => $membre->id, 'pinged_at' => now()->subHours(3)->addMinutes($minute)]);
        }
        foreach ([0, 4] as $minute) {
            ActivityPing::create(['user_id' => $membre->id, 'pinged_at' => now()->subHour()->addMinutes($minute)]);
        }

        $sessions = Growth::sessions();

        $this->assertSame(2, $sessions['sessions']);
        $this->assertSame(8.0, $sessions['moyenne_minutes']);
    }

    public function test_two_members_never_share_a_session(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();

        ActivityPing::create(['user_id' => $a->id, 'pinged_at' => now()->subMinutes(10)]);
        ActivityPing::create(['user_id' => $b->id, 'pinged_at' => now()->subMinutes(9)]);

        $this->assertSame(2, Growth::sessions()['sessions']);
    }

    public function test_a_cohort_whose_window_is_still_open_reports_nothing_rather_than_zero(): void
    {
        User::factory()->create(['created_at' => now()->subDays(2)]);

        $cohortes = collect(Growth::retention());
        $recente = $cohortes->last();

        $this->assertNotNull($recente);
        $this->assertNull($recente['d30']);
    }

    public function test_the_engagement_rate_is_the_share_of_views_that_led_to_something(): void
    {
        $review = Review::factory()->create(['nb_views' => 100]);
        Comment::factory()->count(3)->create(['review_id' => $review->id]);

        $engagement = Growth::engagement();

        $this->assertSame(100, $engagement['vues']);
        $this->assertSame(3, $engagement['interactions']);
        $this->assertSame(3.0, $engagement['taux']);
    }

    public function test_the_growth_export_is_a_readable_spreadsheet(): void
    {
        $contenu = $this->actingAs($this->admin(), 'sanctum')
            ->get('/api/admin/croissance/export')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8')
            ->getContent();

        $this->assertStringContainsString('Membres actifs mensuels', $contenu);
        $this->assertStringContainsString('Retention par cohorte', $contenu);
    }

    public function test_old_pings_are_pruned(): void
    {
        $membre = User::factory()->create();
        ActivityPing::create(['user_id' => $membre->id, 'pinged_at' => now()->subDays(200)]);
        ActivityPing::create(['user_id' => $membre->id, 'pinged_at' => now()->subDays(10)]);

        $this->artisan('yowl:prune-pings')->assertExitCode(0);

        $this->assertSame(1, ActivityPing::count());
    }
}
