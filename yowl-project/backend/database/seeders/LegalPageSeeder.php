<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use App\Support\RichText;
use Illuminate\Database\Seeder;

class LegalPageSeeder extends Seeder
{
    /**
     * The four legal pages, written out in full.
     *
     * The text lives in HTML files next to this seeder rather than inside a
     * PHP string: it is long, it is meant to be edited by somebody who is not
     * a developer, and a diff on a heredoc of nine thousand characters is
     * unreadable. The administrator can then keep editing it from the console,
     * which is what the files seed rather than replace.
     */
    private const PAGES = [
        'a-propos' => 'À propos',
        'faq' => 'Foire aux questions',
        'charte' => 'Charte de la communauté',
        'confidentialite' => 'Politique de confidentialité',
        'conditions' => "Conditions d'utilisation",
        'mentions-legales' => 'Mentions légales',
    ];

    /**
     * Set by yowl:seed-pages --reset, and by nothing else.
     *
     * Overwriting a page an administrator has edited is a deliberate act, so
     * it takes a deliberate flag rather than a default.
     */
    public static bool $reinitialiser = false;

    /**
     * Create the pages that are missing, and touch nothing else.
     *
     * This runs at every container start, so that a fresh deployment has its
     * six pages rather than six 404 links in its own footer. It therefore
     * must never overwrite: an earlier version used updateOrCreate, which
     * would have wiped an administrator's edits on every restart, silently
     * and permanently.
     *
     * Resetting a page to the shipped text is a deliberate act, and it has
     * its own flag.
     */
    public function run(): void
    {
        $crees = 0;

        foreach (self::PAGES as $slug => $title) {
            $existante = LegalPage::where('slug', $slug)->first();

            if ($existante && ! self::$reinitialiser) {
                continue;
            }

            $body = $this->body($slug);

            LegalPage::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'body' => $body,
                    'draft_body' => $body,
                    'published_at' => now(),
                ]
            );

            $crees++;
        }

        $this->command?->info($crees.' page(s) du site créée(s).');
    }

    /**
     * Read a page and pass it through the same allow list as the editor.
     *
     * Seeding through RichText rather than around it means a tag added here by
     * mistake is caught at seed time, not the day somebody opens the page.
     */
    private function body(string $slug): string
    {
        $chemin = __DIR__.'/legal/'.$slug.'.html';

        if (! is_file($chemin)) {
            throw new \RuntimeException("Texte légal introuvable : {$chemin}");
        }

        return RichText::clean(file_get_contents($chemin));
    }
}
