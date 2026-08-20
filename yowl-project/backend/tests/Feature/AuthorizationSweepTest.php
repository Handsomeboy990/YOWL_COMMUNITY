<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One pass over every place where knowing an identifier could be enough.
 *
 * Each case is somebody acting on a resource that is not theirs. They are
 * grouped here rather than scattered across the feature tests because the
 * question is the same everywhere, and a new endpoint that forgets it should
 * fail in one obvious place.
 */
class AuthorizationSweepTest extends TestCase
{
    use RefreshDatabase;

    private User $moi;
    private User $autre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->moi = User::factory()->create();
        $this->autre = User::factory()->create();
    }

    public function test_a_member_cannot_edit_another_profile(): void
    {
        $this->actingAs($this->moi, 'sanctum')
            ->postJson('/api/users/'.$this->autre->id, ['username' => 'detourne'])
            ->assertStatus(403);

        $this->assertNotSame('detourne', $this->autre->fresh()->username);
    }

    public function test_a_member_cannot_delete_another_account(): void
    {
        $this->actingAs($this->moi, 'sanctum')
            ->deleteJson('/api/users/'.$this->autre->id)
            ->assertStatus(403);

        $this->assertNull($this->autre->fresh()->anonymized_at);
    }

    public function test_a_member_cannot_edit_another_review(): void
    {
        $avis = Review::factory()->create(['user_id' => $this->autre->id, 'content' => 'Le texte original.']);

        $this->actingAs($this->moi, 'sanctum')
            ->postJson('/api/reviews/'.$avis->id, ['content' => 'Réécrit par quelqu un d autre.'])
            ->assertStatus(403);

        $this->assertSame('Le texte original.', $avis->fresh()->content);
    }

    public function test_a_member_cannot_delete_another_review(): void
    {
        $avis = Review::factory()->create(['user_id' => $this->autre->id]);

        $this->actingAs($this->moi, 'sanctum')
            ->deleteJson('/api/reviews/'.$avis->id)
            ->assertStatus(403);

        $this->assertDatabaseHas('reviews', ['id' => $avis->id]);
    }

    public function test_the_export_holds_only_the_caller_data(): void
    {
        Review::factory()->create(['user_id' => $this->autre->id, 'content' => "N'appartient pas à l'appelant."]);

        $donnees = $this->actingAs($this->moi, 'sanctum')
            ->getJson('/api/mes-donnees/export')
            ->json();

        $this->assertSame($this->moi->username, $donnees['compte']['pseudo']);
        $this->assertSame([], $donnees['avis']);
    }

    public function test_the_password_route_acts_on_the_caller_alone(): void
    {
        $empreinteAutre = $this->autre->password;

        // La route ne prend aucun identifiant : il n'y a rien à détourner, et
        // une mauvaise saisie est refusée sans rien toucher.
        $this->actingAs($this->moi, 'sanctum')
            ->patchJson('/api/mot-de-passe', [
                'current_password' => 'ce n est pas le bon',
                'password' => 'Nouveau-Mot-2026',
                'password_confirmation' => 'Nouveau-Mot-2026',
            ])
            ->assertStatus(422);

        $this->assertSame($empreinteAutre, $this->autre->fresh()->password);
        $this->assertSame($this->moi->password, $this->moi->fresh()->password);
    }

    /**
     * @dataProvider routesAdministration
     */
    public function test_a_member_is_refused_on_every_administration_route(string $methode, string $chemin): void
    {
        $this->actingAs($this->moi, 'sanctum')
            ->json($methode, $chemin)
            ->assertStatus(403);
    }

    /**
     * @dataProvider routesAdministration
     */
    public function test_a_guest_is_refused_on_every_administration_route(string $methode, string $chemin): void
    {
        $this->json($methode, $chemin)->assertStatus(401);
    }

    public static function routesAdministration(): array
    {
        return [
            'statistiques' => ['GET', '/api/admin/stats'],
            'croissance' => ['GET', '/api/admin/croissance'],
            'export croissance' => ['GET', '/api/admin/croissance/export'],
            'membres' => ['GET', '/api/admin/users'],
            'reglages' => ['GET', '/api/admin/settings'],
            'ecriture reglages' => ['PATCH', '/api/admin/settings'],
            'roles' => ['GET', '/api/admin/roles'],
            'journal' => ['GET', '/api/admin/audit-log'],
            'contestations' => ['GET', '/api/admin/appeals'],
            'signalements' => ['GET', '/api/admin/reports'],
            'pages du site' => ['GET', '/api/admin/legal'],
            'campagnes' => ['GET', '/api/admin/campagnes'],
            'options campagnes' => ['GET', '/api/admin/campagnes/options'],
            'audience campagnes' => ['POST', '/api/admin/campagnes/audience'],
            'suggestions' => ['GET', '/api/admin/suggestions'],
        ];
    }

    public function test_a_member_cannot_send_a_campaign_they_do_not_own_either(): void
    {
        $campagne = Campaign::create([
            'subject' => 'Objet', 'body' => '<p>Corps</p>',
            'purpose' => 'announcement', 'audience' => 'all',
        ]);

        $this->actingAs($this->moi, 'sanctum')
            ->postJson('/api/admin/campagnes/'.$campagne->id.'/envoi')
            ->assertStatus(403);
    }

    public function test_the_public_site_payload_never_leaks_a_private_setting(): void
    {
        $donnees = $this->getJson('/api/site')->json('data');

        foreach (['registration', 'moderation', 'reviews', 'suggestions'] as $groupePrive) {
            $this->assertArrayNotHasKey($groupePrive, $donnees);
        }
    }
}
