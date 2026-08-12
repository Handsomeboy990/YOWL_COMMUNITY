<?php

namespace Tests\Feature;

use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuggestionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_a_visitor_can_send_a_suggestion(): void
    {
        $this->postJson('/api/suggestions', [
            'name' => 'Jean Dupont',
            'email' => 'jean@example.com',
            'message' => 'Ajoutez un mode sombre, ce serait parfait.',
        ])->assertStatus(201);

        $this->assertDatabaseHas('suggestions', [
            'email' => 'jean@example.com',
            'status' => Suggestion::STATUS_NEW,
            'user_id' => null,
        ]);
    }

    public function test_an_authenticated_suggestion_keeps_its_author(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member, 'sanctum')->postJson('/api/suggestions', [
            'message' => 'Un fil dédié aux tags suivis serait utile.',
        ])->assertStatus(201);

        $this->assertDatabaseHas('suggestions', ['user_id' => $member->id]);
    }

    public function test_an_empty_message_is_rejected(): void
    {
        $this->postJson('/api/suggestions', ['message' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    public function test_an_oversized_message_is_rejected(): void
    {
        $this->postJson('/api/suggestions', ['message' => str_repeat('a', 2001)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    public function test_a_malformed_email_is_rejected(): void
    {
        $this->postJson('/api/suggestions', [
            'email' => 'pas-un-email',
            'message' => 'Message valide mais adresse invalide.',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_regular_members_cannot_read_the_suggestions(): void
    {
        $member = User::factory()->create();
        $member->assignRole('client');

        $this->actingAs($member, 'sanctum')->getJson('/api/admin/suggestions')->assertStatus(403);
    }

    public function test_an_admin_lists_the_suggestions_with_the_new_count(): void
    {
        $this->postJson('/api/suggestions', ['message' => 'Première idée à étudier.']);

        $this->actingAs($this->admin(), 'sanctum')->getJson('/api/admin/suggestions')
            ->assertStatus(200)
            ->assertJsonPath('new_count', 1)
            ->assertJsonCount(1, 'data.data');
    }

    public function test_an_admin_moves_a_suggestion_along_its_lifecycle(): void
    {
        $this->postJson('/api/suggestions', ['message' => 'Idée à archiver.']);
        $suggestion = Suggestion::first();
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/suggestions/{$suggestion->id}", ['status' => 'archived'])
            ->assertStatus(200);

        $this->assertDatabaseHas('suggestions', [
            'id' => $suggestion->id,
            'status' => Suggestion::STATUS_ARCHIVED,
            'handled_by' => $admin->id,
        ]);
    }

    public function test_an_unknown_status_is_rejected(): void
    {
        $this->postJson('/api/suggestions', ['message' => 'Idée quelconque.']);
        $suggestion = Suggestion::first();

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/admin/suggestions/{$suggestion->id}", ['status' => 'deleted'])
            ->assertStatus(422);
    }
}
