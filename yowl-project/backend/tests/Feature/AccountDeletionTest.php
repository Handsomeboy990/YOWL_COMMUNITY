<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Supprimer un compte demande deux preuves d'intention, pas un clic.
 *
 * Un dialogue à deux boutons se clique par réflexe. Le mot de passe prouve
 * que la personne devant l'écran est bien la titulaire du compte ; la phrase
 * à recopier oblige à lire ce qu'on est en train de faire.
 */
class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function phrase(): string
    {
        return 'Oui, je veux quitter la communauté '.Settings::get('community.name', 'YOWL');
    }

    private function membre(): User
    {
        Role::findOrCreate('client', 'web');
        $membre = User::factory()->create();
        $membre->assignRole('client');

        return $membre;
    }

    public function test_the_password_alone_is_not_enough(): void
    {
        $membre = $this->membre();

        $this->actingAs($membre, 'sanctum')
            ->deleteJson('/api/users/'.$membre->id, [
                'password' => 'password',
                'confirmation' => 'oui je pars',
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'phrase_incorrecte');

        $this->assertSame($membre->email, $membre->fresh()->email);
    }

    public function test_the_phrase_alone_is_not_enough(): void
    {
        $membre = $this->membre();

        $this->actingAs($membre, 'sanctum')
            ->deleteJson('/api/users/'.$membre->id, [
                'password' => 'ce-n-est-pas-le-bon',
                'confirmation' => $this->phrase(),
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'mot_de_passe_incorrect');

        $this->assertSame($membre->email, $membre->fresh()->email);
    }

    public function test_neither_proof_may_be_omitted(): void
    {
        $membre = $this->membre();

        $this->actingAs($membre, 'sanctum')
            ->deleteJson('/api/users/'.$membre->id, [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password', 'confirmation']);
    }

    public function test_both_proofs_together_erase_the_personal_data(): void
    {
        $membre = $this->membre();

        $this->actingAs($membre, 'sanctum')
            ->deleteJson('/api/users/'.$membre->id, [
                'password' => 'password',
                // La casse ne doit pas décider d'une suppression de compte.
                'confirmation' => '  '.mb_strtoupper($this->phrase()).'  ',
            ])
            ->assertOk();

        $apres = $membre->fresh();
        $this->assertNotSame($membre->email, $apres->email);
        $this->assertSame('Membre supprimé', $apres->fullname);
        $this->assertNull($apres->picture);
    }

    public function test_the_phrase_follows_the_configured_community_name(): void
    {
        Settings::set('community.name', 'YOWL Community');
        $membre = $this->membre();

        // L'ancienne phrase ne doit plus passer : les deux côtés doivent lire
        // le même réglage, sinon le champ devient impossible à satisfaire.
        $this->actingAs($membre, 'sanctum')
            ->deleteJson('/api/users/'.$membre->id, [
                'password' => 'password',
                'confirmation' => 'Oui, je veux quitter la communauté YOWL',
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'phrase_incorrecte');

        $this->actingAs($membre, 'sanctum')
            ->deleteJson('/api/users/'.$membre->id, [
                'password' => 'password',
                'confirmation' => 'Oui, je veux quitter la communauté YOWL Community',
            ])
            ->assertOk();
    }
}
