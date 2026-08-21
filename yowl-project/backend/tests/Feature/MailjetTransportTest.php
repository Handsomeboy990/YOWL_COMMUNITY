<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Envoi par l'API HTTPS de Mailjet.
 *
 * La raison de ce transport est le réseau, pas le fournisseur : les offres
 * gratuites de beaucoup d'hébergeurs filtrent les ports SMTP, et un port
 * filtré ne refuse pas la connexion, il l'absorbe. Le port 443 reste ouvert.
 */
class MailjetTransportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('client', 'web');

        config([
            'mail.default' => 'mailjet',
            'mail.mailers.mailjet.key' => 'une-cle',
            'mail.mailers.mailjet.secret' => 'un-secret',
            'mail.from.address' => 'no-reply@exemple.fr',
            'mail.from.name' => 'YOWL',
        ]);
    }

    private function inscrire(string $pseudo = 'nouvelle'): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/register', [
            'username' => $pseudo,
            'fullname' => 'Nouvelle Personne',
            'email' => $pseudo.'@exemple.fr',
            'password' => 'Password@990',
            'password_confirmation' => 'Password@990',
            'birthdate' => now()->subYears(20)->format('Y-m-d'),
        ]);
    }

    public function test_a_message_reaches_mailjet_in_the_shape_it_expects(): void
    {
        Http::fake(['api.mailjet.com/*' => Http::response(['Messages' => [['Status' => 'success']]], 200)]);

        $this->inscrire()->assertOk()->assertJsonPath('delivered', true);

        Http::assertSent(function (Request $requete) {
            $corps = $requete->data()['Messages'][0] ?? [];

            $this->assertSame('https://api.mailjet.com/v3.1/send', $requete->url());
            // L'authentification passe par l'en-tête Basic, jamais par le corps.
            $this->assertStringStartsWith('Basic ', $requete->header('Authorization')[0] ?? '');
            $this->assertSame('no-reply@exemple.fr', $corps['From']['Email']);
            $this->assertSame('nouvelle@exemple.fr', $corps['To'][0]['Email']);
            $this->assertNotEmpty($corps['Subject']);
            $this->assertNotEmpty($corps['HTMLPart'] ?? $corps['TextPart'] ?? '');

            return true;
        });
    }

    public function test_a_refusal_costs_the_code_and_not_the_account(): void
    {
        // Le cas le plus fréquent au démarrage : expéditeur non validé.
        Http::fake(['api.mailjet.com/*' => Http::response(
            ['ErrorMessage' => 'From is not an authorized sender'], 400
        )]);

        $this->inscrire('refusee')
            ->assertStatus(202)
            ->assertJsonPath('success', true)
            ->assertJsonPath('delivered', false);

        $this->assertDatabaseHas('users', ['email' => 'refusee@exemple.fr']);
    }

    public function test_missing_credentials_fail_without_taking_the_request_down(): void
    {
        config(['mail.mailers.mailjet.key' => '', 'mail.mailers.mailjet.secret' => '']);
        Http::fake();

        $this->inscrire('sanscle')
            ->assertStatus(202)
            ->assertJsonPath('delivered', false);

        // Rien n'est parti : la clé manquante est détectée avant l'appel.
        Http::assertNothingSent();
    }

    public function test_an_unreachable_api_is_a_transport_failure_not_a_crash(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('delai depasse'));

        $this->inscrire('injoignable')
            ->assertStatus(202)
            ->assertJsonPath('delivered', false);

        $this->assertDatabaseHas('users', ['email' => 'injoignable@exemple.fr']);
    }

    public function test_the_transport_is_the_one_configured(): void
    {
        $this->assertInstanceOf(
            \App\Mail\Transport\MailjetTransport::class,
            Mail::mailer('mailjet')->getSymfonyTransport()
        );
    }
}
