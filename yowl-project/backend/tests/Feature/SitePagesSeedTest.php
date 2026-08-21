<?php

namespace Tests\Feature;

use App\Models\LegalPage;
use Database\Seeders\LegalPageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The six pages the footer links to must exist, and must survive a restart.
 *
 * A fresh deployment migrated its tables and seeded nothing, so À propos, the
 * FAQ, the charter, the privacy policy, the terms and the legal notices all
 * answered 404 while the site's own footer linked to them.
 *
 * Seeding them at every container start fixes that, but only if seeding never
 * overwrites: the first version used updateOrCreate, which would have wiped an
 * administrator's edits on every restart, silently and for good.
 */
class SitePagesSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_six_pages_on_an_empty_database(): void
    {
        $this->assertSame(0, LegalPage::count());

        $this->artisan('yowl:seed-pages')->assertExitCode(0);

        $this->assertSame(6, LegalPage::count());
        foreach (array_keys(LegalPage::SLUGS) as $slug) {
            $this->assertDatabaseHas('legal_pages', ['slug' => $slug]);
        }
    }

    public function test_the_pages_are_readable_over_the_api(): void
    {
        $this->artisan('yowl:seed-pages');

        foreach (['a-propos', 'faq', 'charte', 'mentions-legales'] as $slug) {
            $this->getJson("/api/legal/{$slug}")
                ->assertStatus(200)
                ->assertJsonPath('data.slug', $slug);
        }
    }

    public function test_a_restart_never_overwrites_an_edited_page(): void
    {
        $this->artisan('yowl:seed-pages');

        LegalPage::where('slug', 'charte')->update([
            'body' => '<p>Texte réécrit par l\'administration.</p>',
        ]);

        // Le redémarrage suivant du conteneur.
        $this->artisan('yowl:seed-pages')->assertExitCode(0);

        $this->assertSame(
            '<p>Texte réécrit par l\'administration.</p>',
            LegalPage::where('slug', 'charte')->first()->body,
            'Le seeder a écrasé une page éditée depuis la console.'
        );
    }

    public function test_reset_restores_the_shipped_text_when_asked(): void
    {
        $this->artisan('yowl:seed-pages');
        LegalPage::where('slug', 'charte')->update(['body' => '<p>Écrasé.</p>']);

        $this->artisan('yowl:seed-pages', ['--reset' => true, '--no-interaction' => true])
            ->assertExitCode(0);

        $this->assertStringContainsString(
            'charte',
            strtolower(LegalPage::where('slug', 'charte')->first()->body)
        );
        $this->assertStringNotContainsString('Écrasé', LegalPage::where('slug', 'charte')->first()->body);
    }

    public function test_a_missing_page_is_added_without_touching_the_others(): void
    {
        $this->artisan('yowl:seed-pages');
        LegalPage::where('slug', 'faq')->delete();
        LegalPage::where('slug', 'charte')->update(['body' => '<p>Édité.</p>']);

        $this->artisan('yowl:seed-pages')->assertExitCode(0);

        $this->assertSame(6, LegalPage::count());
        $this->assertSame('<p>Édité.</p>', LegalPage::where('slug', 'charte')->first()->body);
    }
}
