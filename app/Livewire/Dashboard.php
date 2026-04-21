<?php

namespace App\Livewire;

use App\Models\DailyReading;
use App\Models\GroupMessage;
use App\Models\ReadingPlan;
use App\Models\ReadingProgress;
use App\Models\TrainingCompletion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public $readingPlan;

    public $userPlan;

    public $todayReading;

    public $todayChapters = [];

    public $completedToday = false;

    public $readingHistory = [];

    public $groupMessages = [];

    public $calendarDays = [];

    public $viewingDay;

    public $viewingReading;

    public $nearbyDays = [];

    public $selectedDay;

    public $selectedReading;

    public $showCatchUpModal = false;

    public $viewingChapters = [];

    public $viewingCompleted = false;

    public $missedReadings = [];

    public $readingPlanId;

    public $userId;

    public $calendarYear;

    public $calendarMonth;

    public $trainingResources = [];

    public $trainingComplete = true;

    public $isTrainingStage = false;

    public $trainingDayNumber = 0;

    public $readingUnlocked = true;

    public function mount()
    {
        $user = Auth::user();
        $this->userId = $user->id;

        $today = Carbon::today();
        $this->calendarYear = $today->year;
        $this->calendarMonth = $today->month;

        return $this->refreshDashboardState($user);
    }

    protected function refreshDashboardState($user)
    {
        $this->userPlan = $user->readingPlans()
            ->where('user_reading_plans.is_active', true)
            ->first();

        if (! $this->userPlan) {
            session()->flash('error', 'No active reading plan found. Please join a reading plan first.');

            return redirect()->route('reading-plans.index');
        }

        if (! $this->userPlan->pivot) {
            session()->flash('error', 'Reading plan data is corrupted. Please rejoin the reading plan.');

            return redirect()->route('reading-plans.index');
        }

        $this->readingPlan = ReadingPlan::with('trainingResources')->find($this->userPlan->id);
        $this->readingPlanId = $this->userPlan->id;

        if (! $this->readingPlan) {
            session()->flash('error', 'Reading plan not found.');

            return redirect()->route('reading-plans.index');
        }

        $this->loadTrainingState($user);
        $this->syncCurrentDay($user);
        $this->loadCurrentReadingState($user);
        $this->loadNearbyDays();
        $this->loadReadingHistory();
        $this->loadGroupMessages();
        $this->generateCalendarDays();
    }

    protected function loadTrainingState($user): void
    {
        $resources = $this->readingPlan->trainingResources()->get();
        $completedResources = TrainingCompletion::where('user_id', $user->id)
            ->whereIn('training_resource_id', $resources->pluck('id'))
            ->get()
            ->keyBy('training_resource_id');

        $this->trainingResources = $resources->values()->map(function ($resource, $index) use ($completedResources) {
            $completion = $completedResources->get($resource->id);

            return [
                'id' => $resource->id,
                'title' => $resource->title,
                'description' => $resource->description,
                'type_label' => $resource->type_label,
                'resource_type' => $resource->resource_type,
                'resource_link' => $resource->resource_link,
                'video_link' => $resource->video_link,
                'document_link' => $resource->document_link,
                'day_number' => $index + 1,
                'completed' => $completion !== null,
                'completed_at' => $completion?->completed_at,
            ];
        })->all();

        $this->trainingComplete = $this->readingPlan->isTrainingCompleteFor($user);
        $this->readingUnlocked = $this->readingPlan->canRecordReadings($user, Carbon::today());
        $this->isTrainingStage = ! empty($this->trainingResources) && ! $this->readingUnlocked;
        $this->trainingDayNumber = 0;

        if ($this->readingPlan->training_days > 0 && $this->readingPlan->start_date && $this->readingPlan->reading_start_date) {
            $today = Carbon::today();
            if ($today->gte($this->readingPlan->start_date) && $today->lt($this->readingPlan->reading_start_date)) {
                $this->trainingDayNumber = min(
                    $this->readingPlan->training_days,
                    $this->readingPlan->start_date->diffInDays($today) + 1
                );
            }
        }
    }

    protected function syncCurrentDay($user): void
    {
        $calculatedCurrentDay = $this->readingPlan->expectedCurrentDay(Carbon::today());
        $maxDayNumber = (int) DailyReading::where('reading_plan_id', $this->readingPlan->id)->max('day_number');

        if ($maxDayNumber > 0) {
            $calculatedCurrentDay = min($calculatedCurrentDay, $maxDayNumber);
        }

        if ($calculatedCurrentDay !== (int) $this->userPlan->pivot->current_day) {
            $user->readingPlans()->updateExistingPivot($this->readingPlan->id, [
                'current_day' => $calculatedCurrentDay,
            ]);

            $this->userPlan = $user->readingPlans()
                ->where('user_reading_plans.is_active', true)
                ->first();
        }
    }

    protected function loadCurrentReadingState($user): void
    {
        $currentDay = $this->userPlan->pivot->current_day;

        $this->todayReading = DailyReading::where('reading_plan_id', $this->readingPlan->id)
            ->where('day_number', $currentDay)
            ->first();

        if ($this->todayReading && ! $this->todayReading->is_break_day) {
            $this->todayChapters = $this->todayReading->bibleChapters ?? collect();
        } else {
            $this->todayChapters = collect();
        }

        $this->completedToday = ReadingProgress::where('user_id', $user->id)
            ->where('daily_reading_id', $this->todayReading?->id)
            ->exists();

        $this->viewingDay = $this->userPlan->pivot->current_day;
        $this->viewingReading = $this->todayReading;
        $this->viewingChapters = $this->todayChapters;
        $this->viewingCompleted = $this->completedToday;
    }

    public function loadReadingHistory()
    {
        $user = Auth::user();
        $this->readingHistory = [];

        if (! $this->userPlan || ! $this->userPlan->pivot) {
            return;
        }

        $currentDay = $this->userPlan->pivot->current_day;
        $previousDays = min(5, $currentDay - 1);

        if ($previousDays <= 0) {
            return;
        }

        $startDay = $currentDay - $previousDays;
        $previousReadings = DailyReading::where('reading_plan_id', $this->readingPlan->id)
            ->whereBetween('day_number', [$startDay, $currentDay - 1])
            ->orderByDesc('day_number')
            ->get();

        $readingStartDate = $this->readingPlan->reading_start_date ?? $this->readingPlan->start_date;

        foreach ($previousReadings as $reading) {
            $progressRecord = ReadingProgress::where('user_id', $user->id)
                ->where('daily_reading_id', $reading->id)
                ->first();

            $actualReadingDate = $readingStartDate
                ? $readingStartDate->copy()->addDays($reading->day_number - 1)
                : null;

            $this->readingHistory[] = [
                'day' => $reading->day_number,
                'date' => $actualReadingDate?->format('M d') ?? 'TBD',
                'reading' => $reading->reading_range,
                'completed' => $progressRecord !== null,
                'completed_date' => $progressRecord?->completed_date,
            ];
        }
    }

    public function loadGroupMessages()
    {
        if (! $this->readingPlan) {
            $this->groupMessages = [];

            return;
        }

        $this->groupMessages = GroupMessage::where('reading_plan_id', $this->readingPlan->id)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();
    }

    public function generateCalendarDays()
    {
        if (! $this->userPlan || ! $this->userPlan->pivot || ! $this->readingPlan) {
            $this->calendarDays = [];

            return;
        }

        $user = Auth::user();
        $today = Carbon::today();
        $firstDayOfMonth = Carbon::create($this->calendarYear, $this->calendarMonth, 1)->startOfMonth();
        $lastDayOfMonth = Carbon::create($this->calendarYear, $this->calendarMonth, 1)->endOfMonth();
        $calendarStart = $firstDayOfMonth->copy()->startOfWeek();
        $calendarEnd = $lastDayOfMonth->copy()->endOfWeek();
        $days = [];
        $currentDate = $calendarStart->copy();
        $completedReadings = ReadingProgress::where('user_id', $user->id)
            ->where('reading_progress.reading_plan_id', $this->readingPlan->id)
            ->join('daily_readings', 'daily_readings.id', '=', 'reading_progress.daily_reading_id')
            ->pluck('daily_readings.day_number')
            ->toArray();
        $planStartDate = $this->readingPlan->reading_start_date ?? $this->readingPlan->start_date;

        while ($currentDate <= $calendarEnd) {
            $isCurrentMonth = $currentDate->month === $firstDayOfMonth->month
                && $currentDate->year === $firstDayOfMonth->year;
            $isReadingDay = false;
            $isBreakDay = false;
            $isCompleted = false;
            $isMissed = false;
            $isFuture = $currentDate->isAfter($today);
            $isToday = $currentDate->isSameDay($today);

            if ($planStartDate) {
                $daysSincePlanStart = $planStartDate->diffInDays($currentDate, false);

                if ($daysSincePlanStart >= 0) {
                    $readingDayNumber = $daysSincePlanStart + 1;
                    $cycleLength = max($this->readingPlan->streak_days + $this->readingPlan->break_days, 1);
                    $cyclePosition = ($readingDayNumber - 1) % $cycleLength + 1;
                    $isReadingDay = $cyclePosition <= $this->readingPlan->streak_days;
                    $isBreakDay = ! $isReadingDay;

                    if ($isReadingDay) {
                        $isCompleted = in_array($readingDayNumber, $completedReadings);
                        if (! $isCompleted && ! $isFuture && ! $isToday) {
                            $isMissed = true;
                        }
                    }
                }
            }

            $days[] = [
                'date' => $currentDate->copy(),
                'day' => $currentDate->day,
                'is_current_month' => $isCurrentMonth,
                'is_reading_day' => $isReadingDay,
                'is_break_day' => $isBreakDay,
                'is_completed' => $isCompleted,
                'is_missed' => $isMissed,
                'is_future' => $isFuture,
                'is_today' => $isToday,
            ];

            $currentDate->addDay();
        }

        $this->calendarDays = $days;
    }

    public function previousMonth()
    {
        $date = Carbon::create($this->calendarYear, $this->calendarMonth, 1)->subMonth();
        $this->calendarYear = $date->year;
        $this->calendarMonth = $date->month;
        $this->generateCalendarDays();
    }

    public function nextMonth()
    {
        $date = Carbon::create($this->calendarYear, $this->calendarMonth, 1)->addMonth();
        $this->calendarYear = $date->year;
        $this->calendarMonth = $date->month;
        $this->generateCalendarDays();
    }

    public function markAsComplete()
    {
        $user = Auth::user();
        if (! $user || ! $this->todayReading) {
            session()->flash('error', 'Unable to mark as complete. Please refresh the page.');

            return;
        }

        $this->recordReadingCompletion($user, $this->todayReading, 'Today\'s reading marked as complete.');
    }

    public function markViewingDayAsComplete()
    {
        $user = Auth::user();
        if (! $user || ! $this->viewingReading) {
            session()->flash('error', 'Unable to mark this day as complete.');

            return;
        }

        $context = $this->viewingDay > $this->userPlan->pivot->current_day
            ? 'Read-ahead day'
            : 'Selected day';

        $this->recordReadingCompletion($user, $this->viewingReading, "{$context} {$this->viewingReading->day_number} marked as complete.");
    }

    public function markTrainingResourceComplete($resourceId)
    {
        $user = Auth::user();
        $resource = $this->readingPlan?->trainingResources()->find($resourceId);

        if (! $user || ! $resource) {
            session()->flash('error', 'Training resource not found.');

            return;
        }

        TrainingCompletion::firstOrCreate(
            [
                'training_resource_id' => $resource->id,
                'user_id' => $user->id,
            ],
            [
                'completed_at' => Carbon::now(),
            ]
        );

        session()->flash('success', $resource->title.' marked as complete.');
        $this->refreshDashboardState($user);
    }

    public function calculateNextBreakDays()
    {
        if (! $this->userPlan || ! $this->userPlan->pivot || ! $this->readingPlan || $this->readingPlan->break_days < 1) {
            return 0;
        }

        $currentDay = $this->userPlan->pivot->current_day;
        $cycleLength = $this->readingPlan->streak_days + $this->readingPlan->break_days;
        $cyclePosition = ($currentDay - 1) % $cycleLength + 1;

        if ($cyclePosition <= $this->readingPlan->streak_days) {
            return $this->readingPlan->streak_days - $cyclePosition + 1;
        }

        return 0;
    }

    public function loadNearbyDays()
    {
        if (! $this->readingPlan || ! $this->viewingDay) {
            $this->nearbyDays = [];

            return;
        }

        $currentDay = $this->viewingDay;
        $startDay = max(1, $currentDay - 5);
        $endDay = $currentDay + 5;

        $this->nearbyDays = DailyReading::where('reading_plan_id', $this->readingPlan->id)
            ->whereBetween('day_number', [$startDay, $endDay])
            ->orderBy('day_number')
            ->get()
            ->map(function ($reading) use ($currentDay) {
                $user = Auth::user();
                $completed = ReadingProgress::where('user_id', $user->id)
                    ->where('daily_reading_id', $reading->id)
                    ->exists();

                return [
                    'id' => $reading->id,
                    'day' => $reading->day_number,
                    'reading' => $reading->reading_range,
                    'is_break_day' => $reading->is_break_day,
                    'is_current' => $reading->day_number == $currentDay,
                    'completed' => $completed,
                    'is_today' => $this->userPlan && $this->userPlan->pivot
                        ? $reading->day_number == $this->userPlan->pivot->current_day
                        : false,
                ];
            })
            ->toArray();
    }

    public function viewDay($dayNumber)
    {
        if (! $this->readingPlan || ! $this->userPlan || ! $this->userPlan->pivot) {
            session()->flash('error', 'Unable to navigate to day. Please refresh the page.');

            return;
        }

        $this->viewingDay = $dayNumber;
        $this->viewingReading = DailyReading::where('reading_plan_id', $this->readingPlanId)
            ->where('day_number', $dayNumber)
            ->first();

        if ($this->viewingReading && ! $this->viewingReading->is_break_day) {
            $this->viewingChapters = $this->viewingReading->bibleChapters ?? collect();
        } else {
            $this->viewingChapters = collect();
        }

        $user = Auth::user();
        $this->viewingCompleted = ReadingProgress::where('user_id', $user->id)
            ->where('daily_reading_id', $this->viewingReading?->id)
            ->exists();

        if (
            $dayNumber < $this->userPlan->pivot->current_day
            && ! $this->viewingCompleted
            && $this->viewingReading
            && ! $this->viewingReading->is_break_day
        ) {
            $this->selectedReading = $this->viewingReading;
            $this->selectedDay = $dayNumber;
        } else {
            $this->selectedReading = null;
            $this->selectedDay = null;
        }

        $this->loadNearbyDays();
    }

    public function resetToToday()
    {
        if (! $this->userPlan || ! $this->userPlan->pivot) {
            return;
        }

        $this->viewingDay = $this->userPlan->pivot->current_day;
        $this->viewingReading = $this->todayReading;
        $this->viewingChapters = $this->todayChapters;
        $this->viewingCompleted = $this->completedToday;
        $this->loadNearbyDays();
    }

    public function showCatchUpOptions()
    {
        if (! $this->userPlan || ! $this->userPlan->pivot || ! $this->readingPlan) {
            session()->flash('error', 'Unable to load catch-up options. Please refresh the page.');

            return;
        }

        if (! $this->readingPlan->canRecordReadings(Auth::user(), Carbon::today())) {
            session()->flash('error', 'Complete training before using catch-up tools.');

            return;
        }

        $user = Auth::user();
        $currentDay = $this->userPlan->pivot->current_day;
        $startDay = max(1, $currentDay - 10);

        $missedReadings = DailyReading::where('reading_plan_id', $this->readingPlanId)
            ->whereBetween('day_number', [$startDay, $currentDay - 1])
            ->where('is_break_day', false)
            ->orderByDesc('day_number')
            ->get()
            ->filter(function ($reading) use ($user) {
                return ! ReadingProgress::where('user_id', $user->id)
                    ->where('daily_reading_id', $reading->id)
                    ->exists();
            });

        if ($missedReadings->isEmpty()) {
            session()->flash('message', 'You have no missed readings to catch up on!');

            return;
        }

        $this->missedReadings = $missedReadings;
        $this->showCatchUpModal = true;
    }

    public function selectReading($readingId)
    {
        $this->selectedReading = DailyReading::find($readingId);
        $this->selectedDay = $this->selectedReading ? $this->selectedReading->day_number : null;
    }

    public function markPreviousAsComplete()
    {
        if (! $this->selectedReading || ! $this->userPlan || ! $this->readingPlan) {
            session()->flash('error', 'Unable to mark reading as complete.');

            return;
        }

        $user = Auth::user();
        $this->recordReadingCompletion($user, $this->selectedReading, 'Day '.$this->selectedReading->day_number.' marked as complete.');
        $this->showCatchUpModal = false;
        $this->selectedReading = null;
        $this->selectedDay = null;
    }

    protected function recordReadingCompletion($user, DailyReading $reading, string $successMessage): void
    {
        if (! $this->readingPlan->canRecordReadings($user, Carbon::today())) {
            session()->flash('error', 'Complete all training resources and wait for the reading start date before recording reading progress.');

            return;
        }

        if ($reading->is_break_day) {
            session()->flash('error', 'Break days cannot be marked as complete.');

            return;
        }

        $alreadyCompleted = ReadingProgress::where('user_id', $user->id)
            ->where('daily_reading_id', $reading->id)
            ->exists();

        if ($alreadyCompleted) {
            session()->flash('message', 'This reading was already marked as complete.');

            return;
        }

        ReadingProgress::create([
            'user_id' => $user->id,
            'reading_plan_id' => $this->readingPlan->id,
            'daily_reading_id' => $reading->id,
            'completed_date' => Carbon::today(),
        ]);

        $this->recomputeAndUpdatePivotStats($user, $this->readingPlan->id);
        session()->flash('message', $successMessage);
        $this->refreshDashboardState($user);
    }

    /**
     * Recompute current streak and completion rate and update pivot.
     */
    protected function recomputeAndUpdatePivotStats($user, $planId)
    {
        $userPlan = $user->readingPlans()->where('reading_plan_id', $planId)->first();
        if (! $userPlan || ! $userPlan->pivot) {
            return;
        }

        $currentDay = $userPlan->pivot->current_day;
        $completedDayNumbers = ReadingProgress::where('user_id', $user->id)
            ->where('reading_progress.reading_plan_id', $planId)
            ->join('daily_readings', 'daily_readings.id', '=', 'reading_progress.daily_reading_id')
            ->pluck('daily_readings.day_number')
            ->toArray();
        $daysMap = DailyReading::where('reading_plan_id', $planId)
            ->where('day_number', '<=', $currentDay)
            ->orderBy('day_number')
            ->get(['day_number', 'is_break_day'])
            ->keyBy('day_number');
        $readingDaysSoFar = $daysMap->filter(fn ($day) => ! $day->is_break_day)->count();
        $completedReadingDays = collect($completedDayNumbers)
            ->filter(fn ($dayNumber) => isset($daysMap[$dayNumber]) && ! $daysMap[$dayNumber]->is_break_day)
            ->count();
        $completionRate = $readingDaysSoFar > 0 ? ($completedReadingDays / $readingDaysSoFar) * 100 : 0;

        $streak = 0;
        for ($dayNumber = $currentDay; $dayNumber >= 1; $dayNumber--) {
            $day = $daysMap->get($dayNumber);
            if (! $day) {
                break;
            }
            if ($day->is_break_day) {
                continue;
            }
            if (in_array($dayNumber, $completedDayNumbers)) {
                $streak++;
            } else {
                break;
            }
        }

        $user->readingPlans()->updateExistingPivot($planId, [
            'current_streak' => $streak,
            'completion_rate' => $completionRate,
        ]);
    }

    public function closeCatchUpModal()
    {
        $this->showCatchUpModal = false;
        $this->selectedReading = null;
        $this->selectedDay = null;
    }

    public function render()
    {
        if (! $this->userPlan || ! $this->userPlan->pivot) {
            return view('livewire.dashboard', [
                'readingPlan' => null,
                'userPlan' => null,
                'todayReading' => null,
                'todayChapters' => collect(),
                'completedToday' => false,
                'readingHistory' => [],
                'groupMessages' => [],
                'calendarDays' => [],
                'nextBreakDays' => 0,
                'trainingResources' => [],
                'trainingComplete' => true,
                'isTrainingStage' => false,
                'trainingDayNumber' => 0,
                'readingUnlocked' => false,
            ]);
        }

        return view('livewire.dashboard', [
            'readingPlan' => $this->readingPlan,
            'userPlan' => $this->userPlan,
            'todayReading' => $this->todayReading,
            'todayChapters' => $this->todayChapters ?? collect(),
            'completedToday' => $this->completedToday,
            'readingHistory' => $this->readingHistory,
            'groupMessages' => $this->groupMessages,
            'calendarDays' => $this->calendarDays,
            'nextBreakDays' => $this->calculateNextBreakDays(),
            'trainingResources' => $this->trainingResources,
            'trainingComplete' => $this->trainingComplete,
            'isTrainingStage' => $this->isTrainingStage,
            'trainingDayNumber' => $this->trainingDayNumber,
            'readingUnlocked' => $this->readingUnlocked,
        ]);
    }
}
