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
        $user = auth()->user();
        
        $newTestamentProgress = [
            'current_day' => ReadingProgress::getNextDay($user->id, 'new'),
            'total_days' => BibleChapter::getTotalDays('new'),
            'completion_rate' => $this->calculateCompletionRate($user->id, 'new'),
            'today_chapters' => $this->getTodayChapters($user->id, 'new')
        ];
        
        $oldTestamentProgress = [
            'current_day' => ReadingProgress::getNextDay($user->id, 'old'),
            'total_days' => BibleChapter::getTotalDays('old'),
            'completion_rate' => $this->calculateCompletionRate($user->id, 'old'),
            'today_chapters' => $this->getTodayChapters($user->id, 'old')
        ];

        return view('reading.progress', compact('newTestamentProgress', 'oldTestamentProgress'));
    }

    public function markAsComplete($testament)
    {
        $user = auth()->user();
        $nextDay = ReadingProgress::getNextDay($user->id, $testament);
        
        ReadingProgress::create([
            'user_id' => $user->id,
            'day_number' => $nextDay,
            'testament' => $testament,
            'chapters_range' => BibleChapter::getDayRange($nextDay, $testament),
            'is_completed' => true,
            'completed_at' => Carbon::now()
        ]);

        return response()->json([
            'success' => true,
            'next_chapters' => $this->getTodayChapters($user->id, $testament)
        ]);
    }

    private function calculateCompletionRate($userId, $testament)
    {
        $completed = ReadingProgress::getCurrentProgress($userId, $testament);
        $total = BibleChapter::getTotalDays($testament);
        
        return ($completed / $total) * 100;
    }

    private function getTodayChapters($userId, $testament)
    {
        $nextDay = ReadingProgress::getNextDay($userId, $testament);
        return BibleChapter::getChaptersForDay($nextDay, $testament);
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

public function getReadingPlans()
{
    $user = auth()->user();
    $readingPlans = $user->reading_plans()->with('chapters')->get();

    return view('reading.plans', compact('readingPlans'));
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