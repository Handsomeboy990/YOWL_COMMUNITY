<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Report;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReportTest extends TestCase
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

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_a_member_can_report_a_review(): void
    {
        $review = Review::factory()->create();
        $member = User::factory()->create();

        $this->actingAs($member, 'sanctum')->postJson('/api/reports', [
            'type' => 'review',
            'id' => $review->id,
            'reason' => 'spam',
            'details' => 'Lien publicitaire répété.',
        ])->assertStatus(201);

        $this->assertDatabaseHas('reports', [
            'user_id' => $member->id,
            'reportable_type' => Review::class,
            'reportable_id' => $review->id,
            'reason' => 'spam',
            'status' => Report::STATUS_PENDING,
        ]);
    }

    public function test_a_member_can_report_a_comment(): void
    {
        $comment = Comment::factory()->create();
        $member = User::factory()->create();

        $this->actingAs($member, 'sanctum')->postJson('/api/reports', [
            'type' => 'comment',
            'id' => $comment->id,
            'reason' => 'harassment',
        ])->assertStatus(201);

        $this->assertDatabaseHas('reports', [
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
        ]);
    }

    public function test_guests_cannot_report(): void
    {
        $review = Review::factory()->create();

        $this->postJson('/api/reports', [
            'type' => 'review',
            'id' => $review->id,
            'reason' => 'spam',
        ])->assertStatus(401);
    }

    public function test_a_member_cannot_report_their_own_content(): void
    {
        $member = User::factory()->create();
        $review = Review::factory()->for($member)->create();

        $this->actingAs($member, 'sanctum')->postJson('/api/reports', [
            'type' => 'review',
            'id' => $review->id,
            'reason' => 'spam',
        ])->assertStatus(422);

        $this->assertDatabaseCount('reports', 0);
    }

    public function test_reporting_twice_does_not_duplicate_the_report(): void
    {
        $review = Review::factory()->create();
        $member = User::factory()->create();
        $payload = ['type' => 'review', 'id' => $review->id, 'reason' => 'spam'];

        $this->actingAs($member, 'sanctum')->postJson('/api/reports', $payload)->assertStatus(201);
        $this->actingAs($member, 'sanctum')->postJson('/api/reports', $payload)->assertStatus(200);

        $this->assertDatabaseCount('reports', 1);
    }

    public function test_reporting_unknown_content_returns_not_found(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member, 'sanctum')->postJson('/api/reports', [
            'type' => 'review',
            'id' => 99999,
            'reason' => 'spam',
        ])->assertStatus(404);
    }

    public function test_an_unknown_reason_is_rejected(): void
    {
        $review = Review::factory()->create();
        $member = User::factory()->create();

        $this->actingAs($member, 'sanctum')->postJson('/api/reports', [
            'type' => 'review',
            'id' => $review->id,
            'reason' => 'because',
        ])->assertStatus(422);
    }

    public function test_an_arbitrary_model_cannot_be_reported(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member, 'sanctum')->postJson('/api/reports', [
            'type' => 'user',
            'id' => $member->id,
            'reason' => 'spam',
        ])->assertStatus(422);
    }

    public function test_regular_members_cannot_read_the_moderation_queue(): void
    {
        $member = User::factory()->create();
        $member->assignRole('client');

        $this->actingAs($member, 'sanctum')->getJson('/api/admin/reports')->assertStatus(403);
    }

    public function test_an_admin_reads_the_pending_queue(): void
    {
        $review = Review::factory()->create();
        $reporter = User::factory()->create();
        $this->actingAs($reporter, 'sanctum')->postJson('/api/reports', [
            'type' => 'review',
            'id' => $review->id,
            'reason' => 'spam',
        ]);

        $this->actingAs($this->admin(), 'sanctum')->getJson('/api/admin/reports?status=pending')
            ->assertStatus(200)
            ->assertJsonPath('pending_count', 1)
            ->assertJsonCount(1, 'data.data');
    }

    public function test_an_admin_dismisses_a_report_without_touching_the_content(): void
    {
        $review = Review::factory()->create();
        $report = $this->reportOn($review);
        $admin = $this->admin();

        $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/admin/reports/{$report->id}", ['status' => 'dismissed'])
            ->assertStatus(200);

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => Report::STATUS_DISMISSED,
            'handled_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    public function test_an_admin_can_delete_the_reported_content(): void
    {
        $review = Review::factory()->create();
        $report = $this->reportOn($review);

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/admin/reports/{$report->id}", [
                'status' => 'actioned',
                'delete_content' => true,
            ])
            ->assertStatus(200);

        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
        $this->assertDatabaseHas('reports', ['id' => $report->id, 'status' => Report::STATUS_ACTIONED]);
    }

    public function test_a_report_cannot_be_pushed_back_to_pending(): void
    {
        $report = $this->reportOn(Review::factory()->create());

        $this->actingAs($this->admin(), 'sanctum')
            ->patchJson("/api/admin/reports/{$report->id}", ['status' => 'pending'])
            ->assertStatus(422);
    }

    /**
     * A pending report filed by a third party on the given content.
     */
    private function reportOn(Review $review): Report
    {
        $report = new Report([
            'user_id' => User::factory()->create()->id,
            'reason' => 'spam',
        ]);
        $report->reportable()->associate($review);
        $report->save();

        return $report;
    }
}
