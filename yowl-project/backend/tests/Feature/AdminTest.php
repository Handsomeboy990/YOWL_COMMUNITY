<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_regular_users_cannot_access_admin_endpoints(): void
    {
        $user = User::factory()->create();
        $user->assignRole('client');

        $this->actingAs($user, 'sanctum')->getJson('/api/admin/stats')->assertStatus(403);
    }

    public function test_admin_stats_include_tag_count(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')->getJson('/api/admin/stats');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => ['users', 'reviews', 'comments', 'tags', 'latest_reviews']]);
    }

    public function test_banning_a_user_revokes_all_their_tokens(): void
    {
        $member = User::factory()->create();
        $member->createToken('session-a');
        $member->createToken('session-b');

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/admin/users/{$member->id}/ban")
            ->assertStatus(200);

        $member->refresh();
        $this->assertFalse((bool) $member->is_active);
        $this->assertSame(0, $member->tokens()->count());
    }

    public function test_admins_cannot_ban_themselves(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/users/{$admin->id}/ban")
            ->assertStatus(403);
    }

    public function test_unpublished_reviews_disappear_from_the_public_feed(): void
    {
        $review = Review::factory()->create();

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/admin/reviews/{$review->id}/unpublish")
            ->assertStatus(200);

        $this->assertCount(0, $this->getJson('/api/reviews')->json('data.data'));

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/admin/reviews/{$review->id}/publish")
            ->assertStatus(200);

        $this->assertCount(1, $this->getJson('/api/reviews')->json('data.data'));
    }
}
