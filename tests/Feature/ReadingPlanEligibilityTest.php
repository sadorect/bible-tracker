<?php

namespace Tests\Feature;

use App\Models\DailyReading;
use App\Models\ReadingPlan;
use App\Models\ReadingProgress;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_cannot_join_old_testament_before_completing_new_testament(): void
    {
        $user = User::factory()->create();
        $oldTestamentPlan = $this->makePlan(ReadingPlan::TYPE_OLD_TESTAMENT, 'Old Testament Cohort');

        $response = $this->actingAs($user)->post(route('reading-plans.join', $oldTestamentPlan));

        $response->assertRedirect(route('reading-plans.show', $oldTestamentPlan));
        $response->assertSessionHas('error', 'Old Testament plans unlock after you complete a New Testament plan.');
        $this->assertDatabaseMissing('user_reading_plans', [
            'user_id' => $user->id,
            'reading_plan_id' => $oldTestamentPlan->id,
        ]);
    }

    public function test_users_can_join_old_testament_after_completing_a_new_testament_plan(): void
    {
        $user = User::factory()->create();
        $newTestamentPlan = $this->makePlan(ReadingPlan::TYPE_NEW_TESTAMENT, 'New Testament Cohort');
        $oldTestamentPlan = $this->makePlan(ReadingPlan::TYPE_OLD_TESTAMENT, 'Old Testament Cohort');

        $user->readingPlans()->attach($newTestamentPlan->id, [
            'joined_date' => Carbon::today(),
            'current_day' => 1,
            'current_streak' => 0,
            'completion_rate' => 100,
            'is_active' => true,
        ]);

        $dailyReading = DailyReading::create([
            'reading_plan_id' => $newTestamentPlan->id,
            'day_number' => 1,
            'book_start' => 'Matthew',
            'chapter_start' => 1,
            'book_end' => 'Matthew',
            'chapter_end' => 9,
            'is_break_day' => false,
        ]);

        ReadingProgress::create([
            'user_id' => $user->id,
            'reading_plan_id' => $newTestamentPlan->id,
            'daily_reading_id' => $dailyReading->id,
            'completed_date' => Carbon::today(),
        ]);

        $response = $this->actingAs($user)->post(route('reading-plans.join', $oldTestamentPlan));

        $response->assertRedirect(route('reading-plans.show', $oldTestamentPlan));
        $this->assertDatabaseHas('user_reading_plans', [
            'user_id' => $user->id,
            'reading_plan_id' => $oldTestamentPlan->id,
            'is_active' => true,
        ]);
    }

    private function makePlan(string $type, string $name): ReadingPlan
    {
        $defaults = ReadingPlan::defaultsFor($type);
        $totalReadingDays = (int) ceil($defaults['total_chapters'] / $defaults['chapters_per_day']);

        return ReadingPlan::create([
            'name' => $name,
            'type' => $type,
            'description' => "{$name} description",
            'chapters_per_day' => $defaults['chapters_per_day'],
            'streak_days' => $defaults['streak_days'],
            'break_days' => $defaults['break_days'],
            'start_date' => Carbon::today(),
            'end_date' => Carbon::today()->copy()->addDays(max(
                $totalReadingDays + (intdiv(max($totalReadingDays - 1, 0), $defaults['streak_days']) * $defaults['break_days']) - 1,
                0
            )),
            'is_active' => true,
            'additional_info' => null,
        ]);
    }
}
