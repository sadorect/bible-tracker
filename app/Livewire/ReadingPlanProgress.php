<?php

namespace App\Livewire;

use App\Models\ReadingPlan;
use App\Models\ReadingProgress;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ReadingPlanProgress extends Component
{
    public $readingPlan;
    public $userPlan;
    public $readingProgress = [];
    public $statistics = [];
    
    public function mount($planId = null)
    {
        $user = Auth::user();
        
        if ($planId) {
            $this->readingPlan = ReadingPlan::find($planId);
            $this->userPlan = $user->readingPlans()
                ->where('reading_plan_id', $planId)
                ->first();
        } else {
            // Get the user's active reading plan
            $this->userPlan = $user->readingPlans()
                ->where('user_reading_plans.is_active', true)
                ->first();
                
            if ($this->userPlan) {
                $this->readingPlan = ReadingPlan::find($this->userPlan->id);
            } else {
                return redirect()->route('reading-plans.index');
            }
        }
        
        $this->loadReadingProgress();
        $this->calculateStatistics();
    }
    
    public function loadReadingProgress()
    {
        $user = Auth::user();
        
        // Get all daily readings for this plan
        $dailyReadings = $this->readingPlan->dailyReadings()
            ->orderBy('day_number')
            ->get();
            
        foreach ($dailyReadings as $reading) {
            $completed = ReadingProgress::where('user_id', $user->id)
                ->where('daily_reading_id', $reading->id)
                ->first();
                
            $this->readingProgress[] = [
                'day' => $reading->day_number,
                'date' => Carbon::parse($this->readingPlan->start_date)->addDays($reading->day_number - 1)->format('M d, Y'),
                'reading' => $reading->reading_range,
                'is_break_day' => $reading->is_break_day,
                'completed' => $completed !== null,
                'completed_date' => $completed ? $completed->completed_date : null,
                'is_future' => Carbon::parse($this->readingPlan->start_date)->addDays($reading->day_number - 1)->isAfter(Carbon::today()),
            ];
        }
    }
    
    public function calculateStatistics()
    {
        $totalDays = count($this->readingProgress);
        $completedDays = 0;
        $missedDays = 0;
        $breakDays = 0;
        $futureDays = 0;
        
        foreach ($this->readingProgress as $progress) {
            if ($progress['is_future']) {
                $futureDays++;
            } elseif ($progress['is_break_day']) {
                $breakDays++;
            } elseif ($progress['completed']) {
                $completedDays++;
            } else {
                $missedDays++;
            }
        }
        
        $pastReadingDays = $totalDays - $futureDays - $breakDays;
        $completionRate = $pastReadingDays > 0 ? ($completedDays / $pastReadingDays) * 100 : 0;
        
        $this->statistics = [
            'total_days' => $totalDays,
            'completed_days' => $completedDays,
            'missed_days' => $missedDays,
            'break_days' => $breakDays,
            'future_days' => $futureDays,
            'completion_rate' => round($completionRate, 1),
            'current_streak' => $this->userPlan->pivot->current_streak ?? 0,
        ];
    }
    
    public function render()
    {
        return view('livewire.reading-plan-progress', [
            'readingPlan' => $this->readingPlan,
            'userPlan' => $this->userPlan,
            'readingProgress' => $this->readingProgress,
            'statistics' => $this->statistics,
        ]);
    }
}