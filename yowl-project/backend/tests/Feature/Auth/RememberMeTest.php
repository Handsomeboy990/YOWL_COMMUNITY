<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RememberMeTest extends TestCase
{
    use RefreshDatabase;

    public function test_without_the_box_the_token_lasts_a_day(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)->assertJsonPath('remember', false);

        $expire = $user->tokens()->first()->expires_at;
        $this->assertNotNull($expire);
        $this->assertTrue($expire->lessThanOrEqualTo(now()->addDay()->addMinute()));
    }

    public function test_with_the_box_the_token_lasts_the_configured_period(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => true,
        ]);

        $response->assertStatus(200)->assertJsonPath('remember', true);

        $expire = $user->tokens()->first()->expires_at;
        // Bien au-dela d'une journee : la case sert enfin a quelque chose.
        $this->assertTrue($expire->greaterThan(now()->addDays(2)));
    }
}
