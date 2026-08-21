<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Les emails sortants : ce qui part, et ce qui est annoncé quand rien ne part.
 *
 * Le mot de passe oublié arrivait, le code de vérification jamais, alors que
 * les deux passent par le même transport.
 */
class OutgoingMailTest extends TestCase
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

    public function test_the_verification_code_reaches_mailjet_with_a_body(): void
    {
        Http::fake(['api.mailjet.com/*' => Http::response(['Messages' => [['Status' => 'success']]], 200)]);

        $membre = User::factory()->create(['email_verified_at' => null]);

        $this->postJson('/api/email/otp/resend', ['email' => $membre->email])->assertOk();

        Http::assertSent(function (Request $requete) {
            $message = $requete->data()['Messages'][0] ?? [];

            // Les deux versions, pas une seule. Un message transactionnel en
            // HTML seul est un signal de courrier indesirable pour la plupart
            // des filtres : celui-ci n'arrivait pas, la reinitialisation de
            // mot de passe si, et elle porte les deux.
            $this->assertNotEmpty($message['TextPart'] ?? '', 'version texte absente');
            $this->assertNotEmpty($message['HTMLPart'] ?? '', 'version HTML absente');
            $this->assertNotEmpty($message['Subject'] ?? '');
            $this->assertNotEmpty($message['From']['Email'] ?? '');

            return true;
        });
    }

    public function test_a_refused_send_is_reported_instead_of_announced_as_sent(): void
    {
        Http::fake(['api.mailjet.com/*' => Http::response(['ErrorMessage' => 'refuse'], 400)]);

        $membre = User::factory()->create(['email_verified_at' => null]);

        // La reponse annoncait « Code envoye » quoi qu'il arrive : personne ne
        // pouvait savoir que rien n'etait parti, et le code en base laissait
        // croire le contraire.
        $this->postJson('/api/email/otp/resend', ['email' => $membre->email])
            ->assertStatus(202)
            ->assertJsonPath('delivered', false);
    }

    /**
     * Les trois emails sortants portent leurs deux versions.
     *
     * Le rendu n'a lieu qu'à l'envoi : un gabarit texte qui référence une
     * variable inexistante ne se voit nulle part ailleurs, et ferait échouer
     * l'envoi en production sans avoir jamais échoué en test.
     */
    public function test_every_outgoing_email_carries_both_a_text_and_an_html_version(): void
    {
        $membre = User::factory()->create(['username' => 'lecteur']);
        $avis = \App\Models\Review::factory()->create(['user_id' => $membre->id]);

        $messages = [
            'code de vérification' => new \App\Mail\EmailVerificationCode('123456'),
            'résumé hebdomadaire' => new \App\Mail\WeeklyDigest(
                $membre, collect([$avis]), ['received' => 3, 'comments' => 2, 'published' => 1], 'https://exemple.fr/desinscription/x'
            ),
            'campagne' => new \App\Mail\CampaignMessage(
                $membre, 'Un sujet', '<p>Premier paragraphe.</p><p>Second paragraphe.</p>',
                'https://exemple.fr/desinscription/x', 'YOWL'
            ),
        ];

        foreach ($messages as $nom => $message) {
            $rendu = $message->render();
            $this->assertNotEmpty($rendu, $nom.' : rendu HTML vide');

            $construit = $message->build();
            $texte = $construit->textView;
            $this->assertNotNull($texte, $nom.' : aucune version texte declaree');

            // Le rendu du gabarit texte, qui est la ou une variable manquante
            // se manifeste.
            $sortie = view($texte, $construit->buildViewData())->render();
            $this->assertNotEmpty(trim($sortie), $nom.' : version texte vide');
        }
    }
}
