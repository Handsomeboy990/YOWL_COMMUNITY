<?php

namespace Tests\Feature;

use App\Models\LegalPage;
use App\Models\User;
use App\Support\RichText;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LegalPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin;
    }

    public function test_a_visitor_reads_a_published_page(): void
    {
        LegalPage::create([
            'slug' => 'charte', 'title' => 'Charte',
            'body' => '<p>Le texte public.</p>', 'published_at' => now(),
        ]);

        $this->getJson('/api/legal/charte')
            ->assertStatus(200)
            ->assertJsonPath('data.body', '<p>Le texte public.</p>');
    }

    public function test_an_unpublished_page_is_not_readable(): void
    {
        LegalPage::create(['slug' => 'charte', 'title' => 'Charte', 'draft_body' => '<p>Pas prêt.</p>']);

        $this->getJson('/api/legal/charte')->assertStatus(404);
    }

    public function test_a_draft_never_reaches_the_public_page(): void
    {
        $admin = $this->admin();
        LegalPage::create([
            'slug' => 'charte', 'title' => 'Charte',
            'body' => '<p>Version en ligne.</p>', 'published_at' => now(),
        ]);

        $this->actingAs($admin, 'sanctum')->putJson('/api/admin/legal/charte', [
            'title' => 'Charte',
            'content' => '<p>Version en cours de rédaction.</p>',
            'action' => 'draft',
        ])->assertStatus(200);

        // Le public voit toujours la version publiee.
        $this->getJson('/api/legal/charte')->assertJsonPath('data.body', '<p>Version en ligne.</p>');
    }

    public function test_publishing_puts_the_draft_online(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')->putJson('/api/admin/legal/charte', [
            'title' => 'Charte',
            'content' => '<p>Nouvelle version.</p>',
            'action' => 'publish',
        ])->assertStatus(200);

        $this->getJson('/api/legal/charte')->assertJsonPath('data.body', '<p>Nouvelle version.</p>');
    }

    public function test_a_regular_member_cannot_edit(): void
    {
        $this->admin();
        $membre = User::factory()->create();

        $this->actingAs($membre, 'sanctum')->putJson('/api/admin/legal/charte', [
            'title' => 'Charte', 'content' => '<p>x</p>', 'action' => 'publish',
        ])->assertStatus(403);
    }

    public function test_an_unknown_page_is_refused(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')->putJson('/api/admin/legal/inventee', [
            'title' => 'Inventée', 'content' => '<p>x</p>', 'action' => 'publish',
        ])->assertStatus(404);
    }

    public function test_a_script_never_survives_the_editor(): void
    {
        $sale = '<p>Bonjour</p><script>alert(1)</script><p onclick="alert(2)">Suite</p>';
        $propre = RichText::clean($sale);

        $this->assertStringNotContainsString('<script', $propre);
        $this->assertStringNotContainsString('onclick', $propre);
        $this->assertStringContainsString('Bonjour', $propre);
        $this->assertStringContainsString('Suite', $propre);
    }

    public function test_a_javascript_address_is_stripped_from_a_link(): void
    {
        $propre = RichText::clean('<p><a href="javascript:alert(1)">Clique</a></p>');

        $this->assertStringNotContainsString('javascript:', $propre);
        // Le texte reste, seule l'adresse dangereuse part.
        $this->assertStringContainsString('Clique', $propre);
    }

    public function test_an_outgoing_link_cannot_take_over_the_page(): void
    {
        $propre = RichText::clean('<p><a href="https://exemple.fr">Exemple</a></p>');

        $this->assertStringContainsString('rel="noopener noreferrer"', $propre);
        $this->assertStringContainsString('target="_blank"', $propre);
    }

    public function test_a_video_from_an_unknown_host_is_removed(): void
    {
        $propre = RichText::clean('<iframe src="https://site-inconnu.example/x"></iframe>');

        $this->assertStringNotContainsString('iframe', $propre);
    }

    public function test_a_youtube_video_survives_but_stays_sandboxed(): void
    {
        $propre = RichText::clean('<iframe src="https://www.youtube.com/embed/abc"></iframe>');

        $this->assertStringContainsString('youtube.com/embed/abc', $propre);
        $this->assertStringContainsString('sandbox=', $propre);
        // allow-same-origin avec allow-scripts laisserait la page s'echapper.
        $this->assertStringNotContainsString('allow-same-origin', $propre);
    }

    public function test_an_image_survives(): void
    {
        $propre = RichText::clean('<p><img src="/storage/legal/x.jpg" alt="Un visuel"></p>');

        $this->assertStringContainsString('src="/storage/legal/x.jpg"', $propre);
        $this->assertStringContainsString('alt="Un visuel"', $propre);
    }

    public function test_the_console_lists_every_page_with_its_state(): void
    {
        $admin = $this->admin();

        $pages = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/legal')->json('data');

        $this->assertCount(count(LegalPage::SLUGS), $pages);
        $this->assertContains('charte', collect($pages)->pluck('slug')->all());
    }
}
