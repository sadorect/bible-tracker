<?php

namespace Tests\Feature;

use App\Models\DailyReading;
use App\Models\ReadingPlan;
use App\Models\ReadingPlanInvite;
use App\Models\ReadingPlanParticipation;
use App\Models\ReadingProgress;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanInviteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_enrollment_link_and_choose_login_or_register(): void
    {
        $plan = $this->makePlan();
        $invite = ReadingPlanInvite::create([
            'reading_plan_id' => $plan->id,
            'token' => 'guest-link-token',
            'expires_at' => now()->addDays(7),
        ]);

        $this->get(route('reading-plan-invites.show', $invite->token))
            ->assertOk()
            ->assertSee('Use an existing account')
            ->assertSee('Register a fresh profile');

        $this->get(route('reading-plan-invites.login', $invite->token))
            ->assertRedirect(route('login'));

        $this->assertSame($invite->token, session('pending_reading_plan_invite_token'));
    }

    public function test_existing_user_can_use_invite_to_start_a_fresh_participation_cycle(): void
    {
        $user = User::factory()->create();
        $plan = $this->makePlan();
        $invite = ReadingPlanInvite::create([
            'reading_plan_id' => $plan->id,
            'token' => 'repeat-link-token',
            'expires_at' => now()->addDays(7),
        ]);

        $oldParticipation = ReadingPlanParticipation::create([
            'user_id' => $user->id,
            'reading_plan_id' => $plan->id,
            'participation_number' => 1,
            'join_source' => ReadingPlanParticipation::SOURCE_DIRECT,
            'started_on' => Carbon::today()->subDays(10),
            'status' => ReadingPlanParticipation::STATUS_ACTIVE,
        ]);

        $user->readingPlans()->attach($plan->id, [
            'joined_date' => Carbon::today()->subDays(10),
            'current_participation_id' => $oldParticipation->id,
            'current_day' => 3,
            'current_streak' => 2,
            'completion_rate' => 33.3,
            'is_active' => true,
        ]);

        $dayOne = DailyReading::create([
            'reading_plan_id' => $plan->id,
            'day_number' => 1,
            'book_start' => 'Matthew',
            'chapter_start' => 1,
            'book_end' => 'Matthew',
            'chapter_end' => 1,
            'is_break_day' => false,
        ]);

        ReadingProgress::create([
            'user_id' => $user->id,
            'reading_plan_id' => $plan->id,
            'reading_plan_participation_id' => $oldParticipation->id,
            'daily_reading_id' => $dayOne->id,
            'completed_date' => Carbon::today()->subDays(9),
        ]);

        $this->actingAs($user)
            ->post(route('reading-plan-invites.accept', $invite->token))
            ->assertRedirect(route('reading-plans.show', $plan));

        $this->assertDatabaseHas('reading_plan_participations', [
            'user_id' => $user->id,
            'reading_plan_id' => $plan->id,
            'participation_number' => 2,
            'reading_plan_invite_id' => $invite->id,
            'status' => ReadingPlanParticipation::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('reading_plan_participations', [
            'id' => $oldParticipation->id,
            'status' => ReadingPlanParticipation::STATUS_RESTARTED,
        ]);

        $pivot = $user->fresh()->readingPlans()->where('reading_plan_id', $plan->id)->firstOrFail()->pivot;
        $this->assertSame(1, (int) $pivot->current_day);
        $this->assertSame(0, (int) $pivot->current_streak);
        $this->assertSame(0.0, (float) $pivot->completion_rate);
        $this->assertNotSame($oldParticipation->id, (int) $pivot->current_participation_id);

        $this->assertDatabaseHas('reading_progress', [
            'reading_plan_participation_id' => $oldParticipation->id,
            'daily_reading_id' => $dayOne->id,
        ]);
    }

    public function test_admin_can_generate_and_revoke_an_enrollment_link(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $plan = $this->makePlan();

        $this->actingAs($admin)
            ->post(route('admin.reading-plans.invites.store', $plan), [
                'label' => 'Open Day',
                'expires_at' => now()->addDays(3)->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('admin.reading-plans.edit', $plan));

        $invite = ReadingPlanInvite::query()->firstOrFail();

        $this->assertSame('Open Day', $invite->label);

        $this->actingAs($admin)
            ->delete(route('admin.reading-plans.invites.revoke', [$plan, $invite]))
            ->assertRedirect(route('admin.reading-plans.edit', $plan));

        $this->assertNotNull($invite->fresh()->revoked_at);
    }

    private function makePlan(): ReadingPlan
    {
        return ReadingPlan::create([
            'name' => 'Invite Cohort',
            'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
            'description' => 'Invite cohort description',
            'chapters_per_day' => 9,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->addDays(30),
            'is_active' => true,
        ]);
    }
}
