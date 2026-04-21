<?php

namespace Tests\Feature;

use App\Models\DailyReading;
use App\Models\ReadingPlan;
use App\Models\TrainingResource;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingProgressFlexibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_mark_ahead_using_quick_mark_ranges(): void
    {
        $user = User::factory()->create();
        $plan = $this->makePlan(ReadingPlan::TYPE_NEW_TESTAMENT, 'Speed Readers Cohort');
        $days = $this->makeReadings($plan, 5);

        $user->readingPlans()->attach($plan->id, [
            'joined_date' => Carbon::today(),
            'current_day' => 2,
            'current_streak' => 0,
            'completion_rate' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('reading.quick-mark'), [
            'day_range' => '2-4',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('reading_progress', [
            'user_id' => $user->id,
            'daily_reading_id' => $days[3]->id,
        ]);
        $this->assertDatabaseCount('reading_progress', 3);
    }

    public function test_users_cannot_record_reading_progress_before_training_is_complete(): void
    {
        $user = User::factory()->create();
        $plan = $this->makePlan(ReadingPlan::TYPE_NEW_TESTAMENT, 'Training Cohort');
        $days = $this->makeReadings($plan, 3);

        TrainingResource::create([
            'reading_plan_id' => $plan->id,
            'title' => 'Welcome Video',
            'resource_type' => TrainingResource::TYPE_YOUTUBE,
            'resource_url' => 'https://www.youtube.com/watch?v=abc123xyz00',
            'sort_order' => 1,
        ]);

        $plan->refresh();
        $plan->syncScheduleDates();

        $user->readingPlans()->attach($plan->id, [
            'joined_date' => Carbon::today(),
            'current_day' => 1,
            'current_streak' => 0,
            'completion_rate' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('reading.quick-mark'), [
            'day_number' => 1,
        ]);

        $response->assertSessionHas('error', 'Complete all training resources and wait for the reading start date before recording reading progress.');
        $this->assertDatabaseMissing('reading_progress', [
            'user_id' => $user->id,
            'daily_reading_id' => $days[0]->id,
        ]);
    }

    public function test_progress_view_route_renders_without_explicit_plan_id(): void
    {
        $user = User::factory()->create();
        $plan = $this->makePlan(ReadingPlan::TYPE_NEW_TESTAMENT, 'Progress View Cohort');
        $this->makeReadings($plan, 3);

        $user->readingPlans()->attach($plan->id, [
            'joined_date' => Carbon::today(),
            'current_day' => 1,
            'current_streak' => 0,
            'completion_rate' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('progress.view'));

        $response->assertOk();
        $response->assertViewIs('reading-progress.view');
        $response->assertViewHas('planId', $plan->id);
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

    private function makeReadings(ReadingPlan $plan, int $count): array
    {
        $readings = [];

        for ($day = 1; $day <= $count; $day++) {
            $readings[] = DailyReading::create([
                'reading_plan_id' => $plan->id,
                'day_number' => $day,
                'book_start' => 'Matthew',
                'chapter_start' => ($day - 1) * 2 + 1,
                'book_end' => 'Matthew',
                'chapter_end' => ($day - 1) * 2 + 2,
                'is_break_day' => false,
            ]);
        }

        return $readings;
    }
}
