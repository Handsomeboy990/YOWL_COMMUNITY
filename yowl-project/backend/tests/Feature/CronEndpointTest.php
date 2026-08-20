<?php

namespace Tests\Feature;

use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CronEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_token_runs_the_due_work(): void
    {
        config(['services.cron.token' => 'jeton-de-test-valide']);

        $this->postJson('/cron/jeton-de-test-valide')
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['duree_ms', 'lancees']]);
    }

    public function test_a_wrong_token_is_indistinguishable_from_no_route(): void
    {
        config(['services.cron.token' => 'jeton-de-test-valide']);

        $this->postJson('/cron/ce-nest-pas-le-bon')->assertStatus(404);
    }

    public function test_the_route_does_not_exist_when_no_token_is_configured(): void
    {
        config(['services.cron.token' => null]);

        // Sur un hebergement qui ne dort pas, la porte reste fermee meme si
        // quelqu'un devine une adresse plausible.
        $this->postJson('/cron/nimporte-quoi')->assertStatus(404);
        $this->postJson('/cron/')->assertStatus(404);
    }

    public function test_it_publishes_a_review_whose_hour_has_passed(): void
    {
        config(['services.cron.token' => 'jeton-de-test-valide']);

        $review = Review::factory()->create();
        $review->forceFill(['is_published' => false, 'scheduled_for' => now()->subMinutes(10)])->save();

        $this->postJson('/cron/jeton-de-test-valide')->assertStatus(200);

        // C'est tout l'intérêt de cette route : sans elle, un conteneur
        // endormi laisserait cet avis programmé pour toujours.
        $this->assertTrue($review->fresh()->is_published);
    }

    public function test_a_task_is_not_run_twice_within_its_interval(): void
    {
        config(['services.cron.token' => 'jeton-de-test-valide']);

        $premier = $this->postJson('/cron/jeton-de-test-valide')->json('data.lancees');
        $second = $this->postJson('/cron/jeton-de-test-valide')->json('data.lancees');

        // Le recalcul des scores est horaire : deux appels rapprochés ne
        // doivent pas le refaire.
        $this->assertContains('yowl:refresh-scores', $premier);
        $this->assertNotContains('yowl:refresh-scores', $second);

        // La publication des avis programmés, elle, tourne à chaque appel.
        $this->assertContains('yowl:publish-scheduled', $second);
    }

    public function test_a_task_runs_again_once_its_interval_has_passed(): void
    {
        config(['services.cron.token' => 'jeton-de-test-valide']);

        $this->postJson('/cron/jeton-de-test-valide')->assertStatus(200);

        $this->travel(2)->hours();

        $lancees = $this->postJson('/cron/jeton-de-test-valide')->json('data.lancees');
        $this->assertContains('yowl:refresh-scores', $lancees);
    }
}
