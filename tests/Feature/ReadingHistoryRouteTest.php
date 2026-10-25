<?php

namespace Tests\Feature;

use App\Livewire\ReadingHistory;
use App\Models\DailyReading;
use App\Models\ReadingPlan;
use App\Models\ReadingProgress;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReadingHistoryRouteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: ReadingPlan}
     */
    private function userOnAnActivePlan(): array
    {
        $plan = ReadingPlan::create([
            'name' => 'New Testament Sprint',
            'type' => 'new_testament',
            'description' => 'NT plan',
            'chapters_per_day' => 3,
            'streak_days' => 10,
            'break_days' => 1,
            'start_date' => Carbon::today()->subDays(5),
            'end_date' => Carbon::today()->addDays(30),
            'is_active' => true,
            'additional_info' => null,
        ]);

        foreach (range(1, 5) as $day) {
            DailyReading::create([
                'reading_plan_id' => $plan->id,
                'day_number' => $day,
                'book_start' => 'Matthew',
                'chapter_start' => $day,
                'book_end' => 'Matthew',
                'chapter_end' => $day + 2,
                'is_break_day' => false,
            ]);
        }

        $user = User::factory()->create();
        $user->readingPlans()->attach($plan->id, [
            'joined_date' => Carbon::today()->subDays(5),
            'current_day' => 5,
            'current_streak' => 5,
            'completion_rate' => 100,
            'is_active' => true,
        ]);

        return [$user, $plan];
    }

    public function test_reading_history_without_an_active_plan_redirects_instead_of_500(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/reading-history')
            ->assertRedirect(route('reading-plans.index'));
    }

    public function test_reading_history_renders_for_a_user_on_an_active_plan(): void
    {
        [$user] = $this->userOnAnActivePlan();

        $this->actingAs($user)
            ->get('/reading-history')
            ->assertOk()
            ->assertSee('Matthew 5-7');
    }

    public function test_searching_the_history_does_not_error(): void
    {
        [$user] = $this->userOnAnActivePlan();

        Livewire::actingAs($user)
            ->test(ReadingHistory::class)
            ->set('searchTerm', 'Matthew')
            ->assertHasNoErrors()
            ->assertCount('readingHistory', 5)
            ->set('searchTerm', 'Revelation')
            ->assertCount('readingHistory', 0);
    }

    public function test_history_survives_a_filter_change(): void
    {
        [$user, $plan] = $this->userOnAnActivePlan();

        $reading = DailyReading::where('reading_plan_id', $plan->id)
            ->where('day_number', 3)
            ->firstOrFail();

        ReadingProgress::create([
            'user_id' => $user->id,
            'reading_plan_id' => $plan->id,
            'daily_reading_id' => $reading->id,
            'completed_date' => Carbon::today()->subDays(2),
        ]);

        // The component keeps the plan in a public property, so the pivot has to
        // survive hydration for current_day to stay correct across updates.
        Livewire::actingAs($user)
            ->test(ReadingHistory::class)
            ->assertCount('readingHistory', 5)
            ->set('filterCompleted', 'completed')
            ->assertCount('readingHistory', 1)
            ->set('filterCompleted', 'all')
            ->assertCount('readingHistory', 5);
    }
}
