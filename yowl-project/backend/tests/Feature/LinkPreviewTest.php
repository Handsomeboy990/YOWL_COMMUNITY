<?php

namespace Tests\Feature;

use App\Jobs\FetchLinkPreview;
use App\Models\Review;
use App\Models\User;
use App\Services\LinkPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LinkPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_publishing_with_a_link_queues_the_preview(): void
    {
        Queue::fake();
        $author = User::factory()->create();

        $this->actingAs($author, 'sanctum')->postJson('/api/reviews', [
            'content' => 'Un article qui vaut le détour.',
            'link' => 'https://example.com/article',
        ])->assertStatus(201);

        Queue::assertPushed(FetchLinkPreview::class);
    }

    public function test_publishing_without_a_link_queues_nothing(): void
    {
        Queue::fake();
        $author = User::factory()->create();

        $this->actingAs($author, 'sanctum')->postJson('/api/reviews', [
            'content' => 'Un avis sans lien.',
        ])->assertStatus(201);

        Queue::assertNotPushed(FetchLinkPreview::class);
    }

    public function test_the_metadata_of_a_page_is_extracted(): void
    {
        Http::fake([
            'example.com/*' => Http::response(<<<'HTML'
                <html><head>
                <meta property="og:title" content="Le titre de la page">
                <meta property="og:description" content="Une description  sur   plusieurs espaces.">
                <meta property="og:image" content="/visuel.jpg">
                <meta property="og:site_name" content="Example">
                <title>Titre de repli</title>
                </head><body>x</body></html>
                HTML, 200, ['Content-Type' => 'text/html']),
        ]);

        $preview = app(LinkPreviewService::class)->fetch('https://example.com/article');

        $this->assertSame('Le titre de la page', $preview['title']);
        $this->assertSame('Une description sur plusieurs espaces.', $preview['description']);
        // L'image relative devient absolue.
        $this->assertSame('https://example.com/visuel.jpg', $preview['image']);
        $this->assertSame('Example', $preview['site_name']);
    }

    public function test_a_page_without_a_title_produces_no_card(): void
    {
        Http::fake([
            'example.com/*' => Http::response('<html><body>rien</body></html>', 200, ['Content-Type' => 'text/html']),
        ]);

        $this->assertNull(app(LinkPreviewService::class)->fetch('https://example.com/vide'));
    }

    public function test_a_private_address_is_never_fetched(): void
    {
        Http::fake();

        foreach ([
            'http://localhost/admin',
            'http://127.0.0.1:8000/api/health',
            'http://192.168.1.10/',
            'http://169.254.169.254/latest/meta-data/',
            'file:///etc/passwd',
        ] as $url) {
            $this->assertNull(
                app(LinkPreviewService::class)->fetch($url),
                $url.' ne doit jamais être appelé'
            );
        }

        Http::assertNothingSent();
    }

    public function test_the_job_stores_the_preview_on_the_review(): void
    {
        Http::fake([
            '*' => Http::response(
                '<html><head><meta property="og:title" content="Un titre"></head></html>',
                200,
                ['Content-Type' => 'text/html']
            ),
        ]);

        $review = Review::factory()->create(['link' => 'https://example.com/page']);

        (new FetchLinkPreview($review->id))->handle(app(LinkPreviewService::class));

        $review->refresh();
        $this->assertSame('Un titre', $review->link_preview['title']);
        $this->assertNotNull($review->link_preview_at);
    }
}
