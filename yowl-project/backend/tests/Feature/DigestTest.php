<?php

namespace Tests\Feature;

use App\Mail\WeeklyDigest;
use App\Models\Review;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DigestTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_member_receives_what_happened_on_their_subjects(): void
    {
        Mail::fake();
        $membre = User::factory()->create();
        $auteur = User::factory()->create();
        $tag = Tag::create(['name' => 'cinema']);

        $review = Review::factory()->create(['user_id' => $auteur->id, 'created_at' => now()->subDay()]);
        $review->tags()->sync([$tag->id]);

        $this->actingAs($membre, 'sanctum')->postJson('/api/follows', ['type' => 'tag', 'id' => $tag->id]);

        $this->artisan('yowl:send-digest')->assertSuccessful();

        Mail::assertSent(WeeklyDigest::class, fn ($mail) => $mail->hasTo($membre->email));
    }

    public function test_nothing_is_sent_when_there_is_nothing_to_say(): void
    {
        Mail::fake();
        User::factory()->create();

        $this->artisan('yowl:send-digest')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_a_member_who_opted_out_receives_nothing(): void
    {
        Mail::fake();
        $membre = User::factory()->create();
        $membre->forceFill(['digest_optin' => false])->save();
        $auteur = User::factory()->create();
        $tag = Tag::create(['name' => 'cinema']);
        $review = Review::factory()->create(['user_id' => $auteur->id, 'created_at' => now()->subDay()]);
        $review->tags()->sync([$tag->id]);

        \App\Models\Follow::create([
            'user_id' => $membre->id,
            'followable_type' => Tag::class,
            'followable_id' => $tag->id,
        ]);

        $this->artisan('yowl:send-digest')->assertSuccessful();

        Mail::assertNotSent(WeeklyDigest::class);
    }

    public function test_the_digest_is_not_sent_twice_in_the_same_week(): void
    {
        Mail::fake();
        $membre = User::factory()->create();
        $auteur = User::factory()->create();
        Review::factory()->create(['user_id' => $auteur->id, 'created_at' => now()->subDay()]);

        $this->artisan('yowl:send-digest');
        $this->artisan('yowl:send-digest');

        Mail::assertSentCount(1);
    }

    public function test_the_unsubscribe_link_works_without_signing_in(): void
    {
        Mail::fake();
        $membre = User::factory()->create();
        $auteur = User::factory()->create();
        Review::factory()->create(['user_id' => $auteur->id, 'created_at' => now()->subDay()]);

        $this->artisan('yowl:send-digest');
        $token = $membre->fresh()->digest_token;
        $this->assertNotNull($token);

        // Aucune authentification : le lien vient d'un email.
        $this->getJson('/api/digest/unsubscribe/'.$token)->assertStatus(200);

        $this->assertFalse($membre->fresh()->digest_optin);
    }

    public function test_an_unknown_token_is_refused(): void
    {
        $this->getJson('/api/digest/unsubscribe/inexistant')->assertStatus(404);
    }

    public function test_a_member_changes_the_preference_from_the_application(): void
    {
        $membre = User::factory()->create();

        $this->actingAs($membre, 'sanctum')
            ->patchJson('/api/digest', ['digest_optin' => false])
            ->assertStatus(200);

        $this->assertFalse($membre->fresh()->digest_optin);
    }
}
