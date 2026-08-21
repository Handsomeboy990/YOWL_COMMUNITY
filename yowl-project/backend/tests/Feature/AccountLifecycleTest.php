<?php

namespace Tests\Feature;

use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccountLifecycleTest extends TestCase
{
    // La suppression exige desormais deux preuves d'intention : le mot de
    // passe courant, et une phrase recopiee. Voir AccountDeletionTest, qui
    // couvre le refus quand l'une des deux manque.

    use RefreshDatabase;

    public function test_changing_the_email_drops_the_verification(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'before@example.com']);
        $this->assertNotNull($user->email_verified_at);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/users/'.$user->id, [
            'email' => 'after@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['email_verification_required' => true]);

        $user->refresh();
        $this->assertSame('after@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->email_verification_code);
        Mail::assertSentCount(1);
    }

    public function test_keeping_the_same_email_keeps_the_verification(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'stable@example.com']);

        $this->actingAs($user, 'sanctum')->postJson('/api/users/'.$user->id, [
            'email' => 'stable@example.com',
            'fullname' => 'Nouveau Nom',
        ])->assertStatus(200);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        Mail::assertNothingSent();
    }

    public function test_deleting_an_account_erases_the_personal_data(): void
    {
        $user = User::factory()->create([
            'username' => 'jean',
            'fullname' => 'Jean Dupont',
            'email' => 'jean@example.com',
        ]);
        $review = Review::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/users/'.$user->id, [
                'password' => 'password',
                'confirmation' => 'Oui, je veux quitter la communauté '.\App\Support\Settings::get('community.name', 'YOWL'),
            ])
            ->assertStatus(200);

        $user->refresh();
        $this->assertNotSame('jean', $user->username);
        $this->assertNotSame('Jean Dupont', $user->fullname);
        $this->assertNotSame('jean@example.com', $user->email);
        $this->assertNull($user->birthdate);
        $this->assertNull($user->picture);
        $this->assertFalse($user->is_active);
        $this->assertNotNull($user->anonymized_at);

        // La contribution reste, pour ne pas trouer les discussions.
        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    public function test_a_deleted_account_cannot_sign_in_again(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/users/'.$user->id, [
                'password' => 'password',
                'confirmation' => 'Oui, je veux quitter la communauté '.\App\Support\Settings::get('community.name', 'YOWL'),
            ])
            ->assertStatus(200);

        // actingAs laisse le garde authentifie pour la suite du test, et la
        // route de connexion est reservee aux visiteurs.
        $this->app->make('auth')->forgetGuards();

        $this->postJson('/api/login', [
            'email' => $user->fresh()->email,
            'password' => 'password',
        ])->assertStatus(422);
    }

    public function test_a_deleted_account_leaves_the_community_count(): void
    {
        User::factory()->count(3)->create();
        $leaving = User::factory()->create();

        $before = $this->getJson('/api/kpi')->json('data.nbUsers');

        $this->actingAs($leaving, 'sanctum')
            ->deleteJson('/api/users/'.$leaving->id, [
                'password' => 'password',
                'confirmation' => 'Oui, je veux quitter la communauté '.\App\Support\Settings::get('community.name', 'YOWL'),
            ])
            ->assertStatus(200);
        $this->flushCache();

        $after = $this->getJson('/api/kpi')->json('data.nbUsers');
        $this->assertSame($before - 1, $after);
    }

    public function test_the_age_partition_counts_each_member_once(): void
    {
        User::factory()->create(['birthdate' => now()->subYears(15)->toDateString()]);
        User::factory()->create(['birthdate' => now()->subYears(20)->toDateString()]);
        User::factory()->create(['birthdate' => now()->subYears(20)->toDateString()]);
        User::factory()->create(['birthdate' => now()->subYears(34)->toDateString()]);

        $ranges = $this->getJson('/api/kpi')->json('data.nbUsersByAgeRange');

        $this->assertSame(1, $ranges['13-17']);
        $this->assertSame(2, $ranges['18-21']);
        $this->assertSame(0, $ranges['22-25']);
        $this->assertSame(0, $ranges['26-29']);
        $this->assertSame(1, $ranges['30-35']);
    }

    private function flushCache(): void
    {
        cache()->forget('kpi.community');
    }
}
