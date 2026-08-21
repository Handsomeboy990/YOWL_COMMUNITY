<?php

namespace Tests\Feature;

use App\Models\PageVisit;
use App\Models\User;
use App\Support\Audience;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * La mesure d'audience, et surtout ce qu'elle refuse d'enregistrer.
 */
class AudienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_path_is_reduced_to_a_known_route_pattern(): void
    {
        $cas = [
            // Ce qui compte le plus : un pseudo dans l'URL dirait quel profil
            // a ete consulte, et par qui si on recoupait avec autre chose.
            '/membres/handsomeboy' => '/membres/:username',
            '/reviews/1234' => '/reviews/:id',
            '/reviews/1234/2' => '/reviews/:id/:page',
            '/sujets/jeuxvideo' => '/sujets/:name',
            '/feed' => '/feed',
            '/feed/3' => '/feed/:page',
            '/' => '/',
            '/about' => '/about',
            // La requete porte souvent le terme cherche, le fragment jamais rien.
            '/feed?q=un+terme+intime' => '/feed',
            '/about#section' => '/about',
            // Inconnu : agrege, pas rejete, sinon la mesure a des trous.
            '/chemin/inconnu/profond' => Audience::AUTRE,
        ];

        foreach ($cas as $brut => $attendu) {
            $this->assertSame($attendu, Audience::normaliserChemin($brut), 'pour '.$brut);
        }
    }

    public function test_a_referrer_is_reduced_to_its_host(): void
    {
        config(['app.frontend_url' => 'https://my-yowl.vercel.app']);

        // Le chemin d'un moteur de recherche porte la requete tapee.
        $this->assertSame('google.com', Audience::normaliserProvenance('https://www.google.com/search?q=avis+film'));
        $this->assertSame('t.co', Audience::normaliserProvenance('https://t.co/abc123'));

        // Une navigation interne n'est pas une provenance.
        $this->assertNull(Audience::normaliserProvenance('https://my-yowl.vercel.app/feed'));
        $this->assertNull(Audience::normaliserProvenance(null));
        $this->assertNull(Audience::normaliserProvenance('pas-une-url'));
    }

    public function test_a_visit_is_recorded_without_anything_personal(): void
    {
        $this->postJson('/api/visite', [
            'path' => '/membres/handsomeboy?onglet=avis',
            'referrer' => 'https://www.google.com/search?q=quelque+chose',
        ], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0) AppleWebKit/605.1.15',
            'HTTP_REFERER' => 'https://www.google.com/search?q=quelque+chose',
        ])->assertOk();

        $visite = PageVisit::sole();

        $this->assertSame('/membres/:username', $visite->path);
        $this->assertSame('google.com', $visite->referrer_host);
        $this->assertSame('mobile', $visite->device);
        $this->assertFalse($visite->is_member);

        // La table ne porte aucune colonne rattachable a une personne.
        $colonnes = array_keys($visite->getAttributes());
        foreach (['user_id', 'ip', 'ip_address', 'session_id', 'fingerprint'] as $interdite) {
            $this->assertNotContains($interdite, $colonnes);
        }
    }

    public function test_a_tablet_is_not_counted_as_a_phone(): void
    {
        // L'agent des tablettes Android contient « Mobile » : teste dans le
        // mauvais ordre, toutes les tablettes finissaient en telephones.
        $agents = [
            'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X)' => 'tablet',
            'Mozilla/5.0 (Linux; Android 13; SM-X200) AppleWebKit/537.36' => 'tablet',
            'Mozilla/5.0 (Linux; Android 13; Pixel 7 Mobile) AppleWebKit/537.36' => 'mobile',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)' => 'desktop',
        ];

        foreach ($agents as $agent => $attendu) {
            PageVisit::query()->delete();
            $this->postJson('/api/visite', ['path' => '/feed'], ['HTTP_USER_AGENT' => $agent])->assertOk();
            $this->assertSame($attendu, PageVisit::sole()->device, 'pour '.$agent);
        }
    }

    public function test_a_crawler_is_answered_but_not_counted(): void
    {
        $this->postJson('/api/visite', ['path' => '/feed'], [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        ])->assertOk();

        $this->assertSame(0, PageVisit::count());
    }

    public function test_a_signed_in_member_is_counted_as_one_without_being_named(): void
    {
        Role::findOrCreate('client', 'web');
        $membre = User::factory()->create();

        $this->actingAs($membre, 'sanctum')
            ->postJson('/api/visite', ['path' => '/feed'], ['HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh)'])
            ->assertOk();

        $visite = PageVisit::sole();
        $this->assertTrue($visite->is_member);
        $this->assertArrayNotHasKey('user_id', $visite->getAttributes());
    }

    public function test_the_dashboard_is_closed_to_anyone_but_an_administrator(): void
    {
        Role::findOrCreate('client', 'web');
        Role::findOrCreate('admin', 'web');

        $this->getJson('/api/admin/analytique')->assertUnauthorized();

        $membre = User::factory()->create();
        $membre->assignRole('client');
        $this->actingAs($membre, 'sanctum')->getJson('/api/admin/analytique')->assertForbidden();
    }

    public function test_the_dashboard_reports_days_without_a_visit_as_zero(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        PageVisit::create(['path' => '/feed', 'device' => 'mobile', 'is_member' => false, 'visited_at' => now()]);
        PageVisit::create(['path' => '/feed', 'device' => 'mobile', 'is_member' => true, 'visited_at' => now()]);
        PageVisit::create(['path' => '/about', 'device' => 'desktop', 'is_member' => false, 'visited_at' => now()]);

        $reponse = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/analytique?jours=7')->assertOk();

        $donnees = $reponse->json('data');

        $this->assertSame(7, $donnees['fenetre']);
        $this->assertSame(3, $donnees['total']);
        // Une courbe qui saute les jours creux dessine une frequentation plus
        // reguliere qu'elle ne l'est : les sept jours sont toujours rendus.
        $this->assertCount(7, $donnees['par_jour']);

        $aujourdhui = end($donnees['par_jour']);
        $this->assertSame(1, $aujourdhui['membres']);
        $this->assertSame(2, $aujourdhui['visiteurs']);

        $this->assertSame('/feed', $donnees['pages'][0]['page']);
        $this->assertSame(2, $donnees['pages'][0]['visites']);
        $this->assertSame(3, $donnees['provenances']['direct']);
    }

    public function test_an_unknown_window_falls_back_instead_of_trusting_the_query(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        foreach (['9999', 'trente', '-1', ''] as $valeur) {
            $this->actingAs($admin, 'sanctum')
                ->getJson('/api/admin/analytique?jours='.$valeur)
                ->assertOk()
                ->assertJsonPath('data.fenetre', 30);
        }
    }
}
