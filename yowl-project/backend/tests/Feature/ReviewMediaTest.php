<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Upload and input hardening of the review endpoints.
 */
class ReviewMediaTest extends TestCase
{
    use RefreshDatabase;

    private function image(string $name = 'photo.jpg', int $kilobytes = 100): UploadedFile
    {
        return UploadedFile::fake()->create($name, $kilobytes, 'image/jpeg');
    }

    public function test_an_image_within_the_limits_is_stored(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/reviews', [
            'content' => 'Une review avec une image.',
            'medias' => [$this->image()],
        ]);

        $response->assertStatus(201);
        $stored = $response->json('data.medias');
        $this->assertCount(1, $stored);
        Storage::disk('public')->assertExists($stored[0]);
    }

    public function test_an_oversized_image_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/reviews', [
            'content' => 'Une review trop lourde.',
            'medias' => [$this->image('huge.jpg', 6000)],
        ])->assertStatus(422);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_a_non_image_file_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/reviews', [
            'content' => 'Une review avec un exécutable.',
            'medias' => [UploadedFile::fake()->create('payload.php', 10, 'application/x-httpd-php')],
        ])->assertStatus(422);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_an_svg_is_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/reviews', [
            'content' => 'Une review avec un SVG.',
            'medias' => [UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')],
        ])->assertStatus(422);
    }

    public function test_more_images_than_allowed_are_rejected(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/reviews', [
            'content' => 'Une review bavarde en images.',
            'medias' => array_map(fn ($i) => $this->image("photo{$i}.jpg"), range(1, 6)),
        ])->assertStatus(422);
    }

    public function test_a_non_http_link_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/reviews', [
            'content' => 'Une review au lien piégé.',
            'link' => 'javascript:alert(document.cookie)',
        ])->assertStatus(422);
    }

    public function test_an_unbounded_content_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/reviews', [
            'content' => str_repeat('a', 5001),
        ])->assertStatus(422);
    }

    public function test_updating_cannot_inject_a_foreign_media_path(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $review = Review::factory()->for($user)->create(['medias' => ['reviews/mine.jpg']]);

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/reviews/{$review->id}", [
            'content' => 'Contenu mis à jour.',
            'existingMedias' => ['reviews/mine.jpg', '../../.env', 'reviews/someone-else.jpg'],
        ]);

        $response->assertStatus(200);
        $this->assertSame(['reviews/mine.jpg'], $review->fresh()->medias);
    }

    public function test_removed_medias_are_deleted_from_the_disk(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Storage::disk('public')->put('reviews/old.jpg', 'binaire');
        $review = Review::factory()->for($user)->create(['medias' => ['reviews/old.jpg']]);

        $this->actingAs($user, 'sanctum')->postJson("/api/reviews/{$review->id}", [
            'content' => 'Sans image désormais.',
            'existingMedias' => [],
        ])->assertStatus(200);

        Storage::disk('public')->assertMissing('reviews/old.jpg');
        $this->assertSame([], $review->fresh()->medias);
    }

    public function test_a_failed_error_response_does_not_leak_internals(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson("/api/reviews/{$review->id}", [
            'content' => 'Tentative sur la review d\'un autre.',
        ]);

        $response->assertStatus(403);
        $this->assertArrayNotHasKey('error', $response->json());
    }
}
