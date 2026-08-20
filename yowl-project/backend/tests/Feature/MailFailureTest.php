<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * A dead mail relay must not look like a broken application.
 *
 * With MAIL_MAILER=smtp pointing at nothing, Symfony throws and the request
 * answered 500 with the words "Server Error". Registration failed outright,
 * so nobody could create an account at all, and a password reset told the
 * visitor nothing they could act on.
 */
class MailFailureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('client', 'web');
        Role::findOrCreate('admin', 'web');
    }

    /**
     * Un relais qui refuse la connexion, comme en production.
     *
     * Un simulacre sur la façade Mail ne suffisait pas : sendResetLink passe
     * par le système de notifications, pas par Mail::to(). On pointe donc le
     * transport dans le vide, ce qui exerce le vrai chemin. Le port 1 refuse
     * immédiatement, sans faire attendre la suite.
     */
    private function relaisMort(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 1,
            'mail.mailers.smtp.timeout' => 1,
        ]);
    }

    public function test_registration_keeps_the_account_when_the_code_cannot_be_sent(): void
    {
        $this->relaisMort();

        $this->postJson('/api/register', [
            'username' => 'camille',
            'fullname' => 'Camille Renard',
            'email' => 'camille@exemple.fr',
            'password' => 'Un-Mot-Solide-2026',
            'password_confirmation' => 'Un-Mot-Solide-2026',
            'birthdate' => '2000-01-01',
        ])
            ->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonPath('delivered', false);

        // Le compte survit : c'est le code qui manque, et il se redemande.
        $this->assertDatabaseHas('users', ['email' => 'camille@exemple.fr']);
    }

    public function test_the_reset_link_says_it_could_not_be_sent(): void
    {
        User::factory()->create(['email' => 'camille@exemple.fr']);
        $this->relaisMort();

        $reponse = $this->postJson('/api/forgot-password', ['email' => 'camille@exemple.fr'])
            ->assertStatus(503);

        // 503 et non 500 : la demande était valide, c'est le service qui ne
        // peut pas l'honorer pour l'instant.
        $this->assertStringNotContainsString('Server Error', $reponse->getContent());
        $this->assertStringContainsString('Réessaie', $reponse->json('message'));
    }

    public function test_a_working_relay_still_behaves_normally(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'camille@exemple.fr']);

        $this->postJson('/api/forgot-password', ['email' => 'camille@exemple.fr'])
            ->assertStatus(200);
    }

    public function test_registration_reports_delivery_when_the_relay_works(): void
    {
        Mail::fake();

        $this->postJson('/api/register', [
            'username' => 'noah',
            'fullname' => 'Noah Moreau',
            'email' => 'noah@exemple.fr',
            'password' => 'Un-Mot-Solide-2026',
            'password_confirmation' => 'Un-Mot-Solide-2026',
            'birthdate' => '2000-01-01',
        ])
            ->assertStatus(200)
            ->assertJsonPath('delivered', true);
    }
}
