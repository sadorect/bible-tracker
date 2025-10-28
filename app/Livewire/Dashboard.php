<?php

namespace App\Livewire;

use App\Models\DailyReading;
use App\Models\GroupMessage;
use App\Models\ReadingPlan;
use App\Models\ReadingProgress;
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
    protected $user;
    
    public function mount()
    {
        $user = Auth::user();
        $this->userId = $user->id;
        
        // Get the user's active reading plan (assuming one active plan per user)
        $this->userPlan = $user->readingPlans()
            ->where('user_reading_plans.is_active', true)
            ->first();
            
        if (!$this->userPlan) {
            session()->flash('error', 'No active reading plan found. Please join a reading plan first.');
            return redirect()->route('reading-plans.index');
        }
        
        // Ensure pivot data exists
        if (!$this->userPlan->pivot) {
            session()->flash('error', 'Reading plan data is corrupted. Please rejoin the reading plan.');
            return redirect()->route('reading-plans.index');
        }
        
        $this->readingPlan = ReadingPlan::find($this->userPlan->id);
        $this->readingPlanId = $this->userPlan->id;
        //$this->readingPlan = ReadingPlan::find($this->readingPlanId);

        if (!$this->readingPlan) {
            session()->flash('error', 'Reading plan not found.');
            return redirect()->route('reading-plans.index');
        }
        
        // Calculate the current day based on the plan's start date and today's date
        $startDate = Carbon::parse($this->readingPlan->start_date);
        $today = Carbon::today();
        $daysSinceStart = $startDate->diffInDays($today, false);
        
        // If the plan hasn't started yet, set current day to 1
        if ($today->lt($startDate)) {
            $calculatedCurrentDay = 1;
        } else {
            // Add 1 because the start date is day 1
            $calculatedCurrentDay = $daysSinceStart + 1;
        }
        
        // Get the maximum day number in the plan
        $maxDayNumber = DailyReading::where('reading_plan_id', $this->readingPlan->id)
            ->max('day_number');
        
        // Ensure we don't exceed the maximum day number
        $calculatedCurrentDay = min($calculatedCurrentDay, $maxDayNumber);
        
        // Update the user's current_day in the pivot table if it's different
        if ($calculatedCurrentDay != $this->userPlan->pivot->current_day) {
            $user->readingPlans()->updateExistingPivot($this->readingPlan->id, [
                'current_day' => $calculatedCurrentDay
            ]);
            
            // Refresh the userPlan with the updated current_day
            $this->userPlan = $user->readingPlans()
                ->where('user_reading_plans.is_active', true)
                ->first();
        }
        
        // Set properties for the component
        $this->readingPlanId = $this->readingPlan->id;
        
        // Get the current day's reading assignment
        $currentDay = $this->userPlan->pivot->current_day;
        $this->todayReading = DailyReading::where('reading_plan_id', $this->readingPlan->id)
            ->where('day_number', $currentDay)
            ->first();
            
        // Get the Bible chapters for today's reading
        if ($this->todayReading && !$this->todayReading->is_break_day) {
            $this->todayChapters = $this->todayReading->bibleChapters ?? collect();
        } else {
            $this->todayChapters = collect();
        }
            
        // Check if user has completed today's reading
        $this->completedToday = ReadingProgress::where('user_id', $user->id)
            ->where('daily_reading_id', $this->todayReading?->id)
            ->where('completed_date', Carbon::today())
            ->exists();
        
        // Initialize viewing day to current day
        $this->viewingDay = $this->userPlan->pivot->current_day;
        $this->viewingReading = $this->todayReading;
        $this->viewingChapters = $this->todayChapters;
        $this->viewingCompleted = $this->completedToday;
        
        // Load nearby days for navigation
        $this->loadNearbyDays();
        
        // Get reading history (last 5 days)
        $this->loadReadingHistory();
        
        // Get recent group messages
        $this->loadGroupMessages();
        
        // Initialize calendar month/year and generate calendar days
        $today = Carbon::today();
        $this->calendarYear = $today->year;
        $this->calendarMonth = $today->month;
        $this->generateCalendarDays();
    }
    
    protected function loadUserPlanData()
    {
        $user = Auth::user();
        $this->userPlan = $user->readingPlans()
            ->where('reading_plans.id', $this->readingPlanId)
            ->first();
        
        if (!$this->userPlan || !$this->userPlan->pivot) {
            session()->flash('error', 'Reading plan data not found.');
            return redirect()->route('reading-plans.index');
        }
        
        $this->readingPlan = ReadingPlan::find($this->readingPlanId);
        
        // Calculate the current day based on the plan's start date and today's date
        $startDate = Carbon::parse($this->readingPlan->start_date);
        $today = Carbon::today();
        
        // Calculate days since start (will be negative if start date is in the future)
        $daysSinceStart = $startDate->diffInDays($today, false);
        
        // If the plan hasn't started yet, set current day to 1
        if ($daysSinceStart < 0) {
            $calculatedCurrentDay = 1;
        } else {
            // Add 1 because the start date is day 1
            $calculatedCurrentDay = $daysSinceStart + 1;
        }
        
        // Get the maximum day number in the plan
        $maxDayNumber = DailyReading::where('reading_plan_id', $this->readingPlanId)
            ->max('day_number');
        
        // Ensure we don't exceed the maximum day number
        $calculatedCurrentDay = min($calculatedCurrentDay, $maxDayNumber);
        
        // Update the user's current_day in the pivot table if it's different
        if ($calculatedCurrentDay != $this->userPlan->pivot->current_day) {
            $user->readingPlans()->updateExistingPivot($this->readingPlanId, [
                'current_day' => $calculatedCurrentDay
            ]);
            
            // Refresh the userPlan with the updated current_day
            $this->userPlan = $user->readingPlans()
                ->where('reading_plans.id', $this->readingPlanId)
                ->first();
        }
    }

    public function loadReadingHistory()
    {
        $user = Auth::user();
        
        if (!$this->userPlan || !$this->userPlan->pivot) {
            $this->readingHistory = [];
            return;
        }
        
        $currentDay = $this->userPlan->pivot->current_day;
        
        // Get the last 5 days (or fewer if we're early in the plan)
        $previousDays = min(5, $currentDay - 1);
        
        if ($previousDays > 0) {
            $startDay = $currentDay - $previousDays;
            
            $previousReadings = DailyReading::where('reading_plan_id', $this->readingPlan->id)
                ->whereBetween('day_number', [$startDay, $currentDay - 1])
                ->orderByDesc('day_number')
                ->get();
                
            // Get the plan start date for correct date calculation
            $planStartDate = Carbon::parse($this->readingPlan->start_date);
                
            foreach ($previousReadings as $reading) {
                $completed = ReadingProgress::where('user_id', $user->id)
                    ->where('daily_reading_id', $reading->id)
                    ->exists();
                    
                $completedDate = null;
                if ($completed) {
                    $progressRecord = ReadingProgress::where('user_id', $user->id)
                        ->where('daily_reading_id', $reading->id)
                        ->first();
                    $completedDate = $progressRecord->completed_date;
                }
                
                // Calculate the ACTUAL date for this reading day
                $actualReadingDate = $planStartDate->copy()->addDays($reading->day_number - 1);
                
                $this->readingHistory[] = [
                    'day' => $reading->day_number,
                    'date' => $actualReadingDate->format('M d'),
                    'reading' => $reading->reading_range,
                    'completed' => $completed,
                    'completed_date' => $completedDate,
                ];
            }
        }
    }
    
    public function loadGroupMessages()
    {
        if (!$this->readingPlan) {
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
        if (!$this->userPlan || !$this->userPlan->pivot || !$this->readingPlan) {
            $this->calendarDays = [];
            return;
        }
        
        $user = Auth::user();
        $today = Carbon::today();
        $firstDayOfMonth = Carbon::create($this->calendarYear, $this->calendarMonth, 1)->startOfMonth();
        $lastDayOfMonth = Carbon::create($this->calendarYear, $this->calendarMonth, 1)->endOfMonth();
        
        // Get start of calendar grid (might be in previous month)
        $calendarStart = $firstDayOfMonth->copy()->startOfWeek();
        
        // Get end of calendar grid (might be in next month)
        $calendarEnd = $lastDayOfMonth->copy()->endOfWeek();
        
        // Calculate days between
        $days = [];
        $currentDate = $calendarStart->copy();
        
        // Get all user's completed readings for efficiency (set of day_numbers)
        $completedReadings = ReadingProgress::where('user_id', $user->id)
            ->where('reading_progress.reading_plan_id', $this->readingPlan->id)
            ->join('daily_readings', 'daily_readings.id', '=', 'reading_progress.daily_reading_id')
            ->pluck('daily_readings.day_number')
            ->toArray();
        
        while ($currentDate <= $calendarEnd) {
            $dayNumber = $currentDate->day;
            // Mark current-month cells relative to the displayed month/year, not today's month
            $isCurrentMonth = $currentDate->month === $firstDayOfMonth->month && $currentDate->year === $firstDayOfMonth->year;
            
            // Calculate if this day corresponds to a reading day
            $planStartDate = Carbon::parse($this->readingPlan->start_date);
            $daysSincePlanStart = $planStartDate->diffInDays($currentDate, false);
            
            $isReadingDay = false;
            $isBreakDay = false;
            $isCompleted = false;
            $isMissed = false;
            $isFuture = $currentDate->isAfter($today);
            $isToday = $currentDate->isSameDay($today);
            
            // Only calculate for days in or after the plan start
            if ($daysSincePlanStart >= 0) {
                // Calculate reading day number
                $readingDayNumber = $daysSincePlanStart + 1;
                
                // Check if it's a reading day or break day based on plan settings
                $cycleLength = $this->readingPlan->streak_days + $this->readingPlan->break_days;
                $cyclePosition = ($readingDayNumber - 1) % $cycleLength + 1;
                
                $isReadingDay = $cyclePosition <= $this->readingPlan->streak_days;
                $isBreakDay = !$isReadingDay;
                
                // Check if completed (for past days)
                if ($isReadingDay) {
                    $isCompleted = in_array($readingDayNumber, $completedReadings);
                    
                    // Mark as missed if it's a past reading day that wasn't completed
                    if (!$isCompleted && !$isFuture && !$isToday) {
                        $isMissed = true;
                    }
                }
            }
            
            $days[] = [
                'date' => $currentDate->copy(),
                'day' => $dayNumber,
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
        if (!$user || !$this->userPlan || !$this->userPlan->pivot || !$this->todayReading) {
            session()->flash('error', 'Unable to mark as complete. Please refresh the page.');
            return;
        }
        
        // Check if already completed
        if ($this->completedToday) {
            return;
        }
        
            // Prevent marking a break day
            if ($this->todayReading->is_break_day) {
                session()->flash('error', 'Today is a break day. There is no reading to complete.');
                return;
            }

        // Create progress record
        ReadingProgress::create([
            'user_id' => $user->id,
            'reading_plan_id' => $this->readingPlan->id,
            'daily_reading_id' => $this->todayReading->id,
            'completed_date' => Carbon::today(),
        ]);
        
            // Recompute and update pivot stats (streak + completion rate)
            $this->recomputeAndUpdatePivotStats($user, $this->readingPlan->id);
        
        // Refresh the component state
        $this->mount();
    }
    
    public function calculateNextBreakDays()
    {
        if (!$this->userPlan || !$this->userPlan->pivot || !$this->readingPlan) {
            return 0;
        }
        
        // Calculate days until next break
        $currentDay = $this->userPlan->pivot->current_day;
        $cycleLength = $this->readingPlan->streak_days + $this->readingPlan->break_days;
        $cyclePosition = ($currentDay - 1) % $cycleLength + 1;
        
        // If we're in a streak period, calculate days until next break
        if ($cyclePosition <= $this->readingPlan->streak_days) {
            return $this->readingPlan->streak_days - $cyclePosition + 1;
        }
        
        // If we're in a break period, return 0
        return 0;
    }
    
    public function loadNearbyDays()
    {
        if (!$this->readingPlan || !$this->viewingDay) {
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
                    'is_today' => $this->userPlan && $this->userPlan->pivot ? 
                        $reading->day_number == $this->userPlan->pivot->current_day : false,
                ];
            })
            ->toArray();
    }

    public function viewDay($dayNumber)
    {
        if (!$this->readingPlan || !$this->userPlan || !$this->userPlan->pivot) {
            session()->flash('error', 'Unable to navigate to day. Please refresh the page.');
            return;
        }
        
        $this->viewingDay = $dayNumber;
        $this->viewingReading = DailyReading::where('reading_plan_id', $this->readingPlanId)
            ->where('day_number', $dayNumber)
            ->first();

              // Fix: Add proper plan context validation
        if (!$this->readingPlan || !$this->userPlan) {
            $this->loadUserPlanData(); // Reload the plan data
        }
        
        $this->viewingReading = DailyReading::where('reading_plan_id', $this->readingPlanId)
            ->where('day_number', $dayNumber)
            ->first();
            
        if ($this->viewingReading && !$this->viewingReading->is_break_day) {
            $this->viewingChapters = $this->viewingReading->bibleChapters ?? collect();
        } else {
            $this->viewingChapters = collect();
        }
        
        $user = Auth::user();
        $this->viewingCompleted = ReadingProgress::where('user_id', $user->id)
            ->where('daily_reading_id', $this->viewingReading?->id)
            ->exists();
            
        // If viewing a previous day, set it as the selected reading for catch-up
        if ($dayNumber < $this->userPlan->pivot->current_day && !$this->viewingCompleted && $this->viewingReading && !$this->viewingReading->is_break_day) {
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
        if (!$this->userPlan || !$this->userPlan->pivot) {
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
        if (!$this->userPlan || !$this->userPlan->pivot || !$this->readingPlan) {
            session()->flash('error', 'Unable to load catch-up options. Please refresh the page.');
            return;
        }
        
        // Get missed readings from the past (limit to last 10 days)
        $user = Auth::user();
        $currentDay = $this->userPlan->pivot->current_day;
        $startDay = max(1, $currentDay - 10);
        
        $missedReadings = DailyReading::where('reading_plan_id', $this->readingPlanId)
            ->whereBetween('day_number', [$startDay, $currentDay - 1])
            ->where('is_break_day', false)
            ->orderByDesc('day_number')
            ->get()
            ->filter(function ($reading) use ($user) {
                // Filter out readings that are already completed
                return !ReadingProgress::where('user_id', $user->id)
                    ->where('daily_reading_id', $reading->id)
                    ->exists();
            });
        
        if ($missedReadings->isEmpty()) {
            // No missed readings to catch up on
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
        if (!$this->selectedReading || !$this->userPlan || !$this->readingPlan) {
            session()->flash('error', 'Unable to mark reading as complete.');
            return;
        }
        
        $user = Auth::user();
        
        // Prevent marking a break day
        if ($this->selectedReading->is_break_day) {
            session()->flash('error', 'Selected day is a break day and cannot be marked as complete.');
            $this->showCatchUpModal = false;
            return;
        }

        // Check if already completed
        $alreadyCompleted = ReadingProgress::where('user_id', $user->id)
            ->where('daily_reading_id', $this->selectedReading->id)
            ->exists();
            
        if ($alreadyCompleted) {
            session()->flash('message', 'This reading was already marked as complete.');
            $this->showCatchUpModal = false;
            return;
        }
        
        // Create progress record
        ReadingProgress::create([
            'user_id' => $user->id,
            'reading_plan_id' => $this->readingPlan->id,
            'daily_reading_id' => $this->selectedReading->id,
            'completed_date' => Carbon::today(),
        ]);
        
        // Recompute and update pivot stats (streak + completion rate)
        $this->recomputeAndUpdatePivotStats($user, $this->readingPlan->id);
        
        session()->flash('message', 'Day ' . $this->selectedReading->day_number . ' marked as complete!');
        $this->showCatchUpModal = false;
        $this->selectedReading = null;
        $this->selectedDay = null;
        
        // Refresh the component state
        $this->mount();
    }

    /**
     * Recompute current streak and completion rate and update pivot.
     */
    protected function recomputeAndUpdatePivotStats($user, $planId)
    {
        // Recalculate completion rate and plan-based streak (ignoring calendar dates)
        $userPlan = $user->readingPlans()->where('reading_plan_id', $planId)->first();
        if (!$userPlan || !$userPlan->pivot) {
            return;
        }

        $currentDay = $userPlan->pivot->current_day;
        // Completed day_numbers set
        $completedDayNumbers = ReadingProgress::where('user_id', $user->id)
            ->where('reading_progress.reading_plan_id', $planId)
            ->join('daily_readings', 'daily_readings.id', '=', 'reading_progress.daily_reading_id')
            ->pluck('daily_readings.day_number')
            ->toArray();

        // Map of day_number => is_break_day
        $daysMap = DailyReading::where('reading_plan_id', $planId)
            ->where('day_number', '<=', $currentDay)
            ->orderBy('day_number')
            ->get(['day_number', 'is_break_day'])
            ->keyBy('day_number');

        // Completion rate up to current day excluding break days
        $readingDaysSoFar = $daysMap->filter(fn($d) => !$d->is_break_day)->count();
        $completedReadingDays = collect($completedDayNumbers)
            ->filter(fn($dn) => isset($daysMap[$dn]) && !$daysMap[$dn]->is_break_day)
            ->count();
        $completionRate = $readingDaysSoFar > 0 ? ($completedReadingDays / $readingDaysSoFar) * 100 : 0;

        // Plan-based streak: count consecutive completed reading days backwards from current_day, skipping break days
        $streak = 0;
        for ($d = $currentDay; $d >= 1; $d--) {
            $day = $daysMap->get($d);
            if (!$day) { break; }
            if ($day->is_break_day) { continue; }
            if (in_array($d, $completedDayNumbers)) {
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
    /**
     * Close the catch-up modal and reset selected reading.
     */
    
    public function closeCatchUpModal()
    {
        $this->showCatchUpModal = false;
        $this->selectedReading = null;
        $this->selectedDay = null;
    }

    public function render()
    {
        // Ensure we have valid data before rendering
        if (!$this->userPlan || !$this->userPlan->pivot) {
            // Don't redirect here, just return a view with error state
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
        ]);
        }
        
        $nextBreakDays = $this->calculateNextBreakDays();
        
        return view('livewire.dashboard', [
            'readingPlan' => $this->readingPlan,
            'userPlan' => $this->userPlan,
            'todayReading' => $this->todayReading,
            'todayChapters' => $this->todayChapters ?? collect(),
            'completedToday' => $this->completedToday,
            'readingHistory' => $this->readingHistory,
            'groupMessages' => $this->groupMessages,
            'calendarDays' => $this->calendarDays,
            'nextBreakDays' => $nextBreakDays,
        ]);
    }
}
