<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Signing in must not need a session store.
 *
 * Auth::attempt() goes through the session guard, which writes a row. The
 * guest middleware asks the same guard whether somebody is already signed in.
 * Neither serves an API that only issues bearer tokens, and both made every
 * login answer 500 on a deployment whose session store was unusable, with
 * "Server Error" as the only clue.
 */
class StatelessLoginTest extends TestCase
{
    use RefreshDatabase;

    private function membre(): User
    {
        return User::factory()->create([
            'username' => 'camille',
            'email' => 'camille@exemple.fr',
            'password' => Hash::make('Un-Mot-Solide-2026'),
            'email_verified_at' => now(),
        ]);
    }

    public function test_a_member_signs_in_without_any_session_store(): void
    {
        $this->membre();
        // La panne exacte de la production : plus rien pour écrire une session.
        Schema::drop('sessions');

        $this->postJson('/api/login', [
            'email' => 'camille@exemple.fr',
            'password' => 'Un-Mot-Solide-2026',
            'remember' => true,
        ])
            ->assertStatus(200)
            ->assertJsonStructure(['token'])
            ->assertJsonPath('user.username', 'camille');
    }

    public function test_a_wrong_password_still_answers_properly_without_sessions(): void
    {
        $this->membre();
        Schema::drop('sessions');

        $this->postJson('/api/login', [
            'email' => 'camille@exemple.fr',
            'password' => 'ce-nest-pas-le-bon',
        ])->assertStatus(422);
    }

    public function test_signing_in_opens_no_session(): void
    {
        $this->membre();

        $reponse = $this->postJson('/api/login', [
            'email' => 'camille@exemple.fr',
            'password' => 'Un-Mot-Solide-2026',
        ])->assertStatus(200);

        // Un cookie de session sur une API à jetons signale que le garde web
        // s'est réinvité dans le circuit.
        $this->assertEmpty(
            array_filter($reponse->headers->getCookies(), fn ($c) => $c->getName() === config('session.cookie')),
            'La connexion ne doit ouvrir aucune session.'
        );
        $this->assertSame(0, \DB::table('sessions')->count());
    }

    public function test_registration_needs_no_session_store_either(): void
    {
        \Spatie\Permission\Models\Role::findOrCreate('client', 'web');
        Schema::drop('sessions');

        $this->postJson('/api/register', [
            'username' => 'noah',
            'fullname' => 'Noah Moreau',
            'email' => 'noah@exemple.fr',
            'password' => 'Un-Mot-Solide-2026',
            'password_confirmation' => 'Un-Mot-Solide-2026',
            'birthdate' => '2000-01-01',
        ])->assertStatus(200);
    }
}
