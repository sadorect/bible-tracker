<?php

namespace Tests\Feature;

use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_plan_index_only_lists_publicly_visible_plans(): void
    {
        $user = User::factory()->create();

        $recruitingPlan = ReadingPlan::create([
            'name' => 'Recruiting NT Cohort',
            'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
            'lifecycle_status' => ReadingPlan::STATUS_RECRUITING,
            'chapters_per_day' => 9,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => now()->addDays(5),
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addDays(5),
            'is_active' => true,
        ]);

        $activePlan = ReadingPlan::create([
            'name' => 'Active OT Cohort',
            'type' => ReadingPlan::TYPE_OLD_TESTAMENT,
            'lifecycle_status' => ReadingPlan::STATUS_ACTIVE,
            'chapters_per_day' => 8,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => now()->subDays(2),
            'enrollment_starts_at' => now()->subDays(10),
            'enrollment_ends_at' => now()->addDays(2),
            'is_active' => true,
        ]);

        ReadingPlan::create([
            'name' => 'Draft Cohort',
            'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
            'lifecycle_status' => ReadingPlan::STATUS_DRAFT,
            'chapters_per_day' => 9,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => now()->addDays(14),
            'is_active' => false,
        ]);

        ReadingPlan::create([
            'name' => 'Closed Cohort',
            'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
            'lifecycle_status' => ReadingPlan::STATUS_CLOSED,
            'chapters_per_day' => 9,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => now()->subDays(10),
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reading-plans.index'));

        $response->assertOk();
        $response->assertSee($recruitingPlan->name);
        $response->assertSee($activePlan->name);
        $response->assertDontSee('Draft Cohort');
        $response->assertDontSee('Closed Cohort');
    }

    public function test_direct_join_is_blocked_when_enrollment_window_is_closed(): void
    {
        $user = User::factory()->create();

        $plan = ReadingPlan::create([
            'name' => 'Closed Enrollment Cohort',
            'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
            'lifecycle_status' => ReadingPlan::STATUS_ACTIVE,
            'chapters_per_day' => 9,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => now()->subDays(1),
            'enrollment_starts_at' => now()->subDays(10),
            'enrollment_ends_at' => now()->subMinute(),
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->post(route('reading-plans.join', $plan))
            ->assertRedirect(route('reading-plans.show', $plan))
            ->assertSessionHas('error', 'Enrollment is currently closed for this reading plan.');

        $this->assertDatabaseMissing('reading_plan_participations', [
            'user_id' => $user->id,
            'reading_plan_id' => $plan->id,
        ]);
    }

    public function test_member_plan_index_highlights_a_recommended_next_cohort(): void
    {
        $user = User::factory()->create();

        ReadingPlan::create([
            'name' => 'NT Spring Cohort',
            'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
            'lifecycle_status' => ReadingPlan::STATUS_RECRUITING,
            'chapters_per_day' => 9,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => now()->addDays(5),
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addDays(5),
            'is_active' => true,
        ]);

        ReadingPlan::create([
            'name' => 'OT Summer Cohort',
            'type' => ReadingPlan::TYPE_OLD_TESTAMENT,
            'lifecycle_status' => ReadingPlan::STATUS_RECRUITING,
            'chapters_per_day' => 8,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => now()->addDays(10),
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addDays(10),
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('reading-plans.index'))
            ->assertOk()
            ->assertSee('Recommended next')
            ->assertSee('Start with a New Testament cohort first.')
            ->assertSee('NT Spring Cohort');
    }
}
