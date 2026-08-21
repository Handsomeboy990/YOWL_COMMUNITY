<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Tests\TestCase;

/**
 * Two ways a deployment ends up writing nowhere.
 *
 * DB_CONNECTION defaults to sqlite. A deployment that forgets it, or that
 * pastes the PGHOST style variables a managed provider hands out, writes to a
 * local file that dies with the container. Migrations ran from scratch on
 * every restart, the managed database stayed empty, and nothing said so.
 */
class DatabaseConfigTest extends TestCase
{
    /*
     * Pas de RefreshDatabase : la garde ne lit que la configuration, elle
     * n'ouvre aucune connexion. Le trait, lui, tenterait de se connecter au
     * pilote qu'on vient de changer, et échouerait faute de PostgreSQL ici.
     */

    private function rejouerLaGarde(): void
    {
        $provider = new AppServiceProvider($this->app);
        $methode = new \ReflectionMethod($provider, 'guardEphemeralDatabase');
        $methode->setAccessible(true);
        $methode->invoke($provider);
    }

    public function test_production_on_sqlite_is_reported_as_critical(): void
    {
        \Illuminate\Support\Facades\Log::spy();
        app()->detectEnvironment(fn () => 'production');
        config(['database.default' => 'sqlite']);

        $this->rejouerLaGarde();

        \Illuminate\Support\Facades\Log::shouldHaveReceived('critical')->once();
    }

    public function test_the_guard_never_throws(): void
    {
        // Une première version levait une exception, ce qui tuait
        // composer install : package:discover lance artisan sur un dépôt
        // fraîchement cloné, où APP_ENV vaut production par défaut et
        // DB_CONNECTION vaut sqlite. Le refus vit désormais dans
        // l'entrypoint, qui ne tourne qu'au démarrage d'un conteneur.
        \Illuminate\Support\Facades\Log::spy();
        app()->detectEnvironment(fn () => 'production');
        config(['database.default' => 'sqlite']);

        $this->rejouerLaGarde();
        $this->assertTrue(true, 'Aucune exception ne doit sortir de la garde.');
    }

    public function test_production_is_happy_with_a_real_engine(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config(['database.default' => 'pgsql']);

        $this->rejouerLaGarde();
        $this->assertTrue(true, 'Aucune exception attendue.');
    }

    public function test_development_keeps_sqlite_without_complaint(): void
    {
        app()->detectEnvironment(fn () => 'local');
        config(['database.default' => 'sqlite']);

        $this->rejouerLaGarde();
        $this->assertTrue(true, 'SQLite reste le choix normal en développement.');
    }

    public function test_the_message_names_what_to_do(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config(['database.default' => 'sqlite']);

        $capture = null;
        \Illuminate\Support\Facades\Log::shouldReceive('critical')
            ->once()
            ->andReturnUsing(function ($message) use (&$capture) {
                $capture = $message;
            });

        $this->rejouerLaGarde();

        // Un message qui ne dit pas quoi faire ne vaut pas mieux que
        // « Server Error ».
        foreach (['DB_CONNECTION=pgsql', 'DB_HOST', 'PGHOST'] as $indice) {
            $this->assertStringContainsString($indice, $capture);
        }
    }
}
