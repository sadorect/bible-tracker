<?php

namespace App\Livewire;

use App\Models\DailyReading;
use App\Models\ReadingPlan;
use App\Models\ReadingProgress;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ReadingHistory extends Component
{
    public $readingHistory = [];

    public $filterCompleted = 'all'; // 'all', 'completed', 'missed'

    public $searchTerm = '';

    public $userPlan;

    /**
     * Pivot data does not survive Livewire hydration, so the plan's current day
     * is captured as a scalar at mount and used from then on. Reading it off
     * $userPlan->pivot silently yielded 0 after any update, emptying the page.
     */
    public int $currentDay = 0;

    public $participationSummary = [];

    public function mount()
    {
        $this->loadUserPlan();

        // Redirect from mount, not render(). Livewire calls ->render() on
        // whatever render() returns, and a Redirector has no such method, so
        // bouncing from there raised a BadMethodCallException (500) for every
        // user without an active plan.
        if (! $this->userPlan) {
            return $this->redirectRoute('reading-plans.index', navigate: false);
        }

        $this->loadReadingHistory();
    }

    public function updatedFilterCompleted()
    {
        $this->loadReadingHistory();
    }

    public function updatedSearchTerm()
    {
        $this->loadReadingHistory();
    }

    protected function loadUserPlan()
    {
        $user = Auth::user();

        // Get user's active reading plan
        $this->userPlan = $user->readingPlans()
            ->where('user_reading_plans.is_active', true)
            ->first();

        $this->currentDay = (int) ($this->userPlan?->pivot?->current_day ?? 0);
    }

    public function loadReadingHistory()
    {
        if (! $this->userPlan) {
            $this->readingHistory = [];
            $this->participationSummary = [];

            return;
        }

        $user = Auth::user();

        // Get all daily readings for the user's active plan up to current day
        $query = DailyReading::where('reading_plan_id', $this->userPlan->id)
            ->where('day_number', '<=', $this->currentDay)
            ->orderBy('day_number', 'desc');

        // Apply search filter. Only real columns here: reading_range is an
        // accessor, and referencing it made MySQL raise "Unknown column".
        if ($this->searchTerm) {
            $term = '%'.$this->searchTerm.'%';

            $query->where(function ($q) use ($term) {
                $q->where('book_start', 'like', $term)
                    ->orWhere('book_end', 'like', $term);
            });
        }

        $dailyReadings = $query->get();

        // Get the reading plan to access the start date
        $readingPlan = ReadingPlan::find($this->userPlan->id);
        $planStartDate = Carbon::parse($readingPlan->reading_start_date ?? $readingPlan->start_date);

        // Get all completed readings for this user and plan
        $completedReadings = ReadingProgress::where('user_id', $user->id)
            ->where('reading_plan_id', $this->userPlan->id)
            ->when($user->currentParticipationIdForPlan($this->userPlan->id), fn ($query, $participationId) => $query->where('reading_plan_participation_id', $participationId))
            ->get()
            ->keyBy('daily_reading_id');

        $this->readingHistory = $dailyReadings->map(function ($reading) use ($completedReadings, $planStartDate) {
            $progress = $completedReadings->get($reading->id);
            $completed = $progress !== null;

            // Filter based on completion status
            if ($this->filterCompleted === 'completed' && ! $completed) {
                return null;
            }
            if ($this->filterCompleted === 'missed' && ($completed || $reading->is_break_day)) {
                return null;
            }

            // Calculate the ACTUAL date for this reading day
            // Day 1 = start_date, Day 2 = start_date + 1 day, etc.
            $actualReadingDate = $planStartDate->copy()->addDays($reading->day_number - 1);

            return [
                'id' => $reading->id,
                'day' => $reading->day_number,
                'date' => $actualReadingDate->format('M d, Y'), // This is the actual assigned reading date
                'reading' => $reading->reading_range,
                'is_break_day' => $reading->is_break_day,
                'completed' => $completed,
                'completed_date' => $progress ? Carbon::parse($progress->completed_date)->format('M d, Y g:i A') : null, // This is when it was actually completed
                'actual_reading_date' => $actualReadingDate, // Store for potential sorting/filtering
            ];
        })->filter()->values()->toArray();

        $currentParticipationId = $user->currentParticipationIdForPlan($this->userPlan->id);
        $participations = $user->readingPlanParticipations()
            ->where('reading_plan_id', $this->userPlan->id)
            ->latest('participation_number')
            ->get();
        $currentParticipation = $participations->firstWhere('id', $currentParticipationId);
        $completedCount = collect($this->readingHistory)->where('completed', true)->count();
        $missedCount = collect($this->readingHistory)->where('completed', false)->where('is_break_day', false)->count();
        $totalReadingDays = collect($this->readingHistory)->where('is_break_day', false)->count();

        $this->participationSummary = [
            'cycle_number' => $currentParticipation?->participation_number,
            'status' => $currentParticipation?->status,
            'started_on' => $currentParticipation?->started_on,
            'last_completed_on' => $completedReadings->max('completed_date'),
            'completed_count' => $completedCount,
            'missed_count' => $missedCount,
            'completion_rate' => $totalReadingDays > 0 ? round(($completedCount / $totalReadingDays) * 100) : 0,
            'total_cycles' => $participations->count(),
            'best_cycle' => $participations
                ->map(function ($participation) use ($user) {
                    $completedDays = ReadingProgress::query()
                        ->where('user_id', $user->id)
                        ->where('reading_plan_id', $participation->reading_plan_id)
                        ->where('reading_plan_participation_id', $participation->id)
                        ->distinct('daily_reading_id')
                        ->count('daily_reading_id');

                    return [
                        'cycle_number' => $participation->participation_number,
                        'completed_days' => $completedDays,
                    ];
                })
                ->sortByDesc('completed_days')
                ->first(),
        ];
    }

    public function render()
    {
        // No plan-guard here: mount() already redirected. render() must always
        // return a view.
        return view('livewire.reading-history')->layout('layouts.app')
            ->with([
                'readingHistory' => $this->readingHistory,
                'userPlan' => $this->userPlan,
            ]);
    }
}
