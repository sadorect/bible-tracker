<?php

namespace Tests\Feature;

use App\Models\DailyReading;
use App\Models\Hierarchy;
use App\Models\ReadingPlan;
use App\Models\ReadingProgress;
use App\Models\SystemRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScopedProgressReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_leader_reports_are_scoped_to_their_branch(): void
    {
        [$leader, $memberInScope, $memberOutOfScope, $plan] = $this->seedScopedReportScenario();

        $response = $this->actingAs($leader)->get(route('admin.progress.index'));

        $response->assertOk();
        $response->assertSee($memberInScope->name);
        $response->assertDontSee($memberOutOfScope->name);
        $response->assertSee('Reporting scope: Team Alpha');
        $response->assertSee($plan->name);
    }

    public function test_leader_cannot_open_user_detail_outside_their_tree(): void
    {
        [$leader, , $memberOutOfScope] = $this->seedScopedReportScenario();

        $this->actingAs($leader)
            ->get(route('admin.progress.user', $memberOutOfScope))
            ->assertForbidden();
    }

    public function test_leader_csv_export_only_contains_branch_members(): void
    {
        [$leader, $memberInScope, $memberOutOfScope] = $this->seedScopedReportScenario();

        $response = $this->actingAs($leader)->get(route('admin.progress.export', [
            'format' => 'csv',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();

        $this->assertStringContainsString($memberInScope->name, $content);
        $this->assertStringNotContainsString($memberOutOfScope->name, $content);
    }

    public function test_leader_can_save_a_report_preset(): void
    {
        [$leader] = $this->seedScopedReportScenario();

        $this->actingAs($leader)
            ->post(route('admin.progress.presets.store'), [
                'name' => 'Catch-up only',
                'pace_status' => 'catching_up',
                'date_range' => 'this_week',
            ])
            ->assertRedirect(route('admin.progress.index', [
                'user_id' => 0,
                'plan_id' => 0,
                'hierarchy_id' => 0,
                'role' => '',
                'pace_status' => 'catching_up',
                'training_status' => '',
                'date_range' => 'this_week',
                'start_date' => '',
                'end_date' => '',
            ]));

        $this->assertDatabaseHas('report_presets', [
            'user_id' => $leader->id,
            'name' => 'Catch-up only',
        ]);
    }

    public function test_scoped_progress_index_stays_paginated_with_large_result_sets(): void
    {
        [$leader, , , $plan] = $this->seedScopedReportScenario();

        $reading = DailyReading::query()->where('reading_plan_id', $plan->id)->firstOrFail();
        $teamAlphaId = $leader->hierarchy_id;

        $extraMembers = User::factory()->count(60)->create([
            'role' => User::ROLE_MEMBER,
            'hierarchy_id' => $teamAlphaId,
        ]);

        foreach ($extraMembers as $member) {
            $member->readingPlans()->attach($plan->id, [
                'joined_date' => now()->subDays(2),
                'current_day' => 1,
                'current_streak' => 1,
                'completion_rate' => 100,
                'is_active' => true,
            ]);

            ReadingProgress::create([
                'user_id' => $member->id,
                'reading_plan_id' => $plan->id,
                'daily_reading_id' => $reading->id,
                'completed_date' => now()->subDay(),
            ]);
        }

        $response = $this->actingAs($leader)->get(route('admin.progress.index', [
            'per_page' => 50,
        ]));

        $response->assertOk();
        $response->assertViewHas('progress', fn ($progress) => $progress->perPage() === 50
            && $progress->total() === 61
            && $progress->count() === 50);
        $response->assertSee('Showing 50 of 61 matching entries.');
    }

    public function test_leader_hierarchy_summary_export_only_contains_branch_groups(): void
    {
        [$leader, , , $plan] = $this->seedScopedReportScenario();

        $response = $this->actingAs($leader)->get(route('admin.progress.export', [
            'format' => 'csv',
            'report_type' => 'hierarchy_summary',
            'plan_id' => $plan->id,
        ]));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Team Alpha', $content);
        $this->assertStringNotContainsString('Team Bravo', $content);
    }

    private function seedScopedReportScenario(): array
    {
        $leader = User::factory()->create([
            'role' => User::ROLE_TEAM_LEADER,
        ]);

        $otherLeader = User::factory()->create([
            'role' => User::ROLE_TEAM_LEADER,
        ]);

        $teamAlpha = Hierarchy::create([
            'name' => 'Team Alpha',
            'type' => 'team',
            'leader_id' => $leader->id,
        ]);

        $teamBravo = Hierarchy::create([
            'name' => 'Team Bravo',
            'type' => 'team',
            'leader_id' => $otherLeader->id,
        ]);

        $leader->update(['hierarchy_id' => $teamAlpha->id]);
        $otherLeader->update(['hierarchy_id' => $teamBravo->id]);

        $reportsRole = SystemRole::query()->where('slug', 'reports_admin')->firstOrFail();
        $leader->systemRoles()->syncWithoutDetaching([$reportsRole->id]);

        $memberInScope = User::factory()->create([
            'name' => 'Member In Scope',
            'role' => User::ROLE_MEMBER,
            'hierarchy_id' => $teamAlpha->id,
        ]);

        $memberOutOfScope = User::factory()->create([
            'name' => 'Member Out Of Scope',
            'role' => User::ROLE_MEMBER,
            'hierarchy_id' => $teamBravo->id,
        ]);

        $plan = ReadingPlan::create([
            'name' => 'Scoped Report Plan',
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
            'user_id' => $memberInScope->id,
            'reading_plan_id' => $plan->id,
            'daily_reading_id' => $reading->id,
            'completed_date' => now()->subDay(),
        ]);

        ReadingProgress::create([
            'user_id' => $memberOutOfScope->id,
            'reading_plan_id' => $plan->id,
            'daily_reading_id' => $reading->id,
            'completed_date' => now()->subDay(),
        ]);

        $memberInScope->readingPlans()->attach($plan->id, [
            'joined_date' => now()->subDays(2),
            'current_day' => 1,
            'current_streak' => 1,
            'completion_rate' => 100,
            'is_active' => true,
        ]);

        $memberOutOfScope->readingPlans()->attach($plan->id, [
            'joined_date' => now()->subDays(2),
            'current_day' => 1,
            'current_streak' => 1,
            'completion_rate' => 100,
            'is_active' => true,
        ]);

        return [$leader, $memberInScope, $memberOutOfScope, $plan];
    }
}
