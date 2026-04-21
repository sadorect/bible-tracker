<?php

namespace App\Http\Controllers;

use App\Models\DailyReading;
use App\Models\ReadingProgress;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        if (! $activePlan) {
            return redirect()->route('reading-plans.index')
                ->with('error', 'No active reading plan found. Please join a reading plan first.');
        }

        // Calculate the current day based on the actual reading start date.
        $calculatedCurrentDay = $activePlan->expectedCurrentDay(Carbon::today());

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
        $trainingResources = $activePlan->trainingResources()->get();
        $trainingComplete = $activePlan->isTrainingCompleteFor($user);
        $readingUnlocked = $activePlan->canRecordReadings($user, Carbon::today());

        return view('reading-progress.index', [
            'readingPlan' => $activePlan,
            'todayReading' => $todayReading,
            'readingPlans' => $readingPlans,
            'trainingResources' => $trainingResources,
            'trainingComplete' => $trainingComplete,
            'readingUnlocked' => $readingUnlocked,
        ]);
    }

    public function view(Request $request)
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

        if (! $planId) {
            $user = Auth::user();
            $activePlan = $user->readingPlans()
                ->where('user_reading_plans.is_active', true)
                ->first();

            if ($activePlan) {
                $planId = $activePlan->id;
            }
        }

        return view('reading-progress.view', [
            'planId' => $planId,
        ]);
    }

    public function quickMark(Request $request)
    {
        $request->validate([
            'day_number' => 'nullable|integer|min:1',
            'day_range' => ['nullable', 'regex:/^\s*\d+\s*-\s*\d+\s*$/'],
            'apply_catch_up' => 'nullable|boolean',
        ]);

        $user = Auth::user();

        // Get user's active reading plan
        $userPlan = $user->readingPlans()
            ->where('user_reading_plans.is_active', true)
            ->first();

        if (! $userPlan) {
            return back()->with('error', 'No active reading plan found.');
        }

        if (! $userPlan->canRecordReadings($user, Carbon::today())) {
            return back()->with('error', 'Complete all training resources and wait for the reading start date before recording reading progress.');
        }

        $currentDay = $userPlan->pivot->current_day;
        $maxDay = (int) DailyReading::where('reading_plan_id', $userPlan->id)->max('day_number');

        // Build list of days to mark
        $daysToMark = [];

        if ($request->filled('day_range')) {
            // Parse range like "90-95"
            [$start, $end] = array_map('intval', preg_split('/\s*-\s*/', $request->day_range));
            if ($start > $end) {
                [$start, $end] = [$end, $start];
            }
            $end = min($end, $maxDay);
            $start = max(1, $start);
            $daysToMark = range($start, $end);
        } elseif ($request->boolean('apply_catch_up') && $request->filled('day_number')) {
            // Mark all missed days up to the given day_number (inclusive)
            $target = min(intval($request->day_number), $currentDay);
            $daysToMark = range(1, $target);
        } elseif ($request->filled('day_number')) {
            $target = intval($request->day_number);
            if ($target > $maxDay) {
                return back()->with('error', "This plan only has {$maxDay} day(s).");
            }
            $daysToMark = [$target];
        } else {
            return back()->with('error', 'Please provide a day number or a day range to mark.');
        }

        // Fetch readings and filter valid ones (not break days, not already completed)
        $readings = DailyReading::where('reading_plan_id', $userPlan->id)
            ->whereIn('day_number', $daysToMark)
            ->orderBy('day_number')
            ->get()
            ->keyBy('day_number');

        $already = 0;
        $breaks = 0;
        $created = 0;
        $notFound = 0;

        foreach ($daysToMark as $day) {
            $reading = $readings->get($day);
            if (! $reading) {
                $notFound++;

                continue;
            }
            if ($reading->is_break_day) {
                $breaks++;

                continue;
            }

            $exists = ReadingProgress::where('user_id', $user->id)
                ->where('daily_reading_id', $reading->id)
                ->exists();
            if ($exists) {
                $already++;

                continue;
            }

            ReadingProgress::create([
                'user_id' => $user->id,
                'reading_plan_id' => $userPlan->id,
                'daily_reading_id' => $reading->id,
                'completed_date' => Carbon::today(),
            ]);
            $created++;
        }

        // Recompute completion rate up to current day excluding break days
        $readingDaysSoFar = DailyReading::where('reading_plan_id', $userPlan->id)
            ->where('is_break_day', false)
            ->where('day_number', '<=', $currentDay)
            ->count();
        $completedDays = ReadingProgress::where('user_id', $user->id)
            ->where('reading_plan_id', $userPlan->id)
            ->count();
        $completionRate = $readingDaysSoFar > 0 ? ($completedDays / $readingDaysSoFar) * 100 : 0;

        // Plan-based streak: consecutive completed reading days from current_day (skip breaks)
        $completedDayNumbers = ReadingProgress::where('user_id', $user->id)
            ->where('reading_progress.reading_plan_id', $userPlan->id)
            ->join('daily_readings', 'daily_readings.id', '=', 'reading_progress.daily_reading_id')
            ->pluck('daily_readings.day_number')
            ->toArray();
        $daysMap = DailyReading::where('reading_plan_id', $userPlan->id)
            ->where('day_number', '<=', $currentDay)
            ->orderBy('day_number')
            ->get(['day_number', 'is_break_day'])
            ->keyBy('day_number');
        $streak = 0;
        for ($d = $currentDay; $d >= 1; $d--) {
            $day = $daysMap->get($d);
            if (! $day) {
                break;
            }
            if ($day->is_break_day) {
                continue;
            }
            if (in_array($d, $completedDayNumbers)) {
                $streak++;
            } else {
                break;
            }
        }

        $user->readingPlans()->updateExistingPivot($userPlan->id, [
            'completion_rate' => $completionRate,
            'current_streak' => $streak,
        ]);

        $msgParts = [];
        if ($created) {
            $msgParts[] = "$created day(s) marked";
        }
        if ($already) {
            $msgParts[] = "$already already completed";
        }
        if ($breaks) {
            $msgParts[] = "$breaks break day(s) skipped";
        }
        if ($notFound) {
            $msgParts[] = "$notFound not found";
        }
        $summary = implode(', ', $msgParts);

        return back()->with('success', $summary ?: 'No changes made.');
    }
}
