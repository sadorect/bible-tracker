<?php

namespace App\Livewire;

use App\Models\ReadingProgress;
use App\Models\ReadingPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ReadingHistory extends Component
{
    public $readingHistory = [];
    public $filterCompleted = 'all'; // 'all', 'completed', 'missed'
    public $searchTerm = '';
    public $userPlan;

    public function mount()
    {
        $this->loadUserPlan();
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
    }

    public function loadReadingHistory()
    {
        if (!$this->userPlan) {
            $this->readingHistory = [];
            return;
        }

        $user = Auth::user();

        // Get all daily readings for the user's active plan up to current day
        $query = \App\Models\DailyReading::where('reading_plan_id', $this->userPlan->id)
            ->where('day_number', '<=', $this->userPlan->pivot->current_day)
            ->orderBy('day_number', 'desc');

        // Apply search filter
        if ($this->searchTerm) {
            $query->where(function($q) {
                $q->where('reading_range', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('book_start', 'like', '%' . $this->searchTerm . '%')
                  ->orWhere('book_end', 'like', '%' . $this->searchTerm . '%');
            });
        }

        $dailyReadings = $query->get();

        // Get the reading plan to access the start date
        $readingPlan = ReadingPlan::find($this->userPlan->id);
        $planStartDate = Carbon::parse($readingPlan->start_date);

        // Get all completed readings for this user and plan
        $completedReadings = ReadingProgress::where('user_id', $user->id)
            ->where('reading_plan_id', $this->userPlan->id)
            ->get()
            ->keyBy('daily_reading_id');

        $this->readingHistory = $dailyReadings->map(function ($reading) use ($user, $completedReadings, $planStartDate) {
            $progress = $completedReadings->get($reading->id);
            $completed = $progress !== null;
            
            // Filter based on completion status
            if ($this->filterCompleted === 'completed' && !$completed) {
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
    }

    public function render()
    {
        // Check if we need to redirect to reading plans if no active plan
        if (!$this->userPlan) {
            return redirect()->route('reading-plans.index');
        }

        return view('livewire.reading-history')->layout('layouts.app')
            ->with([
                'readingHistory' => $this->readingHistory,
                'userPlan' => $this->userPlan,
            ]);
    }
}
