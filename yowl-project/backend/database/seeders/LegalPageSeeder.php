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

    public function run(): void
    {
        foreach (self::PAGES as $slug => $title) {
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
        }
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
