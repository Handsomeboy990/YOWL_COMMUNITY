<?php

namespace Tests\Feature;

use App\Models\Appeal;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppealTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function hiddenReview(User $author): Review
    {
        $review = Review::factory()->create(['user_id' => $author->id]);
        $review->forceFill(['is_published' => false])->save();

        return $review->fresh();
    }

    public function test_the_author_of_hidden_content_can_appeal(): void
    {
        $author = User::factory()->create();
        $review = $this->hiddenReview($author);

        $this->actingAs($author, 'sanctum')
            ->postJson('/api/appeals', [
                'type' => 'review',
                'id' => $review->id,
                'message' => 'Ce texte cite un article, il ne fait pas la promotion de son contenu.',
            ])
            ->assertStatus(201);

        $this->assertDatabaseCount('appeals', 1);
    }

    public function test_somebody_else_cannot_appeal_for_the_author(): void
    {
        $author = User::factory()->create();
        $stranger = User::factory()->create();
        $review = $this->hiddenReview($author);

        $this->actingAs($stranger, 'sanctum')
            ->postJson('/api/appeals', [
                'type' => 'review',
                'id' => $review->id,
                'message' => 'Je conteste a sa place, ce qui ne se fait pas.',
            ])
            ->assertStatus(403);
    }

    public function test_published_content_cannot_be_appealed(): void
    {
        $author = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $author->id]);

        $this->actingAs($author, 'sanctum')
            ->postJson('/api/appeals', [
                'type' => 'review',
                'id' => $review->id,
                'message' => 'Rien ne justifie cette contestation, le texte est en ligne.',
            ])
            ->assertStatus(422);
    }

    public function test_the_same_content_cannot_be_appealed_twice(): void
    {
        $author = User::factory()->create();
        $review = $this->hiddenReview($author);

        $payload = [
            'type' => 'review',
            'id' => $review->id,
            'message' => 'Premiere contestation, la seule qui doit etre enregistree.',
        ];

        $this->actingAs($author, 'sanctum')->postJson('/api/appeals', $payload)->assertStatus(201);

        // La seconde tentative renvoie la contestation deja deposee plutot
        // qu'une erreur : le membre veut savoir ou elle en est, pas apprendre
        // qu'il a mal cliqué.
        $this->actingAs($author, 'sanctum')->postJson('/api/appeals', $payload)->assertStatus(200);

        $this->assertDatabaseCount('appeals', 1);
    }

    public function test_granting_an_appeal_puts_the_content_back_online(): void
    {
        $author = User::factory()->create();
        $review = $this->hiddenReview($author);

        $this->actingAs($author, 'sanctum')->postJson('/api/appeals', [
            'type' => 'review',
            'id' => $review->id,
            'message' => 'Le signalement visait une citation, pas un propos que je tiens.',
        ]);

        $appeal = Appeal::first();

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson('/api/admin/appeals/'.$appeal->id, [
                'status' => Appeal::STATUS_GRANTED,
                'response' => 'Tu as raison, la citation etait claire. Ton avis est remis en ligne.',
            ])
            ->assertStatus(200);

        $this->assertTrue($review->fresh()->is_published);
        $this->assertSame(Appeal::STATUS_GRANTED, $appeal->fresh()->status);
    }

    public function test_upholding_an_appeal_leaves_the_content_hidden(): void
    {
        $author = User::factory()->create();
        $review = $this->hiddenReview($author);

        $this->actingAs($author, 'sanctum')->postJson('/api/appeals', [
            'type' => 'review',
            'id' => $review->id,
            'message' => 'Je conteste la decision prise sur cet avis, elle me parait severe.',
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson('/api/admin/appeals/'.Appeal::first()->id, [
                'status' => Appeal::STATUS_UPHELD,
                'response' => 'La decision est maintenue : le propos vise une personne nommement.',
            ])
            ->assertStatus(200);

        $this->assertFalse($review->fresh()->is_published);
    }

    public function test_an_answer_is_required_to_close_an_appeal(): void
    {
        $author = User::factory()->create();
        $review = $this->hiddenReview($author);

        $this->actingAs($author, 'sanctum')->postJson('/api/appeals', [
            'type' => 'review',
            'id' => $review->id,
            'message' => 'Je demande un reexamen de la decision prise sur cet avis.',
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson('/api/admin/appeals/'.Appeal::first()->id, [
                'status' => Appeal::STATUS_UPHELD,
            ])
            ->assertStatus(422);
    }

    public function test_a_member_cannot_read_the_appeal_queue(): void
    {
        $this->actingAs(User::factory()->create(), 'sanctum')
            ->getJson('/api/admin/appeals')
            ->assertStatus(403);
    }

    public function test_a_member_reads_their_own_appeals_with_the_answer(): void
    {
        $author = User::factory()->create();
        $review = $this->hiddenReview($author);

        $this->actingAs($author, 'sanctum')->postJson('/api/appeals', [
            'type' => 'review',
            'id' => $review->id,
            'message' => 'Je conteste, le lien cite est un article de presse.',
        ]);

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson('/api/admin/appeals/'.Appeal::first()->id, [
                'status' => Appeal::STATUS_GRANTED,
                'response' => 'Verifie, tu as raison. Avis remis en ligne.',
            ]);

        $this->actingAs($author, 'sanctum')
            ->getJson('/api/appeals')
            ->assertStatus(200)
            ->assertJsonPath('data.data.0.response', 'Verifie, tu as raison. Avis remis en ligne.');
    }
}
