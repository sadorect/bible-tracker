<?php

namespace Tests\Feature;

use App\Models\DailyReading;
use App\Models\Hierarchy;
use App\Models\ReadingPlan;
use App\Models\ReadingProgress;
use App\Models\TrainingResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HierarchyMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_leader_can_view_member_training_and_pace_statuses(): void
    {
        $leader = User::factory()->create(['role' => 'team_leader']);
        $team = Hierarchy::create([
            'name' => 'Team Alpha',
            'type' => 'team',
            'leader_id' => $leader->id,
        ]);

        $leader->update(['hierarchy_id' => $team->id]);

        $inTrainingMember = User::factory()->create([
            'name' => 'In Training Member',
            'hierarchy_id' => $team->id,
        ]);
        $catchingUpMember = User::factory()->create([
            'name' => 'Catching Up Member',
            'hierarchy_id' => $team->id,
        ]);
        $aheadMember = User::factory()->create([
            'name' => 'Ahead Member',
            'hierarchy_id' => $team->id,
        ]);
        $idleMember = User::factory()->create([
            'name' => 'Idle Member',
            'hierarchy_id' => $team->id,
        ]);

        $trainingPlan = $this->makePlan('Training Plan', Carbon::today());
        $catchUpPlan = $this->makePlan('Catch Up Plan', Carbon::yesterday());
        $aheadPlan = $this->makePlan('Ahead Plan', Carbon::yesterday());

        $trainingResource = TrainingResource::create([
            'reading_plan_id' => $trainingPlan->id,
            'title' => 'Orientation Video',
            'resource_type' => TrainingResource::TYPE_YOUTUBE,
            'resource_url' => 'https://www.youtube.com/watch?v=abc123xyz00',
            'sort_order' => 1,
        ]);
        $trainingPlan->refresh();
        $trainingPlan->syncScheduleDates();

        $trainingDays = $this->makeReadings($trainingPlan, 3);
        $catchUpDays = $this->makeReadings($catchUpPlan, 3);
        $aheadDays = $this->makeReadings($aheadPlan, 3);

        $inTrainingMember->readingPlans()->attach($trainingPlan->id, $this->activePivot());
        $catchingUpMember->readingPlans()->attach($catchUpPlan->id, $this->activePivot(currentDay: 2));
        $aheadMember->readingPlans()->attach($aheadPlan->id, $this->activePivot(currentDay: 2));

        ReadingProgress::create([
            'user_id' => $catchingUpMember->id,
            'reading_plan_id' => $catchUpPlan->id,
            'daily_reading_id' => $catchUpDays[0]->id,
            'completed_date' => Carbon::today(),
        ]);

        ReadingProgress::create([
            'user_id' => $aheadMember->id,
            'reading_plan_id' => $aheadPlan->id,
            'daily_reading_id' => $aheadDays[0]->id,
            'completed_date' => Carbon::today(),
        ]);
        ReadingProgress::create([
            'user_id' => $aheadMember->id,
            'reading_plan_id' => $aheadPlan->id,
            'daily_reading_id' => $aheadDays[1]->id,
            'completed_date' => Carbon::today(),
        ]);
        ReadingProgress::create([
            'user_id' => $aheadMember->id,
            'reading_plan_id' => $aheadPlan->id,
            'daily_reading_id' => $aheadDays[2]->id,
            'completed_date' => Carbon::today(),
        ]);

        $response = $this->actingAs($leader)->get(route('hierarchy.manage'));

        $response->assertOk();
        $response->assertSee('In Training Member');
        $response->assertSee('Catching Up Member');
        $response->assertSee('Ahead Member');
        $response->assertSee('Idle Member');
        $response->assertSee('In Training');
        $response->assertSee('Catching Up');
        $response->assertSee('Reading Ahead');
        $response->assertSee('No Active Plan');
        $response->assertSee('0 / 1');
    }

    public function test_non_leader_cannot_access_team_management_page(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $response = $this->actingAs($member)->get(route('hierarchy.manage'));

        $response->assertForbidden();
    }

    public function test_batch_leader_can_filter_team_monitoring_by_group_and_status(): void
    {
        $batchLeader = User::factory()->create(['role' => 'batch_leader']);
        $batch = Hierarchy::create([
            'name' => 'Batch 2026',
            'type' => 'batch',
            'leader_id' => $batchLeader->id,
        ]);

        $batchLeader->update(['hierarchy_id' => $batch->id]);

        $teamAlphaLeader = User::factory()->create(['role' => 'team_leader']);
        $teamBravoLeader = User::factory()->create(['role' => 'team_leader']);

        $teamAlpha = Hierarchy::create([
            'name' => 'Team Alpha',
            'type' => 'team',
            'leader_id' => $teamAlphaLeader->id,
            'parent_id' => $batch->id,
        ]);

        $teamBravo = Hierarchy::create([
            'name' => 'Team Bravo',
            'type' => 'team',
            'leader_id' => $teamBravoLeader->id,
            'parent_id' => $batch->id,
        ]);

        $teamAlphaLeader->update(['hierarchy_id' => $teamAlpha->id]);
        $teamBravoLeader->update(['hierarchy_id' => $teamBravo->id]);

        $aheadMember = User::factory()->create([
            'name' => 'Alpha Ahead Member',
            'hierarchy_id' => $teamAlpha->id,
        ]);
        $catchUpMember = User::factory()->create([
            'name' => 'Bravo Catch Up Member',
            'hierarchy_id' => $teamBravo->id,
        ]);

        $plan = $this->makePlan('Shared Team Plan', Carbon::yesterday());
        $aheadDays = $this->makeReadings($plan, 3);

        $aheadMember->readingPlans()->attach($plan->id, $this->activePivot(currentDay: 2));
        $catchUpMember->readingPlans()->attach($plan->id, $this->activePivot(currentDay: 2));

        ReadingProgress::create([
            'user_id' => $aheadMember->id,
            'reading_plan_id' => $plan->id,
            'daily_reading_id' => $aheadDays[0]->id,
            'completed_date' => Carbon::today(),
        ]);
        ReadingProgress::create([
            'user_id' => $aheadMember->id,
            'reading_plan_id' => $plan->id,
            'daily_reading_id' => $aheadDays[1]->id,
            'completed_date' => Carbon::today(),
        ]);
        ReadingProgress::create([
            'user_id' => $aheadMember->id,
            'reading_plan_id' => $plan->id,
            'daily_reading_id' => $aheadDays[2]->id,
            'completed_date' => Carbon::today(),
        ]);
        ReadingProgress::create([
            'user_id' => $catchUpMember->id,
            'reading_plan_id' => $plan->id,
            'daily_reading_id' => $aheadDays[0]->id,
            'completed_date' => Carbon::today(),
        ]);

        $response = $this->actingAs($batchLeader)->get(route('hierarchy.manage', [
            'status' => 'reading_ahead',
            'hierarchy_id' => $teamAlpha->id,
            'search' => 'Alpha',
        ]));

        $response->assertOk();
        $response->assertSee('Alpha Ahead Member');
        $response->assertDontSee('Bravo Catch Up Member');
        $response->assertSee('Showing 1 of 4 monitored people.');
    }

    public function test_batch_leader_can_reassign_members_to_any_team_below_them(): void
    {
        $batchLeader = User::factory()->create(['role' => User::ROLE_BATCH_LEADER]);
        $batch = Hierarchy::create([
            'name' => 'Batch 2026',
            'type' => 'batch',
            'leader_id' => $batchLeader->id,
        ]);

        $batchLeader->update(['hierarchy_id' => $batch->id]);

        $teamAlphaLeader = User::factory()->create(['role' => User::ROLE_TEAM_LEADER]);
        $teamBravoLeader = User::factory()->create(['role' => User::ROLE_TEAM_LEADER]);

        $teamAlpha = Hierarchy::create([
            'name' => 'Team Alpha',
            'type' => 'team',
            'leader_id' => $teamAlphaLeader->id,
            'parent_id' => $batch->id,
        ]);

        $teamBravo = Hierarchy::create([
            'name' => 'Team Bravo',
            'type' => 'team',
            'leader_id' => $teamBravoLeader->id,
            'parent_id' => $batch->id,
        ]);

        $member = User::factory()->create([
            'role' => User::ROLE_MEMBER,
            'hierarchy_id' => $teamAlpha->id,
        ]);

        $response = $this->actingAs($batchLeader)->post(route('hierarchy.members.update', $member), [
            'hierarchy_id' => $teamBravo->id,
        ]);

        $response->assertRedirect(route('hierarchy.manage'));
        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'hierarchy_id' => $teamBravo->id,
        ]);
    }

    public function test_team_leader_cannot_reassign_members_outside_their_team_scope(): void
    {
        $batchLeader = User::factory()->create(['role' => User::ROLE_BATCH_LEADER]);
        $batch = Hierarchy::create([
            'name' => 'Batch 2026',
            'type' => 'batch',
            'leader_id' => $batchLeader->id,
        ]);

        $teamLeader = User::factory()->create(['role' => User::ROLE_TEAM_LEADER]);
        $otherTeamLeader = User::factory()->create(['role' => User::ROLE_TEAM_LEADER]);

        $teamAlpha = Hierarchy::create([
            'name' => 'Team Alpha',
            'type' => 'team',
            'leader_id' => $teamLeader->id,
            'parent_id' => $batch->id,
        ]);

        $teamBravo = Hierarchy::create([
            'name' => 'Team Bravo',
            'type' => 'team',
            'leader_id' => $otherTeamLeader->id,
            'parent_id' => $batch->id,
        ]);

        $teamLeader->update(['hierarchy_id' => $teamAlpha->id]);

        $member = User::factory()->create([
            'role' => User::ROLE_MEMBER,
            'hierarchy_id' => $teamAlpha->id,
        ]);

        $response = $this->actingAs($teamLeader)->post(route('hierarchy.members.update', $member), [
            'hierarchy_id' => $teamBravo->id,
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('hierarchy_id');
        $this->assertDatabaseHas('users', [
            'id' => $member->id,
            'hierarchy_id' => $teamAlpha->id,
        ]);
    }

    private function makePlan(string $name, Carbon $startDate): ReadingPlan
    {
        $plan = ReadingPlan::create([
            'name' => $name,
            'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
            'description' => $name.' description',
            'chapters_per_day' => 9,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addDays(30),
            'is_active' => true,
        ]);

        return $plan;
    }

    private function makeReadings(ReadingPlan $plan, int $count): array
    {
        $readings = [];

        for ($day = 1; $day <= $count; $day++) {
            $readings[] = DailyReading::create([
                'reading_plan_id' => $plan->id,
                'day_number' => $day,
                'book_start' => 'Matthew',
                'chapter_start' => $day,
                'book_end' => 'Matthew',
                'chapter_end' => $day,
                'is_break_day' => false,
            ]);
        }

        return $readings;
    }

    private function activePivot(int $currentDay = 1): array
    {
        return [
            'joined_date' => Carbon::today(),
            'current_day' => $currentDay,
            'current_streak' => 0,
            'completion_rate' => 0,
            'is_active' => true,
        ];
    }
}
