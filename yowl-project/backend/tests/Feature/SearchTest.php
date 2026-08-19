<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private function contents(string $query): \Illuminate\Support\Collection
    {
        return collect($this->getJson('/api/reviews?search='.urlencode($query))->json('data.data'))
            ->pluck('content');
    }

    public function test_search_ignores_accents(): void
    {
        Review::factory()->create(['content' => 'Une soirée au cinéma qui valait le détour.']);
        Review::factory()->create(['content' => 'Rien à voir avec le sujet.']);

        // Sans normalisation, "cinema" ne trouvait jamais "cinéma".
        $this->assertContains(
            'Une soirée au cinéma qui valait le détour.',
            $this->contents('cinema')
        );
    }

    public function test_search_ignores_case(): void
    {
        Review::factory()->create(['content' => 'Le Documentaire est remarquable.']);

        $this->assertContains('Le Documentaire est remarquable.', $this->contents('DOCUMENTAIRE'));
    }

    public function test_every_word_must_appear_in_any_order(): void
    {
        Review::factory()->create(['content' => 'Un concert de musique électronique.']);
        Review::factory()->create(['content' => 'Un concert de piano classique.']);

        $trouves = $this->contents('electronique concert');

        $this->assertContains('Un concert de musique électronique.', $trouves);
        $this->assertNotContains('Un concert de piano classique.', $trouves);
    }

    public function test_search_finds_by_author_name(): void
    {
        $auteur = User::factory()->create(['username' => 'nadia_critique']);
        Review::factory()->create(['user_id' => $auteur->id, 'content' => 'Texte sans rapport.']);
        Review::factory()->create(['content' => 'Autre texte.']);

        $this->assertContains('Texte sans rapport.', $this->contents('nadia_critique'));
    }

    public function test_search_finds_by_tag(): void
    {
        $tag = Tag::create(['name' => 'gastronomie']);
        $review = Review::factory()->create(['content' => 'Aucun mot commun ici.']);
        $review->tags()->sync([$tag->id]);
        Review::factory()->create(['content' => 'Encore un autre texte.']);

        $this->assertContains('Aucun mot commun ici.', $this->contents('gastronomie'));
    }

    public function test_a_single_letter_matches_nothing_in_particular(): void
    {
        Review::factory()->count(3)->create();

        // Un terme trop court renverrait tout : il est ignoré.
        $this->assertCount(3, $this->contents('a'));
    }

    public function test_search_combines_with_a_filter(): void
    {
        Review::factory()->create(['content' => 'Concert très aimé.', 'nb_like' => 10]);
        Review::factory()->create(['content' => 'Concert sans réaction.', 'nb_like' => 0]);

        $trouves = collect(
            $this->getJson('/api/reviews?search=concert&noLikes=1')->json('data.data')
        )->pluck('content');

        $this->assertContains('Concert sans réaction.', $trouves);
        $this->assertNotContains('Concert très aimé.', $trouves);
    }
}
