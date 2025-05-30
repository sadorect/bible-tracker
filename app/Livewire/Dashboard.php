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
    public $showQuickMarkModal = false;
    public $quickMarkDay = '';
    public $quickMarkError = '';
    public $quickMarkSuccess = '';
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
        
        // Generate calendar days for current month
        $this->generateCalendarDays();
    }
    
    public function showQuickMarkForm()
    { 
        logger('showQuickMarkForm called - before setting modal to true');
        $this->showQuickMarkModal = true;
        logger('showQuickMarkModal is now: ' . ($this->showQuickMarkModal ? 'true' : 'false'));
        $this->quickMarkDay = '';
        $this->quickMarkError = '';
        // Force a refresh
    // Dispatch an event to force update
    $this->dispatch('show-quick-mark-modal');
    $this->dispatch('modal-opened');
        logger('showQuickMarkForm called - after setting modal to true');
    }
    
    public function closeQuickMarkModal()
    {
        $this->showQuickMarkModal = false;
        $this->quickMarkDay = '';
        $this->quickMarkError = '';
    }
    
    public function quickMarkComplete()
    {
        $this->quickMarkError = '';
        
        // Validate input
        if (empty($this->quickMarkDay) || !is_numeric($this->quickMarkDay)) {
            $this->quickMarkError = 'Please enter a valid day number.';
            return;
        }
        
        $dayNumber = (int) $this->quickMarkDay;
        
        // Check if day is valid (not future, within plan range)
        if ($dayNumber > $this->userPlan->pivot->current_day) {
            $this->quickMarkError = 'Cannot mark future days as complete.';
            return;
        }
        
        if ($dayNumber < 1) {
            $this->quickMarkError = 'Day number must be 1 or greater.';
            return;
        }
        
        // Find the daily reading for this day
        $dailyReading = DailyReading::where('reading_plan_id', $this->readingPlan->id)
            ->where('day_number', $dayNumber)
            ->first();
            
        if (!$dailyReading) {
            $this->quickMarkError = "Day {$dayNumber} not found in this reading plan.";
            return;
        }
        
        if ($dailyReading->is_break_day) {
            $this->quickMarkError = "Day {$dayNumber} is a break day - no reading to complete.";
            return;
        }
        
        $user = Auth::user();
        
        // Check if already completed
        $alreadyCompleted = ReadingProgress::where('user_id', $user->id)
            ->where('daily_reading_id', $dailyReading->id)
            ->exists();
            
        if ($alreadyCompleted) {
            $this->quickMarkError = "Day {$dayNumber} is already marked as complete.";
            return;
        }
        
        // Mark as complete
        ReadingProgress::create([
            'user_id' => $user->id,
            'reading_plan_id' => $this->readingPlan->id,
            'daily_reading_id' => $dailyReading->id,
            'completed_date' => Carbon::today(),
        ]);
        
        // Update completion rate
        if ($this->userPlan->pivot) {
            $completedDays = ReadingProgress::where('user_id', $user->id)
                ->where('reading_plan_id', $this->readingPlan->id)
                ->count();
            
            $totalDays = $this->userPlan->pivot->current_day;
            $completionRate = $totalDays > 0 ? ($completedDays / $totalDays) * 100 : 0;
            
            $user->readingPlans()->updateExistingPivot($this->readingPlan->id, [
                'completion_rate' => $completionRate,
            ]);
        }
        
        session()->flash('message', "Day {$dayNumber} ({$dailyReading->reading_range}) marked as complete!");
        $this->closeQuickMarkModal();
        
        // Refresh component data
        $this->mount();
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
        $firstDayOfMonth = Carbon::today()->startOfMonth();
        $lastDayOfMonth = Carbon::today()->endOfMonth();
        
        // Get start of calendar grid (might be in previous month)
        $calendarStart = $firstDayOfMonth->copy()->startOfWeek();
        
        // Get end of calendar grid (might be in next month)
        $calendarEnd = $lastDayOfMonth->copy()->endOfWeek();
        
        // Calculate days between
        $days = [];
        $currentDate = $calendarStart->copy();
        
        // Get all user's completed readings for efficiency
        $completedReadings = ReadingProgress::where('user_id', $user->id)
            ->where('reading_progress.reading_plan_id', $this->readingPlan->id)
            ->join('daily_readings', 'daily_readings.id', '=', 'reading_progress.daily_reading_id')
            ->pluck('daily_readings.day_number')
            ->toArray();
        
        while ($currentDate <= $calendarEnd) {
            $dayNumber = $currentDate->day;
            $isCurrentMonth = $currentDate->month === $today->month;
            
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

        public function markAsComplete()
    {
        $user = Auth::user();
        if (!$user || !$this->userPlan || !$this->userPlan->pivot || !$this->todayReading) {
            session()->flash('error', 'Unable to mark as complete. Please refresh the page.');
            return;
        }
        
        $user = Auth::user();
        
        // Check if already completed
        if ($this->completedToday) {
            return;
        }
        
        // Create progress record
        ReadingProgress::create([
            'user_id' => $user->id,
            'reading_plan_id' => $this->readingPlan->id,
            'daily_reading_id' => $this->todayReading->id,
            'completed_date' => Carbon::today(),
        ]);
        
        // Update user plan statistics
        $userPlan = $user->readingPlans()->where('reading_plan_id', $this->readingPlan->id)->first();
        
        if ($userPlan && $userPlan->pivot) {
            // Update streak
            $currentStreak = $userPlan->pivot->current_streak + 1;
            
            // Calculate completion rate
            $totalDays = $this->todayReading->day_number;
            $completedDays = ReadingProgress::where('user_id', $user->id)
                ->where('reading_plan_id', $this->readingPlan->id)
                ->count();
            $completionRate = ($completedDays / $totalDays) * 100;
            
            // Update pivot record
            $user->readingPlans()->updateExistingPivot($this->readingPlan->id, [
                'current_streak' => $currentStreak,
                'completion_rate' => $completionRate,
            ]);
        }
        
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
        
        // Update user plan statistics if pivot exists
        if ($this->userPlan->pivot) {
            $completedDays = ReadingProgress::where('user_id', $user->id)
                ->where('reading_plan_id', $this->readingPlan->id)
                ->count();
            
            $totalDays = $this->userPlan->pivot->current_day;
            $completionRate = $totalDays > 0 ? ($completedDays / $totalDays) * 100 : 0;
            
            // Update pivot record
            $user->readingPlans()->updateExistingPivot($this->readingPlan->id, [
                'completion_rate' => $completionRate,
            ]);
        }
        
        session()->flash('message', 'Day ' . $this->selectedReading->day_number . ' marked as complete!');
        $this->showCatchUpModal = false;
        $this->selectedReading = null;
        $this->selectedDay = null;
        
        // Refresh the component state
        $this->mount();
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
