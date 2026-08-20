<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SiteIdentityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_a_visitor_reads_the_site_identity_without_an_account(): void
    {
        $this->getJson('/api/site')
            ->assertStatus(200)
            ->assertJsonPath('data.identity.footer', '© 2026 YOWL — LONG Corp')
            ->assertJsonPath('data.community.name', 'YOWL Community');
    }

    public function test_the_public_payload_leaves_out_private_settings(): void
    {
        $donnees = $this->getJson('/api/site')->json('data');

        $this->assertArrayNotHasKey('moderation', $donnees);
        $this->assertArrayNotHasKey('registration', $donnees);
        $this->assertArrayHasKey('identity', $donnees);
    }

    public function test_an_administrator_changes_the_footer_line(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson('/api/admin/settings', [
                'settings' => ['identity.footer' => '© 2027 YOWL — Une autre société'],
            ])
            ->assertStatus(200);

        $this->getJson('/api/site')
            ->assertJsonPath('data.identity.footer', '© 2027 YOWL — Une autre société');
    }

    public function test_a_member_cannot_change_the_settings(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->patchJson('/api/admin/settings', [
                'settings' => ['identity.footer' => 'Détourné'],
            ])
            ->assertStatus(403);
    }

    public function test_an_administrator_uploads_a_logo(): void
    {
        Storage::fake(config('filesystems.media'));

        $reponse = $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/settings/image', [
                'key' => 'identity.logo',
                'image' => UploadedFile::fake()->image('logo.png', 512, 512),
            ])
            ->assertStatus(200);

        $chemin = $reponse->json('data.path');
        $this->assertNotEmpty($chemin);
        Storage::disk(config('filesystems.media'))->assertExists($chemin);

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson('/api/admin/settings', ['settings' => ['identity.logo' => $chemin]])
            ->assertStatus(200);

        $this->getJson('/api/site')->assertJsonPath('data.identity.logo', $chemin);
    }

    public function test_an_image_is_refused_for_a_setting_that_is_not_one(): void
    {
        Storage::fake(config('filesystems.media'));

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/admin/settings/image', [
                'key' => 'identity.footer',
                'image' => UploadedFile::fake()->image('logo.png'),
            ])
            ->assertStatus(422);
    }

    public function test_the_seo_description_is_capped_at_the_length_engines_keep(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson('/api/admin/settings', [
                'settings' => ['seo.description' => str_repeat('a', 161)],
            ])
            ->assertStatus(422);
    }

    public function test_indexing_can_be_turned_off(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson('/api/admin/settings', ['settings' => ['seo.indexable' => false]])
            ->assertStatus(200);

        $this->assertFalse(Settings::get('seo.indexable'));
        $this->getJson('/api/site')->assertJsonPath('data.seo.indexable', false);
    }
}
