<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BroadcastGuardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Reruns the guard against the current configuration.
     */
    private function rejouerLaGarde(): void
    {
        $provider = new AppServiceProvider($this->app);
        $methode = new \ReflectionMethod($provider, 'guardBroadcastConnection');
        $methode->setAccessible(true);
        $methode->invoke($provider);
    }

    public function test_an_unknown_driver_falls_back_instead_of_crashing(): void
    {
        Log::spy();
        // La faute réellement commise : la valeur de MAIL_MAILER posée sur
        // BROADCAST_CONNECTION. Elle mettait le conteneur entier par terre.
        config(['broadcasting.default' => 'smtp']);

        $this->rejouerLaGarde();

        $this->assertSame('null', config('broadcasting.default'));
        Log::shouldHaveReceived('error')->once();
    }

    public function test_a_known_driver_is_left_alone(): void
    {
        foreach (['log', 'null', 'reverb', 'pusher'] as $valide) {
            config(['broadcasting.default' => $valide]);
            $this->rejouerLaGarde();
            $this->assertSame($valide, config('broadcasting.default'));
        }
    }

    public function test_the_application_still_boots_with_a_wrong_driver(): void
    {
        config(['broadcasting.default' => 'ce-nest-pas-un-pilote']);
        $this->rejouerLaGarde();

        // Le fil doit répondre : c'était tout l'enjeu.
        $this->getJson('/api/health')->assertStatus(200);
        $this->getJson('/api/reviews')->assertStatus(200);
    }
}
