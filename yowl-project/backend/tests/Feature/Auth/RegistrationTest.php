<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Les rôles ne sont plus créés par la classe de base : un test qui en a
     * besoin le déclare, pour que la précondition reste visible ici.
     */
    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('client', 'web');
        Role::findOrCreate('admin', 'web');
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'username' => 'testuser',
            'fullname' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'birthdate' => '2000-01-01',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
    }
}
