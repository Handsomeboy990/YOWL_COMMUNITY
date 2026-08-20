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

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('admin', 'web');
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
    }

    public function test_if_none_does_nothing_when_an_administrator_exists(): void
    {
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
