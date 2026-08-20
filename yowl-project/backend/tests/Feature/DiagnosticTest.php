<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Naming what the deployment is actually talking to.
 *
 * A container ran its migrations from scratch on every restart while the
 * managed database stayed empty: it was writing to a local SQLite file that
 * died with it. Nothing in the logs said so, and a host without a shell gives
 * no way to ask. This endpoint asks.
 */
class DiagnosticTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_names_the_database_engine_in_use(): void
    {
        config(['services.cron.token' => 'jeton-de-test']);

        $donnees = $this->getJson('/diagnostic/jeton-de-test')
            ->assertStatus(200)
            ->json('data');

        $this->assertArrayHasKey('base_de_donnees', $donnees);
        $this->assertTrue($donnees['base_de_donnees']['joignable']);
        $this->assertIsInt($donnees['base_de_donnees']['migrations_appliquees']);
    }

    public function test_it_warns_when_the_database_will_not_survive_a_restart(): void
    {
        config(['services.cron.token' => 'jeton-de-test']);

        // La suite tourne sur SQLite : c'est exactement l'état à signaler.
        $base = $this->getJson('/diagnostic/jeton-de-test')->json('data.base_de_donnees');

        $this->assertFalse($base['persistante']);
        $this->assertStringContainsString('DB_CONNECTION', $base['avertissement']);
    }

    public function test_it_reports_the_cache_and_the_session_store(): void
    {
        config(['services.cron.token' => 'jeton-de-test']);

        $donnees = $this->getJson('/diagnostic/jeton-de-test')->json('data');

        $this->assertTrue($donnees['cache']['fonctionne']);
        $this->assertTrue($donnees['sessions']['utilisable']);
    }

    public function test_it_never_returns_a_credential(): void
    {
        config([
            'services.cron.token' => 'jeton-de-test',
            'database.connections.pgsql.password' => 'mot-de-passe-secret',
            'mail.mailers.smtp.password' => 'cle-smtp-secrete',
            'app.key' => 'base64:CLE-APPLICATIVE',
        ]);

        $corps = $this->getJson('/diagnostic/jeton-de-test')->getContent();

        foreach (['mot-de-passe-secret', 'cle-smtp-secrete', 'CLE-APPLICATIVE'] as $secret) {
            $this->assertStringNotContainsString($secret, $corps);
        }
    }

    public function test_a_wrong_token_is_indistinguishable_from_no_route(): void
    {
        config(['services.cron.token' => 'jeton-de-test']);

        $this->getJson('/diagnostic/ce-nest-pas-le-bon')->assertStatus(404);
    }

    public function test_the_route_does_not_exist_without_a_token(): void
    {
        config(['services.cron.token' => null]);

        $this->getJson('/diagnostic/nimporte-quoi')->assertStatus(404);
    }
}
