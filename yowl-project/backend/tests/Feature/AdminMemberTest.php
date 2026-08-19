<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminMemberTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'client', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin;
    }

    public function test_a_regular_member_cannot_read_a_member_record(): void
    {
        $this->admin();
        $member = User::factory()->create();
        $nosy = User::factory()->create();

        $this->actingAs($nosy, 'sanctum')
            ->getJson('/api/admin/users/'.$member->id)
            ->assertStatus(403);
    }

    public function test_the_record_gathers_what_the_member_wrote_and_received(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();
        $someone = User::factory()->create();

        $review = Review::factory()->create(['user_id' => $member->id, 'nb_views' => 120, 'nb_like' => 4]);
        Review::factory()->create(['user_id' => $member->id, 'nb_views' => 30, 'nb_like' => 1]);
        Comment::factory()->count(2)->create(['user_id' => $member->id, 'review_id' => $review->id]);

        $report = new Report(['user_id' => $someone->id, 'reason' => 'spam']);
        $report->reportable()->associate($review);
        $report->save();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/users/'.$member->id);

        $response->assertStatus(200);
        $response->assertJsonPath('data.stats.reviews', 2);
        $response->assertJsonPath('data.stats.views', 150);
        $response->assertJsonPath('data.stats.likes_received', 5);
        $response->assertJsonPath('data.stats.comments_written', 2);
        // Le signalement vise le contenu du membre, pas le membre lui-meme.
        $response->assertJsonPath('data.stats.reports_received', 1);
        $response->assertJsonPath('data.stats.reports_filed', 0);
        $this->assertCount(2, $response->json('data.recent_reviews'));
    }

    public function test_an_admin_edits_a_member_record(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create(['fullname' => 'Ancien Nom']);

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/admin/users/'.$member->id, ['fullname' => 'Nouveau Nom'])
            ->assertStatus(200);

        $this->assertSame('Nouveau Nom', $member->fresh()->fullname);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.updated', 'user_id' => $admin->id]);
    }

    public function test_editing_cannot_take_a_username_already_taken(): void
    {
        $admin = $this->admin();
        User::factory()->create(['username' => 'deja_pris']);
        $member = User::factory()->create();

        $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/admin/users/'.$member->id, ['username' => 'deja_pris'])
            ->assertStatus(422);
    }

    public function test_regenerating_a_password_changes_it_and_revokes_the_sessions(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();
        $member->createToken('session');
        $formerHash = $member->password;

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/users/'.$member->id.'/password');

        $response->assertStatus(200);
        $issued = $response->json('data.password');
        $this->assertNotEmpty($issued);

        $member->refresh();
        $this->assertNotSame($formerHash, $member->password);
        $this->assertTrue(Hash::check($issued, $member->password));
        $this->assertSame(0, $member->tokens()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.password_regenerated']);
    }

    public function test_a_deleted_account_cannot_have_its_password_regenerated(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create();
        $member->forceFill(['anonymized_at' => now()])->save();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/users/'.$member->id.'/password')
            ->assertStatus(422);
    }
}
