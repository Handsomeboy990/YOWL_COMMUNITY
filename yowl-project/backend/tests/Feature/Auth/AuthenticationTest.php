<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token']);
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/logout');

        $response->assertStatus(200);
    }

    public function test_a_deactivated_account_cannot_sign_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_a_member_older_than_the_registration_limit_keeps_their_account(): void
    {
        // La borne d'age s'applique a l'inscription. Un membre inscrit a 30 ans
        // ne doit pas etre expulse le jour de ses 36 ans.
        $user = User::factory()->create([
            'birthdate' => now()->subYears(42)->format('Y-m-d'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token']);
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/register', ['email' => 'flood'.$attempt.'@example.com']);
        }

        $this->postJson('/api/register', ['email' => 'flood-last@example.com'])
            ->assertStatus(429);
    }

    /**
     * Un compte créé hors de Laravel, typiquement depuis la console SQL de
     * l'hébergeur avec pgcrypto, porte un hachage préfixé $2a$ au lieu de
     * $2y$. Le vérificateur de Laravel lève alors une exception au lieu de
     * répondre faux, et la connexion sortait en 500 sans rien expliquer,
     * y compris quand le mot de passe était mauvais.
     */
    public function test_an_unreadable_password_hash_is_refused_not_a_server_error(): void
    {
        $user = User::factory()->create();

        // Le même hachage bcrypt, avec l'ancien marqueur d'algorithme.
        //
        // L'écriture passe par le constructeur de requêtes, jamais par le
        // modèle : le cast « hashed » appelle Hash::isHashed(), ne reconnaît
        // pas $2a$, et re-hacherait silencieusement la valeur en $2y$. Le
        // script SQL qui crée un compte depuis la console de l'hébergeur
        // écrit lui aussi directement dans la table, sans ce garde-fou.
        DB::table('users')->where('id', $user->id)->update([
            'password' => str_replace('$2y$', '$2a$', bcrypt('Password@990')),
        ]);

        foreach (['Password@990', 'mauvais-mot-de-passe'] as $tentative) {
            $reponse = $this->postJson('/api/login', [
                'email' => $user->email,
                'password' => $tentative,
            ]);

            $reponse->assertStatus(422);
            $reponse->assertJsonValidationErrors('email');
        }
    }
}
