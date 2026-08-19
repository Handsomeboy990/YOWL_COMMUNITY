<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagFeedTest extends TestCase
{
    use RefreshDatabase;

    private function tagAvecAvis(string $nom, int $combien = 3): Tag
    {
        $tag = Tag::create(['name' => $nom]);
        foreach (range(1, $combien) as $ignored) {
            Review::factory()->create(['is_published' => true])->tags()->sync([$tag->id]);
        }

        return $tag;
    }

    public function test_a_topic_has_its_own_page_with_its_counts(): void
    {
        $tag = $this->tagAvecAvis('cinema', 4);

        $response = $this->getJson('/api/sujets/cinema');

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'cinema');
        $response->assertJsonPath('data.stats.reviews', 4);
        $this->assertSame(4, $response->json('data.stats.contributors'));
        $this->assertSame($tag->id, $response->json('data.id'));
    }

    public function test_the_topic_is_found_whatever_the_case(): void
    {
        $this->tagAvecAvis('cinema');

        $this->getJson('/api/sujets/CINEMA')->assertStatus(200);
    }

    public function test_an_unknown_topic_answers_not_found(): void
    {
        $this->getJson('/api/sujets/inexistant')->assertStatus(404);
    }

    public function test_the_topic_feed_only_carries_that_topic(): void
    {
        $cinema = $this->tagAvecAvis('cinema', 2);
        $this->tagAvecAvis('cuisine', 3);

        $avis = $this->getJson('/api/sujets/cinema/avis')->json('data.data');

        $this->assertCount(2, $avis);
        foreach ($avis as $item) {
            $this->assertContains($cinema->id, collect($item['tags'])->pluck('id')->all());
        }
    }

    public function test_an_unpublished_review_stays_out_of_the_topic(): void
    {
        $tag = Tag::create(['name' => 'cinema']);
        Review::factory()->create(['is_published' => true, 'content' => 'visible'])->tags()->sync([$tag->id]);
        Review::factory()->create(['is_published' => false, 'content' => 'cachee'])->tags()->sync([$tag->id]);

        $contenus = collect($this->getJson('/api/sujets/cinema/avis')->json('data.data'))->pluck('content');

        $this->assertContains('visible', $contenus);
        $this->assertNotContains('cachee', $contenus);
    }

    public function test_a_blocked_member_disappears_from_the_topic_feed(): void
    {
        $moi = User::factory()->create();
        $genant = User::factory()->create();
        $tag = Tag::create(['name' => 'cinema']);
        Review::factory()->create(['user_id' => $genant->id, 'content' => 'du bloque'])->tags()->sync([$tag->id]);

        $this->actingAs($moi, 'sanctum')->postJson('/api/blocks/'.$genant->id);

        $contenus = collect(
            $this->actingAs($moi, 'sanctum')->getJson('/api/sujets/cinema/avis')->json('data.data')
        )->pluck('content');

        $this->assertNotContains('du bloque', $contenus);
    }

    public function test_the_topic_lists_its_main_contributors(): void
    {
        $tag = Tag::create(['name' => 'cinema']);
        $assidu = User::factory()->create();
        foreach (range(1, 3) as $ignored) {
            Review::factory()->create(['user_id' => $assidu->id])->tags()->sync([$tag->id]);
        }
        Review::factory()->create()->tags()->sync([$tag->id]);

        $premiers = $this->getJson('/api/sujets/cinema')->json('data.top_contributors');

        $this->assertSame($assidu->id, $premiers[0]['id']);
    }

    public function test_the_topic_suggests_the_ones_that_travel_with_it(): void
    {
        $cinema = Tag::create(['name' => 'cinema']);
        $serie = Tag::create(['name' => 'serie']);
        $sansRapport = Tag::create(['name' => 'cuisine']);
        Review::factory()->create()->tags()->sync([$cinema->id, $serie->id]);
        Review::factory()->create()->tags()->sync([$sansRapport->id]);

        $voisins = collect($this->getJson('/api/sujets/cinema')->json('data.related'))->pluck('name');

        $this->assertContains('serie', $voisins);
        $this->assertNotContains('cuisine', $voisins);
    }

    public function test_the_directory_leaves_out_topics_nobody_used(): void
    {
        $this->tagAvecAvis('cinema');
        Tag::create(['name' => 'jamais-utilise']);

        $noms = collect($this->getJson('/api/sujets')->json('data'))->pluck('name');

        $this->assertContains('cinema', $noms);
        $this->assertNotContains('jamais-utilise', $noms);
    }
}
