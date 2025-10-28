<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\BibleChapter;
use App\Models\DailyReading;
use Illuminate\Http\Request;
use App\Models\ReadingProgress;
use Illuminate\Support\Facades\Auth;

class ReadingProgressController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get the user's active reading plan
        $activePlan = $user->readingPlans()
            ->where('user_reading_plans.is_active', true)
            ->first();

        if (!$activePlan) {
            return redirect()->route('reading-plans.index')
                ->with('error', 'No active reading plan found. Please join a reading plan first.');
        }

        // Calculate the current day based on plan start date
        $startDate = Carbon::parse($activePlan->start_date);
        $today = Carbon::today();
        $daysSinceStart = $startDate->diffInDays($today, false);
        $calculatedCurrentDay = $daysSinceStart < 0 ? 1 : $daysSinceStart + 1;

        // Clamp to max day in the plan
        $maxDay = DailyReading::where('reading_plan_id', $activePlan->id)->max('day_number');
        $calculatedCurrentDay = min($calculatedCurrentDay, (int) $maxDay);

        // Update pivot if different
        if ($activePlan->pivot && $activePlan->pivot->current_day != $calculatedCurrentDay) {
            $user->readingPlans()->updateExistingPivot($activePlan->id, [
                'current_day' => $calculatedCurrentDay,
            ]);
            // Refresh active plan
            $activePlan = $user->readingPlans()
                ->where('user_reading_plans.is_active', true)
                ->first();
        }

        // Today's reading (may be break day)
        $todayReading = DailyReading::where('reading_plan_id', $activePlan->id)
            ->where('day_number', $activePlan->pivot->current_day)
            ->first();

        // All user's reading plans for the summary grid
        $readingPlans = $user->readingPlans()->get();

        return view('reading-progress.index', [
            'readingPlan' => $activePlan,
            'todayReading' => $todayReading,
            'readingPlans' => $readingPlans,
        ]);
    }

    public function view($request)
{
    /*
    $user = auth()->user();
    
    $newTestamentProgress = [
        'current_day' => ReadingProgress::getNextDay($user->id, 'new'),
        'total_days' => BibleChapter::getTotalDays('new'),
        'completion_rate' => $this->calculateCompletionRate($user->id, 'new'),
        'today_chapters' => $this->getTodayChapters($user->id, 'new'),
        'history' => ReadingProgress::where('user_id', $user->id)
            ->where('testament', 'new')
            ->orderBy('day_number', 'desc')
            ->get()
    ];
    
    $oldTestamentProgress = [
        'current_day' => ReadingProgress::getNextDay($user->id, 'old'),
        'total_days' => BibleChapter::getTotalDays('old'),
        'completion_rate' => $this->calculateCompletionRate($user->id, 'old'),
        'today_chapters' => $this->getTodayChapters($user->id, 'old'),
        'history' => ReadingProgress::where('user_id', $user->id)
            ->where('testament', 'old')
            ->orderBy('day_number', 'desc')
            ->get()
    ];

    return view('reading.view', compact('newTestamentProgress', 'oldTestamentProgress'));
    */

    $planId = $request->query('plan_id');
        
        if (!$planId) {
            $user = Auth::user();
            $activePlan = $user->readingPlans()
                ->where('user_reading_plans.is_active', true)
                ->first();
                
            if ($activePlan) {
                $planId = $activePlan->id;
            }
        }
        
        return view('reading-progress.view', [
            'planId' => $planId
        ]);
}

public function quickMark(Request $request)
{
    $request->validate([
        'day_number' => 'required|integer|min:1'
    ]);
    
    $user = Auth::user();
    $dayNumber = $request->day_number;
    
    // Get user's active reading plan
    $userPlan = $user->readingPlans()
        ->where('user_reading_plans.is_active', true)
        ->first();
        
    if (!$userPlan) {
        return back()->with('error', 'No active reading plan found.');
    }
    
    // Validate day number
    if ($dayNumber > $userPlan->pivot->current_day) {
        return back()->with('error', 'Cannot mark future days as complete.');
    }
    
    // Find the daily reading
    $dailyReading = DailyReading::where('reading_plan_id', $userPlan->id)
        ->where('day_number', $dayNumber)
        ->first();
        
    if (!$dailyReading) {
        return back()->with('error', "Day {$dayNumber} not found in reading plan.");
    }
    
    if ($dailyReading->is_break_day) {
        return back()->with('error', "Day {$dayNumber} is a break day.");
    }
    
    // Check if already completed
    $alreadyCompleted = ReadingProgress::where('user_id', $user->id)
        ->where('daily_reading_id', $dailyReading->id)
        ->exists();
        
    if ($alreadyCompleted) {
        return back()->with('error', "Day {$dayNumber} is already completed.");
    }
    
    // Mark as complete
    ReadingProgress::create([
        'user_id' => $user->id,
        'reading_plan_id' => $userPlan->id,
        'daily_reading_id' => $dailyReading->id,
        'completed_date' => Carbon::today(),
    ]);
    
    // Update completion rate
    $completedDays = ReadingProgress::where('user_id', $user->id)
        ->where('reading_plan_id', $userPlan->id)
        ->count();
        
    $totalDays = $userPlan->pivot->current_day;
    $completionRate = ($completedDays / $totalDays) * 100;
    
    $user->readingPlans()->updateExistingPivot($userPlan->id, [
        'completion_rate' => $completionRate,
    ]);
    
    return back()->with('success', "Day {$dayNumber} ({$dailyReading->reading_range}) marked as complete!");
}


}