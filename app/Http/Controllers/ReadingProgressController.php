<?php

namespace App\Http\Controllers;

use App\Models\ReadingProgress;
use App\Models\BibleChapter;
use Carbon\Carbon;

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

    public function view()
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

public function index()
{
    $user = Auth::user();
    $readingPlans = $user->readingPlans;
    
    return view('reading-progress.index', [
        'readingPlans' => $readingPlans
    ]);
    return view ('reading-plans.index');
}