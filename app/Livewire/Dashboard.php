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
    public $completedToday = false;
    public $readingHistory = [];
    public $groupMessages = [];
    public $calendarDays = [];
    
    public function mount()
    {
        $user = Auth::user();
        
        // Get the user's active reading plan (assuming one active plan per user)
        $this->userPlan = $user->readingPlans()
            ->where('user_reading_plans.is_active', true)
            ->first();
            
        if (!$this->userPlan) {
            return redirect()->route('reading-plans.index');
        }
        
        $this->readingPlan = ReadingPlan::find($this->userPlan->id);
        
        // Get the current day's reading assignment
        $currentDay = $this->userPlan->pivot->current_day;
        $this->todayReading = DailyReading::where('reading_plan_id', $this->readingPlan->id)
            ->where('day_number', $currentDay)
            ->first();
            
        // Check if user has completed today's reading
        $this->completedToday = ReadingProgress::where('user_id', $user->id)
            ->where('daily_reading_id', $this->todayReading?->id)
            ->where('completed_date', Carbon::today())
            ->exists();
            
        // Get reading history (last 5 days)
        $this->loadReadingHistory();
        
        // Get recent group messages
        $this->loadGroupMessages();
        
        // Generate calendar days for current month
        $this->generateCalendarDays();
    }
    
    public function loadReadingHistory()
    {
        $user = Auth::user();
        $currentDay = $this->userPlan->pivot->current_day;
        
        // Get the last 5 days (or fewer if we're early in the plan)
        $previousDays = min(5, $currentDay - 1);
        
        if ($previousDays > 0) {
            $startDay = $currentDay - $previousDays;
            
            $previousReadings = DailyReading::where('reading_plan_id', $this->readingPlan->id)
                ->whereBetween('day_number', [$startDay, $currentDay - 1])
                ->orderByDesc('day_number')
                ->get();
                
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
                
                $this->readingHistory[] = [
                    'day' => $reading->day_number,
                    'date' => Carbon::parse($this->readingPlan->start_date)->addDays($reading->day_number - 1)->format('M d'),
                    'reading' => $reading->reading_range,
                    'completed' => $completed,
                    'completed_date' => $completedDate,
                ];
            }
        }
    }
    
    public function loadGroupMessages()
    {
        $this->groupMessages = GroupMessage::where('reading_plan_id', $this->readingPlan->id)
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();
    }
    
    public function generateCalendarDays()
    {
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
        
        while ($currentDate <= $calendarEnd) {
            $dayNumber = $currentDate->day;
            $isCurrentMonth = $currentDate->month === $today->month;
            
            // Calculate if this day corresponds to a reading day
            $planStartDate = Carbon::parse($this->readingPlan->start_date);
            $daysSincePlanStart = $currentDate->diffInDays($planStartDate, false);
            
            $isReadingDay = false;
            $isBreakDay = false;
            $isCompleted = false;
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
                if (!$isFuture && $isReadingDay) {
                    $dailyReading = DailyReading::where('reading_plan_id', $this->readingPlan->id)
                        ->where('day_number', $readingDayNumber)
                        ->first();
                        
                    if ($dailyReading) {
                        $isCompleted = ReadingProgress::where('user_id', $user->id)
                            ->where('daily_reading_id', $dailyReading->id)
                            ->exists();
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
        $userPlan = $user->reading_plans()->where('reading_plan_id', $this->readingPlan->id)->first();
        
        // Update streak
        $currentStreak = $userPlan->pivot->current_streak + 1;
        
        // Calculate completion rate
        $totalDays = $this->todayReading->day_number;
        $completedDays = ReadingProgress::where('user_id', $user->id)
            ->where('reading_plan_id', $this->readingPlan->id)
            ->count();
        $completionRate = ($completedDays / $totalDays) * 100;
        
        // Update pivot record
        $user->reading_plans()->updateExistingPivot($this->readingPlan->id, [
            'current_streak' => $currentStreak,
            'completion_rate' => $completionRate,
        ]);
        
        // Refresh the component state
        $this->mount();
    }
    
    public function calculateNextBreakDays()
    {
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
    
    public function render()
    {
        $nextBreakDays = $this->calculateNextBreakDays();
        
        return view('livewire.dashboard', [
            'readingPlan' => $this->readingPlan,
            'userPlan' => $this->userPlan,
            'todayReading' => $this->todayReading,
            'completedToday' => $this->completedToday,
            'readingHistory' => $this->readingHistory,
            'groupMessages' => $this->groupMessages,
            'calendarDays' => $this->calendarDays,
            'nextBreakDays' => $nextBreakDays,
        ]);
    }
}