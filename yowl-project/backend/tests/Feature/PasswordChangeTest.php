<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    private function membre(): User
    {
        return User::factory()->create(['password' => Hash::make('Ancien-Mot-2026')]);
    }

    public function test_a_member_changes_their_password(): void
    {
        $membre = $this->membre();

        $this->actingAs($membre, 'sanctum')
            ->patchJson('/api/mot-de-passe', [
                'current_password' => 'Ancien-Mot-2026',
                'password' => 'Nouveau-Mot-2026',
                'password_confirmation' => 'Nouveau-Mot-2026',
            ])
            ->assertStatus(200);

        $this->assertTrue(Hash::check('Nouveau-Mot-2026', $membre->fresh()->password));
    }

    public function test_the_current_password_is_required_and_checked(): void
    {
        $membre = $this->membre();

        $this->actingAs($membre, 'sanctum')
            ->patchJson('/api/mot-de-passe', [
                'current_password' => 'Ce-N-Est-Pas-Le-Bon',
                'password' => 'Nouveau-Mot-2026',
                'password_confirmation' => 'Nouveau-Mot-2026',
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.current_password.0', 'Ce mot de passe ne correspond pas à ton mot de passe actuel.');

        $this->assertTrue(Hash::check('Ancien-Mot-2026', $membre->fresh()->password));
    }

    public function test_the_confirmation_must_match(): void
    {
        $this->actingAs($this->membre(), 'sanctum')
            ->patchJson('/api/mot-de-passe', [
                'current_password' => 'Ancien-Mot-2026',
                'password' => 'Nouveau-Mot-2026',
                'password_confirmation' => 'Autre-Chose-2026',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_the_new_password_cannot_be_the_old_one(): void
    {
        $this->actingAs($this->membre(), 'sanctum')
            ->patchJson('/api/mot-de-passe', [
                'current_password' => 'Ancien-Mot-2026',
                'password' => 'Ancien-Mot-2026',
                'password_confirmation' => 'Ancien-Mot-2026',
            ])
            ->assertStatus(422);
    }

    public function test_a_weak_password_is_refused(): void
    {
        $this->actingAs($this->membre(), 'sanctum')
            ->patchJson('/api/mot-de-passe', [
                'current_password' => 'Ancien-Mot-2026',
                'password' => 'court',
                'password_confirmation' => 'court',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_changing_the_password_logs_the_other_devices_out(): void
    {
        $membre = $this->membre();
        $autreAppareil = $membre->createToken('telephone')->plainTextToken;
        $this->assertSame(1, $membre->tokens()->count());

        $this->withHeader('Authorization', 'Bearer '.$autreAppareil)
            ->patchJson('/api/mot-de-passe', [
                'current_password' => 'Ancien-Mot-2026',
                'password' => 'Nouveau-Mot-2026',
                'password_confirmation' => 'Nouveau-Mot-2026',
            ])
            ->assertStatus(200);

        // Seul le jeton qui a servi au changement survit.
        $this->assertSame(1, $membre->fresh()->tokens()->count());
    }

    public function test_the_profile_form_can_no_longer_set_a_password(): void
    {
        $membre = $this->membre();

        $this->actingAs($membre, 'sanctum')
            ->postJson('/api/users/'.$membre->id, [
                'username' => 'nouveaupseudo',
                'password' => 'Contourne-2026',
            ])
            ->assertStatus(200);

        // Le champ est ignore : l'ancien mot de passe tient toujours.
        $this->assertTrue(Hash::check('Ancien-Mot-2026', $membre->fresh()->password));
    }

    public function test_a_guest_cannot_change_a_password(): void
    {
        $this->patchJson('/api/mot-de-passe', [
            'current_password' => 'x',
            'password' => 'Nouveau-Mot-2026',
            'password_confirmation' => 'Nouveau-Mot-2026',
        ])->assertStatus(401);
    }
}
