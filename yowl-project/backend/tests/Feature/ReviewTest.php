<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_list_published_reviews(): void
    {
        Review::factory()->count(3)->create();
        Review::factory()->unpublished()->create();

        $response = $this->getJson('/api/reviews');

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertCount(3, $response->json('data.data'));
    }

    public function test_unpublished_reviews_stay_visible_to_their_author(): void
    {
        $author = User::factory()->create();
        Review::factory()->unpublished()->for($author)->create();

        $response = $this->actingAs($author, 'sanctum')->getJson('/api/reviews');

        $this->assertCount(1, $response->json('data.data'));
    }

    public function test_authenticated_users_can_create_a_review_with_tags(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/reviews', [
            'content' => 'A great piece of content worth sharing.',
            'link' => 'https://example.com',
            'tags' => ['Gaming', 'tech'],
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.user.id', $user->id);
        $this->assertEqualsCanonicalizing(
            ['gaming', 'tech'],
            collect($response->json('data.tags'))->pluck('name')->all()
        );
        // Public payload must not leak the author's email
        $this->assertArrayNotHasKey('email', $response->json('data.user'));
    }

    public function test_guests_cannot_create_reviews(): void
    {
        $this->postJson('/api/reviews', ['content' => 'Nope'])->assertStatus(401);
    }

    public function test_only_the_owner_can_update_or_delete_a_review(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $review = Review::factory()->for($owner)->create();

        $this->actingAs($intruder, 'sanctum')
            ->postJson("/api/reviews/{$review->id}", ['content' => 'Hacked'])
            ->assertStatus(403);

        $this->actingAs($intruder, 'sanctum')
            ->deleteJson("/api/reviews/{$review->id}")
            ->assertStatus(403);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/reviews/{$review->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_reactions_toggle_and_expose_the_user_reaction(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        $like = $this->actingAs($user, 'sanctum')
            ->postJson("/api/reviews/{$review->id}/react", ['reaction' => 'like']);
        $like->assertStatus(200);
        $like->assertJson(['nb_like' => 1, 'nb_dislike' => 0, 'user_reaction' => 'like']);

        // Switching to dislike replaces the previous reaction
        $dislike = $this->actingAs($user, 'sanctum')
            ->postJson("/api/reviews/{$review->id}/react", ['reaction' => 'dislike']);
        $dislike->assertJson(['nb_like' => 0, 'nb_dislike' => 1, 'user_reaction' => 'dislike']);

        // Reacting twice with the same value removes it
        $removed = $this->actingAs($user, 'sanctum')
            ->postJson("/api/reviews/{$review->id}/react", ['reaction' => 'dislike']);
        $removed->assertJson(['nb_like' => 0, 'nb_dislike' => 0, 'user_reaction' => null]);
    }

    public function test_review_listing_does_not_leak_author_private_fields(): void
    {
        Review::factory()->create();

        $user = $this->getJson('/api/reviews')->json('data.data.0.user');

        $this->assertArrayNotHasKey('email', $user);
        $this->assertArrayNotHasKey('birthdate', $user);
    }
}
