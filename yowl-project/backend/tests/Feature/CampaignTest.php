<?php

namespace Tests\Feature;

use App\Mail\CampaignMessage;
use App\Models\Campaign;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function brouillon(array $attributs = []): Campaign
    {
        return Campaign::create(array_merge([
            'subject' => 'Du nouveau sur {{site}}',
            'body' => '<p>Bonjour {{pseudo}}, voici les nouvelles.</p>',
            'purpose' => 'announcement',
            'audience' => 'all',
            'status' => Campaign::STATUS_DRAFT,
        ], $attributs));
    }

    public function test_the_console_offers_a_template_for_each_purpose(): void
    {
        $donnees = $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/admin/campagnes/options')
            ->assertStatus(200)
            ->json('data');

        $this->assertArrayHasKey('promotion', $donnees['templates']);
        $this->assertArrayHasKey('feedback', $donnees['templates']);
        $this->assertNotEmpty($donnees['templates']['promotion']['subject']);
        $this->assertArrayHasKey('{{pseudo}}', $donnees['placeholders']);
    }

    public function test_a_member_cannot_reach_the_campaigns(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/admin/campagnes')
            ->assertStatus(403);
    }

    public function test_an_administrator_saves_a_draft(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/campagnes', [
                'subject' => 'Une annonce',
                'body' => '<p>Bonjour {{pseudo}}</p><script>alert(1)</script>',
                'purpose' => 'announcement',
                'audience' => 'all',
            ])
            ->assertStatus(201);

        $campagne = Campaign::first();
        $this->assertSame(Campaign::STATUS_DRAFT, $campagne->status);
        // Le corps passe par la meme liste blanche que les pages legales.
        $this->assertStringNotContainsString('<script', $campagne->body);
    }

    public function test_a_segment_counts_only_who_it_matches(): void
    {
        $auteur = User::factory()->create();
        Review::factory()->create(['user_id' => $auteur->id]);
        User::factory()->count(3)->create();

        $reponse = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/campagnes/audience', ['audience' => 'segment', 'segment' => 'authors'])
            ->assertStatus(200);

        $this->assertSame(1, $reponse->json('data.count'));
    }

    public function test_somebody_who_opted_out_is_never_counted(): void
    {
        User::factory()->count(2)->create();
        User::factory()->create(['email_optout' => true]);

        $avant = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/campagnes/audience', ['audience' => 'all'])
            ->json('data.count');

        // L'administrateur lui-meme compte, plus les deux joignables.
        $this->assertSame(3, $avant);
    }

    public function test_sending_freezes_the_list_and_queues_the_run(): void
    {
        Queue::fake();
        User::factory()->count(4)->create();
        $campagne = $this->brouillon();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/campagnes/'.$campagne->id.'/envoi')
            ->assertStatus(200);

        $campagne->refresh();
        $this->assertSame(Campaign::STATUS_SENDING, $campagne->status);
        $this->assertSame(5, $campagne->recipients_count);
        $this->assertSame(5, $campagne->recipients()->count());
        Queue::assertPushed(\App\Jobs\SendCampaign::class);
    }

    public function test_the_placeholders_are_replaced_per_recipient(): void
    {
        Mail::fake();
        // La file est synchrone en test : sans ce gel, l'envoi partirait
        // pendant la requete et le job serait deja passe.
        Queue::fake();
        $membre = User::factory()->create(['username' => 'camille', 'email_optout' => false]);
        $campagne = $this->brouillon(['audience' => 'selected', 'user_ids' => [$membre->id]]);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/campagnes/'.$campagne->id.'/envoi')
            ->assertStatus(200);

        (new \App\Jobs\SendCampaign($campagne->id))->handle();

        Mail::assertSent(CampaignMessage::class, function ($mail) {
            return $mail->hasTo('camille@example.com') === false
                && str_contains($mail->bodyHtml, 'camille')
                && ! str_contains($mail->bodyHtml, '{{pseudo}}');
        });
    }

    public function test_a_sent_campaign_cannot_be_sent_twice(): void
    {
        Queue::fake();
        User::factory()->count(2)->create();
        $campagne = $this->brouillon();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/campagnes/'.$campagne->id.'/envoi')->assertStatus(200);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/campagnes/'.$campagne->id.'/envoi')->assertStatus(422);
    }

    public function test_a_sent_campaign_cannot_be_edited(): void
    {
        $campagne = $this->brouillon(['status' => Campaign::STATUS_SENT]);

        $this->actingAs($this->admin(), 'sanctum')
            ->putJson('/api/admin/campagnes/'.$campagne->id, [
                'subject' => 'Modifié après coup',
                'body' => '<p>Non</p>',
                'purpose' => 'announcement',
                'audience' => 'all',
            ])
            ->assertStatus(422);
    }

    public function test_a_selection_that_reaches_nobody_is_refused(): void
    {
        $campagne = $this->brouillon(['audience' => 'segment', 'segment' => 'authors']);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/campagnes/'.$campagne->id.'/envoi')
            ->assertStatus(422);
    }

    public function test_unsubscribing_works_without_an_account(): void
    {
        $membre = User::factory()->create();
        $membre->forceFill(['email_token' => 'jeton-de-test-1234'])->save();

        $this->postJson('/api/campagnes/desinscription/jeton-de-test-1234')
            ->assertStatus(200);

        $this->assertTrue($membre->fresh()->email_optout);
    }

    public function test_an_unknown_unsubscribe_token_is_refused(): void
    {
        $this->postJson('/api/campagnes/desinscription/inconnu')->assertStatus(404);
    }

    public function test_somebody_who_opted_out_between_queue_and_send_is_skipped(): void
    {
        Mail::fake();
        Queue::fake();
        $membre = User::factory()->create();
        $campagne = $this->brouillon(['audience' => 'selected', 'user_ids' => [$membre->id]]);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/campagnes/'.$campagne->id.'/envoi')->assertStatus(200);

        // Le membre se desabonne pendant que l'envoi attend dans la file.
        $membre->forceFill(['email_optout' => true])->save();
        (new \App\Jobs\SendCampaign($campagne->id))->handle();

        Mail::assertNothingSent();
    }
}
