<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The API answers the same whether a browser sent an Origin or not.
 *
 * A deployment shipped with Sanctum's stateful middleware in front of the API
 * group. Any request carrying an Origin matching FRONTEND_URL was routed
 * through the full web stack: encrypted cookies, session, CSRF. The same
 * address then answered 200 from a terminal and 500 from a browser, and the
 * session store happened to be broken in production. The client authenticates
 * with bearer tokens and never asks for a CSRF cookie, so that stack was pure
 * liability.
 */
class StatelessApiTest extends TestCase
{
    use RefreshDatabase;

    public static function routesPubliques(): array
    {
        return [
            'sante' => ['/api/health'],
            'fil' => ['/api/reviews'],
            'identite du site' => ['/api/site'],
            'sujets' => ['/api/tags'],
            'compteurs' => ['/api/kpi'],
        ];
    }

    /**
     * @dataProvider routesPubliques
     */
    public function test_an_origin_header_changes_nothing(string $chemin): void
    {
        $sans = $this->getJson($chemin);
        $avec = $this->withHeader('Origin', config('app.frontend_url'))->getJson($chemin);

        $this->assertSame(200, $sans->status(), $chemin.' sans Origin');
        $this->assertSame(200, $avec->status(), $chemin.' avec Origin');
    }

    /**
     * @dataProvider routesPubliques
     */
    public function test_the_api_survives_a_broken_session_store(string $chemin): void
    {
        // La panne observée en production, reproduite : plus rien pour écrire
        // une session. L'API ne doit pas s'en apercevoir, puisqu'elle n'en
        // ouvre aucune.
        Schema::drop('sessions');

        $this->withHeader('Origin', config('app.frontend_url'))
            ->getJson($chemin)
            ->assertStatus(200);
    }

    public function test_the_stateless_routes_leave_the_web_group_alone(): void
    {
        Schema::drop('sessions');

        $this->get('/robots.txt')->assertStatus(200);
        $this->get('/sitemap.xml')->assertStatus(200);
    }

    public function test_no_session_cookie_is_ever_set_on_the_api(): void
    {
        $reponse = $this->withHeader('Origin', config('app.frontend_url'))
            ->getJson('/api/reviews');

        // Un cookie de session sur une API par jeton signale que la pile web
        // s'est réinvitée dans le circuit.
        $this->assertEmpty(
            array_filter($reponse->headers->getCookies(), fn ($c) => $c->getName() === config('session.cookie')),
            'Une session a été ouverte sur une route qui doit rester sans état.'
        );
    }
}
