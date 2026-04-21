<?php

namespace Tests\Feature;

use App\Models\ReadingPlan;
use App\Models\TrainingResource;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_testament_defaults_follow_ten_day_cycle_with_refresh_breaks(): void
    {
        $defaults = ReadingPlan::defaultsFor(ReadingPlan::TYPE_NEW_TESTAMENT);

        $this->assertSame(10, $defaults['streak_days']);
        $this->assertSame(1, $defaults['break_days']);
    }

    public function test_sync_schedule_dates_accounts_for_breaks_and_training_days(): void
    {
        $plan = ReadingPlan::create([
            'name' => 'New Testament Cohort',
            'type' => ReadingPlan::TYPE_NEW_TESTAMENT,
            'description' => 'Plan with training and refresh breaks.',
            'chapters_per_day' => 9,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => Carbon::create(2026, 4, 1),
            'end_date' => null,
            'is_active' => true,
        ]);

        TrainingResource::create([
            'reading_plan_id' => $plan->id,
            'title' => 'Orientation',
            'resource_type' => TrainingResource::TYPE_YOUTUBE,
            'resource_url' => 'https://www.youtube.com/watch?v=abc123xyz00',
            'sort_order' => 1,
        ]);

        TrainingResource::create([
            'reading_plan_id' => $plan->id,
            'title' => 'Prayer Guide',
            'resource_type' => TrainingResource::TYPE_YOUTUBE,
            'resource_url' => 'https://www.youtube.com/watch?v=abc123xyz01',
            'sort_order' => 2,
        ]);

        $plan->refresh();
        $plan->syncScheduleDates();

        $this->assertSame(31, $plan->recommended_total_scheduled_days);
        $this->assertTrue($plan->end_date->isSameDay(Carbon::create(2026, 5, 3)));
    }
}
