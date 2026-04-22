<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BibleChapter;
use App\Models\DailyReading;
use App\Models\Hierarchy;
use App\Models\ReadingPlan;
use App\Models\ReadingPlanInvite;
use App\Models\ReadingProgress;
use App\Models\SystemRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_operations_admin_can_open_audit_trail_but_reports_admin_cannot(): void
    {
        $operationsAdmin = User::factory()->create([
            'role' => User::ROLE_MEMBER,
        ]);
        $reportsAdmin = User::factory()->create([
            'role' => User::ROLE_MEMBER,
        ]);

        $operationsAdmin->systemRoles()->attach(
            SystemRole::query()->where('slug', 'operations_admin')->value('id')
        );
        $reportsAdmin->systemRoles()->attach(
            SystemRole::query()->where('slug', 'reports_admin')->value('id')
        );

        $this->actingAs($operationsAdmin)
            ->get(route('admin.audits.index'))
            ->assertOk()
            ->assertSee('Audit Trail');

        $this->actingAs($reportsAdmin)
            ->get(route('admin.audits.index'))
            ->assertForbidden();
    }

    public function test_member_reassignment_creates_an_audit_log_entry(): void
    {
        $leader = User::factory()->create([
            'role' => User::ROLE_TEAM_LEADER,
        ]);

        $team = Hierarchy::create([
            'name' => 'Team Alpha',
            'type' => 'team',
            'leader_id' => $leader->id,
        ]);

        $leader->update(['hierarchy_id' => $team->id]);

        $member = User::factory()->create([
            'role' => User::ROLE_MEMBER,
            'hierarchy_id' => null,
        ]);

        $this->actingAs($leader)
            ->post(route('hierarchy.members.update', $member), [
                'hierarchy_id' => $team->id,
            ])
            ->assertRedirect(route('hierarchy.manage'));

        $auditLog = AuditLog::query()->where('event', 'hierarchy.member_reassigned')->firstOrFail();

        $this->assertSame($leader->id, $auditLog->actor_id);
        $this->assertSame(User::class, $auditLog->subject_type);
        $this->assertSame($member->id, $auditLog->subject_id);
        $this->assertSame('Team Alpha', $auditLog->metadata['new_hierarchy']);
    }

    public function test_plan_actions_are_written_to_the_audit_trail(): void
    {
        $this->seedNewTestamentChapters();

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.reading-plans.store'), [
                'name' => 'Audit Cohort',
                'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
                'lifecycle_status' => ReadingPlan::STATUS_RECRUITING,
                'description' => 'Initial description',
                'chapters_per_day' => 6,
                'streak_days' => 5,
                'break_days' => 1,
                'start_date' => now()->addDays(5)->format('Y-m-d'),
                'enrollment_starts_at' => now()->subDay()->format('Y-m-d H:i:s'),
                'enrollment_ends_at' => now()->addDays(10)->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('admin.reading-plans.index'));

        $plan = ReadingPlan::query()->where('name', 'Audit Cohort')->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.reading-plans.update', $plan), [
                'name' => $plan->name,
                'type' => $plan->type,
                'lifecycle_status' => ReadingPlan::STATUS_ACTIVE,
                'description' => 'Updated description',
                'chapters_per_day' => $plan->chapters_per_day,
                'streak_days' => $plan->streak_days,
                'break_days' => $plan->break_days,
                'start_date' => now()->format('Y-m-d'),
                'enrollment_starts_at' => now()->subDay()->format('Y-m-d H:i:s'),
                'enrollment_ends_at' => now()->addDays(5)->format('Y-m-d H:i:s'),
            ])
            ->assertRedirect(route('admin.reading-plans.index'));

        $this->actingAs($admin)
            ->put(route('admin.reading-plans.settings.update'), [
                'max_live_new_testament' => 2,
                'max_live_old_testament' => 1,
                'max_live_total' => 3,
            ])
            ->assertRedirect(route('admin.reading-plans.index'));

        $this->actingAs($admin)
            ->post(route('admin.reading-plans.invites.store', $plan), [
                'label' => 'Audit Link',
                'expires_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('admin.reading-plans.edit', $plan));

        $invite = ReadingPlanInvite::query()->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('admin.reading-plans.invites.revoke', [$plan, $invite]))
            ->assertRedirect(route('admin.reading-plans.edit', $plan));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'plans.created',
            'actor_id' => $admin->id,
            'subject_type' => ReadingPlan::class,
            'subject_id' => $plan->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'plans.updated',
            'actor_id' => $admin->id,
            'subject_type' => ReadingPlan::class,
            'subject_id' => $plan->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'plans.lifecycle_settings_updated',
            'actor_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'plans.invites.created',
            'actor_id' => $admin->id,
            'subject_type' => ReadingPlanInvite::class,
            'subject_id' => $invite->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'plans.invites.revoked',
            'actor_id' => $admin->id,
            'subject_type' => ReadingPlanInvite::class,
            'subject_id' => $invite->id,
        ]);
    }

    public function test_report_export_is_logged_with_metadata(): void
    {
        $leader = User::factory()->create([
            'role' => User::ROLE_TEAM_LEADER,
        ]);

        $leader->systemRoles()->attach(
            SystemRole::query()->where('slug', 'reports_admin')->value('id')
        );

        $team = Hierarchy::create([
            'name' => 'Team Alpha',
            'type' => 'team',
            'leader_id' => $leader->id,
        ]);

        $leader->update(['hierarchy_id' => $team->id]);

        $member = User::factory()->create([
            'role' => User::ROLE_MEMBER,
            'hierarchy_id' => $team->id,
        ]);

        $plan = ReadingPlan::create([
            'name' => 'Report Cohort',
            'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
            'lifecycle_status' => ReadingPlan::STATUS_ACTIVE,
            'chapters_per_day' => 9,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => now()->subDays(2),
            'is_active' => true,
        ]);

        $reading = DailyReading::create([
            'reading_plan_id' => $plan->id,
            'day_number' => 1,
            'book_start' => 'Matthew',
            'chapter_start' => 1,
            'book_end' => 'Matthew',
            'chapter_end' => 1,
            'is_break_day' => false,
        ]);

        ReadingProgress::create([
            'user_id' => $member->id,
            'reading_plan_id' => $plan->id,
            'daily_reading_id' => $reading->id,
            'completed_date' => now()->subDay(),
        ]);

        $this->actingAs($leader)
            ->get(route('admin.progress.export', [
                'format' => 'csv',
            ]))
            ->assertOk();

        $auditLog = AuditLog::query()->where('event', 'reports.exported')->latest('id')->firstOrFail();

        $this->assertSame($leader->id, $auditLog->actor_id);
        $this->assertSame('csv', $auditLog->metadata['format']);
        $this->assertSame('detail', $auditLog->metadata['report_type']);
        $this->assertSame('Reporting scope: Team Alpha', $auditLog->metadata['scope_label']);
    }

    private function seedNewTestamentChapters(): void
    {
        $rows = [];

        for ($chapter = 1; $chapter <= 260; $chapter++) {
            $rows[] = [
                'book_name' => 'Test Book',
                'chapter_number' => $chapter,
                'day_number' => 1,
                'testament' => 'new',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        BibleChapter::query()->insert($rows);
    }
}
