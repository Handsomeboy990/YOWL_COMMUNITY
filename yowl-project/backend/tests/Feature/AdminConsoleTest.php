<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminConsoleTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Settings::forget();
        // spatie garde ses roles en cache : sans cela, un role cree dans le
        // test reste introuvable pour le code qui tourne juste apres.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin;
    }

    public function test_a_regular_member_cannot_read_the_settings(): void
    {
        $this->admin();
        $member = User::factory()->create();

        $this->actingAs($member, 'sanctum')->getJson('/api/admin/settings')->assertStatus(403);
    }

    public function test_an_admin_reads_the_settings_with_their_defaults(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')->getJson('/api/admin/settings');

        $response->assertStatus(200);
        $keys = collect($response->json('data'))->pluck('key');
        $this->assertContains('registration.age_max', $keys);
        $this->assertContains('registration.open', $keys);
    }

    public function test_an_unknown_setting_is_refused(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson('/api/admin/settings', ['settings' => ['app.debug' => true]])
            ->assertStatus(422);
    }

    public function test_an_out_of_range_value_is_refused(): void
    {
        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson('/api/admin/settings', ['settings' => ['registration.age_min' => 4]])
            ->assertStatus(422);
    }

    public function test_raising_the_age_limit_lets_an_older_member_register(): void
    {
        Mail::fake();
        $this->admin();

        $payload = fn (string $email) => [
            'username' => 'nouveau'.rand(1000, 9999),
            'fullname' => 'Nouveau Membre',
            'email' => $email,
            'password' => 'Password-1234',
            'password_confirmation' => 'Password-1234',
            'birthdate' => now()->subYears(48)->format('Y-m-d'),
        ];

        // Avec la borne par defaut a 35 ans, la personne est refusee.
        $this->postJson('/api/register', $payload('avant@example.com'))->assertStatus(422);

        // L'administration retire la borne haute.
        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson('/api/admin/settings', ['settings' => ['registration.age_max' => null]])
            ->assertStatus(200);

        $this->app->make('auth')->forgetGuards();

        $this->postJson('/api/register', $payload('apres@example.com'))->assertStatus(200);
    }

    public function test_closing_registration_refuses_new_accounts(): void
    {
        Mail::fake();
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/admin/settings', ['settings' => ['registration.open' => false]])
            ->assertStatus(200);

        $this->app->make('auth')->forgetGuards();

        $this->postJson('/api/register', [
            'username' => 'refuse',
            'fullname' => 'Refuse Moi',
            'email' => 'refuse@example.com',
            'password' => 'Password-1234',
            'password_confirmation' => 'Password-1234',
            'birthdate' => now()->subYears(20)->format('Y-m-d'),
        ])->assertStatus(403);
    }

    public function test_a_settings_change_is_written_to_the_audit_log(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/admin/settings', ['settings' => ['registration.age_max' => 40]])
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'settings.updated',
        ]);
    }

    public function test_an_admin_creates_a_member_with_a_role(): void
    {
        $response = $this->actingAs($this->admin(), 'sanctum')->postJson('/api/admin/users', [
            'username' => 'collegue',
            'fullname' => 'Collegue Nouveau',
            'email' => 'collegue@example.com',
            'password' => 'Password-1234',
            'password_confirmation' => 'Password-1234',
            'roles' => ['client'],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'collegue@example.com']);

        $created = User::where('email', 'collegue@example.com')->first();
        $this->assertTrue($created->hasRole('client'));
        $this->assertNotNull($created->email_verified_at);
        // Le mot de passe ne revient jamais dans la reponse.
        $this->assertStringNotContainsString('Password-1234', $response->getContent());
    }

    public function test_the_role_listing_answers_with_its_member_counts(): void
    {
        $admin = $this->admin();
        Permission::create(['name' => 'moderate.reports', 'guard_name' => 'web']);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/roles');

        $response->assertStatus(200);
        $roles = collect($response->json('data.roles'));
        $adminRole = $roles->firstWhere('name', 'admin');

        $this->assertNotNull($adminRole);
        $this->assertSame(1, $adminRole['users_count']);
        $this->assertTrue($adminRole['protected']);
        $this->assertContains('moderate.reports', $response->json('data.permissions'));
    }

    public function test_a_role_still_carried_by_a_member_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'redacteur', 'guard_name' => 'web']);
        User::factory()->create()->assignRole('redacteur');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/admin/roles/'.$role->id)
            ->assertStatus(422);
    }

    public function test_an_unused_role_is_deleted(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'inutile', 'guard_name' => 'web']);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/admin/roles/'.$role->id)
            ->assertStatus(200);

        $this->assertDatabaseMissing('roles', ['name' => 'inutile']);
    }

    public function test_a_role_can_be_created_and_given_permissions(): void
    {
        $admin = $this->admin();
        Permission::create(['name' => 'moderate.reports', 'guard_name' => 'web']);

        $this->actingAs($admin, 'sanctum')->postJson('/api/admin/roles', [
            'name' => 'moderateur',
            'permissions' => ['moderate.reports'],
        ])->assertStatus(201);

        $role = Role::findByName('moderateur', 'web');
        $this->assertTrue($role->hasPermissionTo('moderate.reports'));
    }

    public function test_the_roles_the_platform_needs_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $adminRole = Role::findByName('admin', 'web');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson('/api/admin/roles/'.$adminRole->id)
            ->assertStatus(422);

        $this->assertNotNull(Role::findByName('admin', 'web'));
    }

    public function test_an_admin_cannot_strip_their_own_administrator_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/admin/users/'.$admin->id.'/roles', ['roles' => ['client']])
            ->assertStatus(403);

        $this->assertTrue($admin->fresh()->hasRole('admin'));
    }

    public function test_the_audit_log_is_readable_by_an_admin(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/admin/settings', ['settings' => ['community.name' => 'YOWL Test']])
            ->assertStatus(200);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/audit-log');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data.data'));
    }
}
