<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use App\Notifications\Mentioned;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_mention_in_a_review_notifies_the_member(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $cible = User::factory()->create(['username' => 'nadia_s']);

        $this->actingAs($author, 'sanctum')->postJson('/api/reviews', [
            'content' => 'Bien vu @nadia_s, je suis du même avis.',
        ])->assertStatus(201);

        Notification::assertSentTo($cible, Mentioned::class);
    }

    public function test_a_mention_in_a_comment_notifies_the_member(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $cible = User::factory()->create(['username' => 'lucas_m']);
        $review = Review::factory()->create();

        $this->actingAs($author, 'sanctum')->postJson('/api/comments', [
            'review_id' => $review->id,
            'content' => 'Demande à @lucas_m, il saura.',
        ])->assertStatus(201);

        Notification::assertSentTo($cible, Mentioned::class);
    }

    public function test_mentioning_yourself_notifies_nobody(): void
    {
        Notification::fake();
        $author = User::factory()->create(['username' => 'moi_meme']);

        $this->actingAs($author, 'sanctum')->postJson('/api/reviews', [
            'content' => 'Note pour @moi_meme : y repenser demain.',
        ])->assertStatus(201);

        Notification::assertNothingSent();
    }

    public function test_a_member_who_blocked_the_author_is_never_mentioned(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $cible = User::factory()->create(['username' => 'ne_veut_pas']);

        // La cible bloque l'auteur : la mention ne doit pas servir a la joindre.
        $this->actingAs($cible, 'sanctum')->postJson('/api/blocks/'.$author->id);

        $this->actingAs($author, 'sanctum')->postJson('/api/reviews', [
            'content' => 'Coucou @ne_veut_pas',
        ])->assertStatus(201);

        Notification::assertNotSentTo($cible, Mentioned::class);
    }

    public function test_an_unknown_handle_is_ignored(): void
    {
        Notification::fake();
        $author = User::factory()->create();

        $this->actingAs($author, 'sanctum')->postJson('/api/reviews', [
            'content' => 'Écris à @personne_ici et @autre_inconnu.',
        ])->assertStatus(201);

        Notification::assertNothingSent();
    }

    public function test_an_email_address_is_not_read_as_a_mention(): void
    {
        Notification::fake();
        $author = User::factory()->create();
        $cible = User::factory()->create(['username' => 'contact']);

        $this->actingAs($author, 'sanctum')->postJson('/api/reviews', [
            'content' => 'Écris à jean@contact pour en savoir plus.',
        ])->assertStatus(201);

        Notification::assertNotSentTo($cible, Mentioned::class);
    }
}
