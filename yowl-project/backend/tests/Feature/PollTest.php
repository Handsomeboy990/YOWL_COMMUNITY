<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PollTest extends TestCase
{
    use RefreshDatabase;

    private function pollOn(Review $review, User $author): array
    {
        return $this->actingAs($author, 'sanctum')->postJson('/api/reviews/'.$review->id.'/poll', [
            'question' => 'Faut-il aller le voir au cinéma ?',
            'options' => ['Oui, en salle', 'Attendre le streaming', 'Passer son tour'],
        ])->json('data');
    }

    public function test_an_author_attaches_a_poll_to_their_review(): void
    {
        $author = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $author->id]);

        $poll = $this->pollOn($review, $author);

        $this->assertSame('Faut-il aller le voir au cinéma ?', $poll['question']);
        $this->assertCount(3, $poll['options']);
        $this->assertSame(0, $poll['total_votes']);
    }

    public function test_only_the_author_attaches_a_poll(): void
    {
        $author = User::factory()->create();
        $autre = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $author->id]);

        $this->actingAs($autre, 'sanctum')->postJson('/api/reviews/'.$review->id.'/poll', [
            'question' => 'Une question qui ne me regarde pas',
            'options' => ['Oui', 'Non'],
        ])->assertStatus(403);
    }

    public function test_a_poll_needs_at_least_two_answers(): void
    {
        $author = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $author->id]);

        $this->actingAs($author, 'sanctum')->postJson('/api/reviews/'.$review->id.'/poll', [
            'question' => 'Une seule réponse possible ?',
            'options' => ['Oui'],
        ])->assertStatus(422);
    }

    public function test_results_stay_hidden_until_the_reader_has_voted(): void
    {
        $author = User::factory()->create();
        $lecteur = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $author->id]);
        $poll = $this->pollOn($review, $author);

        // Montrer les resultats avant le vote oriente la reponse.
        $avant = $this->actingAs($lecteur, 'sanctum')->getJson('/api/polls/'.$poll['id'])->json('data');
        $this->assertFalse($avant['revealed']);
        $this->assertNull($avant['options'][0]['votes']);

        $this->actingAs($lecteur, 'sanctum')->postJson('/api/polls/'.$poll['id'].'/vote', [
            'option_id' => $poll['options'][0]['id'],
        ])->assertStatus(200);

        $apres = $this->actingAs($lecteur, 'sanctum')->getJson('/api/polls/'.$poll['id'])->json('data');
        $this->assertTrue($apres['revealed']);
        $this->assertSame(1, $apres['options'][0]['votes']);
        $this->assertSame(100, $apres['options'][0]['share']);
    }

    public function test_a_member_has_one_voice_and_can_change_it(): void
    {
        $author = User::factory()->create();
        $lecteur = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $author->id]);
        $poll = $this->pollOn($review, $author);

        $this->actingAs($lecteur, 'sanctum')->postJson('/api/polls/'.$poll['id'].'/vote', [
            'option_id' => $poll['options'][0]['id'],
        ]);
        $apres = $this->actingAs($lecteur, 'sanctum')->postJson('/api/polls/'.$poll['id'].'/vote', [
            'option_id' => $poll['options'][1]['id'],
        ])->json('data');

        // Le total ne bouge pas, la voix se deplace.
        $this->assertSame(1, $apres['total_votes']);
        $this->assertSame(0, $apres['options'][0]['votes']);
        $this->assertSame(1, $apres['options'][1]['votes']);
    }

    public function test_voting_twice_the_same_way_changes_nothing(): void
    {
        $author = User::factory()->create();
        $lecteur = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $author->id]);
        $poll = $this->pollOn($review, $author);

        foreach (range(1, 3) as $ignored) {
            $this->actingAs($lecteur, 'sanctum')->postJson('/api/polls/'.$poll['id'].'/vote', [
                'option_id' => $poll['options'][0]['id'],
            ]);
        }

        $etat = $this->actingAs($lecteur, 'sanctum')->getJson('/api/polls/'.$poll['id'])->json('data');
        $this->assertSame(1, $etat['total_votes']);
    }

    public function test_an_answer_from_another_poll_is_refused(): void
    {
        $author = User::factory()->create();
        $lecteur = User::factory()->create();
        $premier = $this->pollOn(Review::factory()->create(['user_id' => $author->id]), $author);
        $second = $this->pollOn(Review::factory()->create(['user_id' => $author->id]), $author);

        $this->actingAs($lecteur, 'sanctum')->postJson('/api/polls/'.$premier['id'].'/vote', [
            'option_id' => $second['options'][0]['id'],
        ])->assertStatus(422);
    }

    public function test_a_closed_poll_refuses_new_votes(): void
    {
        $author = User::factory()->create();
        $lecteur = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $author->id]);
        $poll = $this->pollOn($review, $author);

        \App\Models\Poll::whereKey($poll['id'])->update(['closes_at' => now()->subHour()]);

        $this->actingAs($lecteur, 'sanctum')->postJson('/api/polls/'.$poll['id'].'/vote', [
            'option_id' => $poll['options'][0]['id'],
        ])->assertStatus(422);
    }

    public function test_a_closed_poll_reveals_its_results_to_everybody(): void
    {
        $author = User::factory()->create();
        $passant = User::factory()->create();
        $review = Review::factory()->create(['user_id' => $author->id]);
        $poll = $this->pollOn($review, $author);

        \App\Models\Poll::whereKey($poll['id'])->update(['closes_at' => now()->subHour()]);

        $etat = $this->actingAs($passant, 'sanctum')->getJson('/api/polls/'.$poll['id'])->json('data');
        $this->assertTrue($etat['revealed']);
    }
}
