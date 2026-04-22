<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\BibleChapter;
use App\Models\DailyReading;
use App\Models\Hierarchy;
use App\Models\MessageTemplate;
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

    public function test_horizontal_migration_and_sibling_merge_are_logged(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $squadLeader = User::factory()->create(['role' => User::ROLE_SQUAD_LEADER]);
        $platoonLeaderOne = User::factory()->create(['role' => User::ROLE_PLATOON_LEADER]);
        $platoonLeaderTwo = User::factory()->create(['role' => User::ROLE_PLATOON_LEADER]);
        $batchLeader = User::factory()->create(['role' => User::ROLE_BATCH_LEADER]);
        $teamLeaderOne = User::factory()->create(['role' => User::ROLE_TEAM_LEADER]);
        $teamLeaderTwo = User::factory()->create(['role' => User::ROLE_TEAM_LEADER]);

        $squad = Hierarchy::create([
            'name' => 'Audit Squad',
            'type' => 'squad',
            'leader_id' => $squadLeader->id,
        ]);

        $platoonOne = Hierarchy::create([
            'name' => 'Audit Platoon One',
            'type' => 'platoon',
            'leader_id' => $platoonLeaderOne->id,
            'parent_id' => $squad->id,
        ]);

        $platoonTwo = Hierarchy::create([
            'name' => 'Audit Platoon Two',
            'type' => 'platoon',
            'leader_id' => $platoonLeaderTwo->id,
            'parent_id' => $squad->id,
        ]);

        $batch = Hierarchy::create([
            'name' => 'Audit Batch',
            'type' => 'batch',
            'leader_id' => $batchLeader->id,
            'parent_id' => $platoonOne->id,
        ]);

        $teamSource = Hierarchy::create([
            'name' => 'Audit Team Source',
            'type' => 'team',
            'leader_id' => $teamLeaderOne->id,
            'parent_id' => $batch->id,
        ]);

        $teamTarget = Hierarchy::create([
            'name' => 'Audit Team Target',
            'type' => 'team',
            'leader_id' => $teamLeaderTwo->id,
            'parent_id' => $batch->id,
        ]);

        $batchLeader->update(['hierarchy_id' => $batch->id]);
        $teamLeaderOne->update(['hierarchy_id' => $teamSource->id]);
        $teamLeaderTwo->update(['hierarchy_id' => $teamTarget->id]);

        User::factory()->create([
            'role' => User::ROLE_MEMBER,
            'hierarchy_id' => $teamSource->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.hierarchies.migration.execute'), [
                'source_hierarchy_id' => $batch->id,
                'destination_parent_id' => $platoonTwo->id,
            ])
            ->assertRedirect(route('admin.hierarchies.index'));

        $this->actingAs($admin)
            ->post(route('admin.hierarchies.merge.execute'), [
                'source_hierarchy_id' => $teamSource->id,
                'target_hierarchy_id' => $teamTarget->id,
                'merged_leader_id' => $teamLeaderTwo->id,
                'source_leader_disposition' => 'descendant_team',
                'source_leader_team_id' => $teamTarget->id,
            ])
            ->assertRedirect(route('admin.hierarchies.index'));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'hierarchy.branch_migrated',
            'actor_id' => $admin->id,
            'subject_type' => Hierarchy::class,
            'subject_id' => $batch->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'hierarchy.sibling_merged',
            'actor_id' => $admin->id,
            'subject_type' => Hierarchy::class,
            'subject_id' => $teamTarget->id,
        ]);
    }

    public function test_message_settings_and_template_changes_are_logged_and_audit_export_is_available(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.messages.settings.update'), [
                'default_delivery' => User::MESSAGE_DELIVERY_INBOX,
                'email_enabled' => 1,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.messages.templates.store'), [
                'name' => 'Ops Alert',
                'subject_template' => 'Hello {{ user.name }}',
                'body_template' => 'Please review the update.',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $template = MessageTemplate::query()->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.audits.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'messages.settings_updated',
            'actor_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'messages.template_created',
            'actor_id' => $admin->id,
            'subject_type' => MessageTemplate::class,
            'subject_id' => $template->id,
        ]);
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
