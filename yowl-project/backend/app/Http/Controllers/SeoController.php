<?php

namespace App\Http\Controllers;

use App\Models\LegalPage;
use App\Models\Review;
use App\Models\Tag;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;

class SeoController extends Controller
{
    /**
     * robots.txt, built from the setting rather than shipped as a file.
     *
     * A static file cannot know that indexing was turned off in the console,
     * and a site that is not ready to be found needs that switch to actually
     * do something.
     */
    public function robots()
    {
        $frontend = rtrim(config('app.frontend_url'), '/');

        if (! Settings::get('seo.indexable', true)) {
            $corps = "User-agent: *\nDisallow: /\n";
        } else {
            $corps = implode("\n", [
                'User-agent: *',
                'Allow: /',
                '',
                '# Rien à indexer derrière une connexion',
                'Disallow: /admin',
                'Disallow: /user/',
                'Disallow: /login',
                'Disallow: /signup',
                'Disallow: /password-reset',
                'Disallow: /desinscription/',
                '',
                'Sitemap: '.$frontend.'/sitemap.xml',
                '',
            ]);
        }

        return response($corps, 200, ['Content-Type' => 'text/plain; charset=utf-8']);
    }

    /**
     * The sitemap, listing what a visitor can actually reach without an account.
     *
     * Cached for an hour: it walks every published review, and a crawler that
     * comes back twice in a minute should not cost two full scans.
     */
    public function sitemap()
    {
        if (! Settings::get('seo.indexable', true)) {
            return response('', 404);
        }

        $xml = Cache::remember('yowl.sitemap', now()->addHour(), fn () => $this->buildSitemap());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    private function buildSitemap(): string
    {
        $base = rtrim(config('app.frontend_url'), '/');
        $entrees = [];

        // Les pages fixes, de la plus importante a la moins consultee.
        $entrees[] = ['loc' => $base.'/', 'priority' => '1.0', 'freq' => 'daily'];
        $entrees[] = ['loc' => $base.'/feed', 'priority' => '0.9', 'freq' => 'hourly'];
        $entrees[] = ['loc' => $base.'/sujets', 'priority' => '0.7', 'freq' => 'daily'];

        foreach (LegalPage::whereNotNull('published_at')->get() as $page) {
            $chemin = $page->slug === 'a-propos' ? '/about' : '/'.$page->slug;
            $entrees[] = [
                'loc' => $base.$chemin,
                'priority' => '0.4',
                'freq' => 'monthly',
                'lastmod' => $page->updated_at?->toAtomString(),
            ];
        }

        foreach (Tag::whereHas('reviews', fn ($q) => $q->where('is_published', true))->get() as $tag) {
            $entrees[] = [
                'loc' => $base.'/sujets/'.rawurlencode($tag->name),
                'priority' => '0.6',
                'freq' => 'daily',
            ];
        }

        Review::where('is_published', true)
            ->orderByDesc('created_at')
            ->limit(5000)
            ->get(['id', 'updated_at'])
            ->each(function ($review) use (&$entrees, $base) {
                $entrees[] = [
                    'loc' => $base.'/reviews/'.$review->id,
                    'priority' => '0.5',
                    'freq' => 'weekly',
                    'lastmod' => $review->updated_at?->toAtomString(),
                ];
            });

        $lignes = ['<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach ($entrees as $entree) {
            $lignes[] = '  <url>';
            $lignes[] = '    <loc>'.htmlspecialchars($entree['loc'], ENT_XML1).'</loc>';
            if (! empty($entree['lastmod'])) {
                $lignes[] = '    <lastmod>'.$entree['lastmod'].'</lastmod>';
            }
            $lignes[] = '    <changefreq>'.$entree['freq'].'</changefreq>';
            $lignes[] = '    <priority>'.$entree['priority'].'</priority>';
            $lignes[] = '  </url>';
        }

        $lignes[] = '</urlset>';

        return implode("\n", $lignes);
    }
}
