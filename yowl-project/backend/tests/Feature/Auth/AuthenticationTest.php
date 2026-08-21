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

    /**
     * Chaque refus porte un code, et le navigateur s'en sert.
     *
     * Il distinguait jusqu'ici le compte non vérifié en comparant le texte
     * anglais du message : traduire ce message suffisait à casser l'ouverture
     * de la fenêtre de saisie du code, sans erreur nulle part.
     */
    public function test_every_refusal_carries_a_stable_code(): void
    {
        $verifie = User::factory()->create();

        $this->postJson('/api/login', ['email' => $verifie->email, 'password' => 'faux'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'identifiants_invalides');

        $desactive = User::factory()->create(['is_active' => false]);
        $this->postJson('/api/login', ['email' => $desactive->email, 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'compte_desactive');

        // Tolerance a zero : la verification est exigee des la premiere
        // connexion, ce qui est le cas que ce test veut observer.
        \App\Support\Settings::set('registration.verification_grace', 0);

        $nonVerifie = User::factory()->create(['email_verified_at' => null]);
        $this->postJson('/api/login', ['email' => $nonVerifie->email, 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'email_non_verifie');
    }

    /**
     * Un refus doit dire quoi faire ensuite, sinon la personne réessaie à
     * l'identique. Et il ne doit pas dire si l'adresse existe, sans quoi le
     * formulaire devient un annuaire.
     */
    public function test_a_refusal_says_what_to_do_next_without_revealing_the_account(): void
    {
        $existant = User::factory()->create();

        $connu = $this->postJson('/api/login', ['email' => $existant->email, 'password' => 'faux']);
        $inconnu = $this->postJson('/api/login', ['email' => 'personne@nulle-part.fr', 'password' => 'faux']);

        // Le même mot pour un compte connu et pour une adresse inventée : la
        // différence transformerait le formulaire en annuaire.
        $this->assertSame($connu->json('message'), $inconnu->json('message'));

        \App\Support\Settings::set('registration.verification_grace', 0);
        $nonVerifie = User::factory()->create(['email_verified_at' => null]);

        // Les deux catalogues sont vérifiés : c'est celui qui n'est pas la
        // langue des tests qui se dégrade sans que personne le voie.
        $attendus = [
            'fr' => ['Mot de passe oublié', 'six chiffres'],
            'en' => ['Forgot password', 'six digit code'],
        ];

        foreach ($attendus as $langue => [$indiceRefus, $indiceVerification]) {
            $this->app->setLocale($langue);

            $this->assertStringContainsString(
                $indiceRefus,
                $this->postJson('/api/login', ['email' => $existant->email, 'password' => 'faux'])->json('message'),
                'refus en '.$langue
            );

            $this->assertStringContainsString(
                $indiceVerification,
                $this->postJson('/api/login', ['email' => $nonVerifie->email, 'password' => 'password'])->json('message'),
                'compte non vérifié en '.$langue
            );
        }
    }

    /**
     * La vérification d'adresse est rappelée, puis exigée.
     *
     * L'exiger dès la première connexion transforme une panne de relais en
     * porte close pour tout le monde, y compris pour ceux dont le code n'est
     * jamais parti.
     */
    public function test_an_unverified_account_may_sign_in_until_the_grace_runs_out(): void
    {
        \App\Support\Settings::set('registration.verification_grace', 3);

        $membre = User::factory()->create(['email_verified_at' => null]);

        for ($tentative = 1; $tentative <= 3; $tentative++) {
            $reponse = $this->postJson('/api/login', [
                'email' => $membre->email,
                'password' => 'password',
            ])->assertOk();

            $this->assertFalse($reponse->json('verification.verifie'));
            // Le décompte doit être lisible : sans lui, le blocage arrive
            // sans prévenir.
            $this->assertSame(3 - $tentative, $reponse->json('verification.restant'));
        }

        // La quatrième bute sur le seuil.
        $this->postJson('/api/login', ['email' => $membre->email, 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'email_non_verifie');
    }

    public function test_a_verified_account_is_never_counted_down(): void
    {
        \App\Support\Settings::set('registration.verification_grace', 1);

        $membre = User::factory()->create();

        foreach (range(1, 3) as $ignore) {
            $this->postJson('/api/login', ['email' => $membre->email, 'password' => 'password'])
                ->assertOk()
                ->assertJsonPath('verification.verifie', true)
                ->assertJsonPath('verification.restant', 0);
        }
    }

    /**
     * Le compteur avance sur les connexions réussies, pas sur les refus.
     *
     * Compté avant la vérification des identifiants, une salve de mauvais
     * mots de passe épuiserait le délai de grâce sans que personne ne soit
     * jamais entré.
     */
    public function test_failed_attempts_do_not_eat_into_the_grace(): void
    {
        \App\Support\Settings::set('registration.verification_grace', 5);

        $membre = User::factory()->create(['email_verified_at' => null]);

        foreach (range(1, 3) as $ignore) {
            $this->postJson('/api/login', ['email' => $membre->email, 'password' => 'faux'])
                ->assertStatus(422);
        }

        $this->assertSame(0, $membre->fresh()->login_count);

        $this->postJson('/api/login', ['email' => $membre->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('verification.restant', 4);
    }
}
