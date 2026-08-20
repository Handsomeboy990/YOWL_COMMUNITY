<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\Tag;
use App\Support\Settings;
use Database\Seeders\LegalPageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_robots_allows_crawling_and_points_at_the_sitemap(): void
    {
        $corps = $this->get('/robots.txt')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/plain; charset=utf-8')
            ->getContent();

        $this->assertStringContainsString('Allow: /', $corps);
        $this->assertStringContainsString('Sitemap:', $corps);
        // Rien de ce qui vit derriere une connexion ne doit etre propose.
        $this->assertStringContainsString('Disallow: /admin', $corps);
        $this->assertStringContainsString('Disallow: /user/', $corps);
    }

    public function test_turning_indexing_off_closes_the_door(): void
    {
        Settings::set('seo.indexable', false);

        $corps = $this->get('/robots.txt')->assertStatus(200)->getContent();
        $this->assertStringContainsString('Disallow: /', $corps);
        $this->assertStringNotContainsString('Allow: /', $corps);

        $this->get('/sitemap.xml')->assertStatus(404);
    }

    public function test_the_sitemap_lists_published_reviews_and_topics(): void
    {
        $this->seed(LegalPageSeeder::class);

        $tag = Tag::create(['name' => 'cinema']);
        $visible = Review::factory()->create();
        $visible->tags()->sync([$tag->id]);

        $masque = Review::factory()->create();
        $masque->forceFill(['is_published' => false])->save();

        $xml = $this->get('/sitemap.xml')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/xml; charset=utf-8')
            ->getContent();

        $this->assertStringContainsString('/reviews/'.$visible->id.'<', $xml);
        $this->assertStringNotContainsString('/reviews/'.$masque->id.'<', $xml);
        $this->assertStringContainsString('/sujets/cinema', $xml);
        $this->assertStringContainsString('/charte', $xml);
        $this->assertStringContainsString('/about', $xml);
    }

    public function test_the_sitemap_is_valid_xml(): void
    {
        $xml = $this->get('/sitemap.xml')->getContent();

        $document = simplexml_load_string($xml);
        $this->assertNotFalse($document, 'Le plan de site ne se lit pas comme du XML.');
        $this->assertGreaterThan(0, $document->count());
    }
}
