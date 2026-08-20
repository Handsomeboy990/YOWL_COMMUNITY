<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Creating the first administrator without a terminal.
 *
 * The free plans of most hosts give no shell, so container start is the only
 * moment left. That means every value has to arrive as an option, and the
 * command has to survive every restart without doing anything twice.
 */
class MakeAdminNonInteractiveTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Aucun rôle n'est créé ici, et c'est le point.
     *
     * Une première version de ces tests appelait Role::findOrCreate dans son
     * setUp. Ils mettaient donc en place la condition même qui manquait sur
     * une base de production fraîchement migrée, et le déploiement a échoué
     * sur un cas que la suite déclarait couvert.
     */
    public function test_it_works_on_a_freshly_migrated_database(): void
    {
        $this->assertSame(0, Role::count(), 'La base de test doit partir sans aucun rôle.');

        $this->artisan('yowl:make-admin', [
            '--if-none' => true,
            '--email' => 'chef@yowl.fr',
            '--username' => 'chef',
            '--password' => 'Un-Mot-Solide-2026',
        ])->assertExitCode(0);

        $this->assertTrue(User::where('email', 'chef@yowl.fr')->first()->hasRole('admin'));
    }

    public function test_it_creates_an_administrator_with_no_prompt(): void
    {
        $this->artisan('yowl:make-admin', [
            '--email' => 'chef@yowl.fr',
            '--username' => 'chef',
            '--password' => 'Un-Mot-Solide-2026',
        ])->assertExitCode(0);

        $admin = User::where('email', 'chef@yowl.fr')->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue(Hash::check('Un-Mot-Solide-2026', $admin->password));

        // email_verified_at n'est pas assignable en masse : sans forceFill il
        // restait nul, et LoginRequest refuse une adresse non vérifiée. Le
        // compte était créé mais inutilisable.
        $this->assertNotNull($admin->email_verified_at, 'Le compte doit être vérifié.');
    }

    public function test_the_created_administrator_can_actually_sign_in(): void
    {
        $this->artisan('yowl:make-admin', [
            '--email' => 'chef@yowl.fr',
            '--username' => 'chef',
            '--password' => 'Un-Mot-Solide-2026',
        ])->assertExitCode(0);

        // La seule vérification qui compte : le compte sert à quelque chose.
        $this->postJson('/api/login', [
            'email' => 'chef@yowl.fr',
            'password' => 'Un-Mot-Solide-2026',
        ])
            ->assertStatus(200)
            ->assertJsonStructure(['token'])
            ->assertJsonPath('user.username', 'chef');
    }

    public function test_if_none_does_nothing_when_an_administrator_exists(): void
    {
        // Ce test-ci a besoin d'un administrateur en place : il déclare donc
        // le rôle, contrairement aux autres qui vérifient le départ à vide.
        Role::findOrCreate('admin', 'web');

        $premier = User::factory()->create(['email' => 'premier@yowl.fr']);
        $premier->assignRole('admin');

        $this->artisan('yowl:make-admin', [
            '--if-none' => true,
            '--email' => 'second@yowl.fr',
            '--username' => 'second',
            '--password' => 'Un-Mot-Solide-2026',
        ])->assertExitCode(0);

        // Le redémarrage du conteneur ne doit pas fabriquer un second compte.
        $this->assertNull(User::where('email', 'second@yowl.fr')->first());
        $this->assertSame(1, User::role('admin')->count());
    }

    public function test_if_none_still_creates_the_very_first_one(): void
    {
        $this->artisan('yowl:make-admin', [
            '--if-none' => true,
            '--email' => 'chef@yowl.fr',
            '--username' => 'chef',
            '--password' => 'Un-Mot-Solide-2026',
        ])->assertExitCode(0);

        $this->assertTrue(User::where('email', 'chef@yowl.fr')->first()->hasRole('admin'));
    }

    public function test_an_existing_account_is_promoted_rather_than_duplicated(): void
    {
        $membre = User::factory()->create(['email' => 'deja@yowl.fr']);

        $this->artisan('yowl:make-admin', [
            '--email' => 'deja@yowl.fr',
            '--password' => 'Un-Mot-Solide-2026',
        ])->assertExitCode(0);

        $this->assertSame(1, User::where('email', 'deja@yowl.fr')->count());
        $this->assertTrue($membre->fresh()->hasRole('admin'));
    }

    public function test_a_weak_password_is_refused(): void
    {
        $this->artisan('yowl:make-admin', [
            '--email' => 'chef@yowl.fr',
            '--username' => 'chef',
            '--password' => 'court',
        ])->assertExitCode(1);

        $this->assertNull(User::where('email', 'chef@yowl.fr')->first());
    }
}
