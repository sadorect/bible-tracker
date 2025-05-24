<?php

namespace App\Http\Controllers\Admin;

use Artisan;
use Carbon\Carbon;
use App\Models\ReadingPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

class AdminReadingPlanController extends Controller
{
    /**
     * Display a listing of the reading plans.
     */
    public function index()
    {
        $readingPlans = ReadingPlan::all();
        return view('admin.reading-plans.index', compact('readingPlans'));
    }

    /**
     * Show the form for creating a new reading plan.
     */
    public function create()
    {
        return view('admin.reading-plans.create');
    }

    /**
     * Store a newly created reading plan in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['old_testament', 'new_testament', 'full_bible', 'custom'])],
            'description' => ['nullable', 'string'],
            'chapters_per_day' => ['required', 'integer', 'min:1'],
            'streak_days' => ['required', 'integer', 'min:1'],
            'break_days' => ['required', 'integer', 'min:0'],
            'start_date' => ['required', 'date'],
            'is_active' => ['boolean'],
            'additional_info' => ['nullable', 'string'],
        ]);

        $readingPlan = ReadingPlan::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? '',
            'chapters_per_day' => $validated['chapters_per_day'],
            'streak_days' => $validated['streak_days'],
            'break_days' => $validated['break_days'],
            'start_date' => Carbon::parse($validated['start_date']),
            'is_active' => $request->has('is_active'),
            'additional_info' => $validated['additional_info'] ?? '',
        ]);
    
        // Generate daily readings for the plan
        \Illuminate\Support\Facades\Artisan::call('reading:generate', [
            'plan_id' => $readingPlan->id
        ]);
    
        return redirect()->route('admin.reading-plans.index')
            ->with('success', 'Reading plan created successfully with daily readings.');
    }

    /**
     * Show the form for editing the specified reading plan.
     */
    public function edit(ReadingPlan $readingPlan)
    {
        return view('admin.reading-plans.edit', compact('readingPlan'));
    }

    /**
     * Update the specified reading plan in storage.
     */
    public function update(Request $request, ReadingPlan $readingPlan)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'additional_info' => ['nullable', 'string'],
        ]);

        $readingPlan->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'is_active' => $request->has('is_active'),
            'additional_info' => $validated['additional_info'] ?? '',
        ]);

        return redirect()->route('admin.reading-plans.index')
            ->with('success', 'Reading plan updated successfully.');
    }
/**
     * Skip to a specific day in the reading plan.
     */
    public function skipToDay(Request $request, ReadingPlan $readingPlan)
    {
        $request->validate([
            'day' => 'required|integer|min:1'
        ]);
        
        $user = Auth::user();
        $day = $request->input('day');
        
        // Check if user is in this plan
        $existingPlan = $user->readingPlans()
            ->where('reading_plan_id', $readingPlan->id)
            ->first();
            
        if (!$existingPlan) {
            return redirect()->route('reading-plans.index')
                ->with('error', 'You are not following this reading plan.');
        }
        
        // Check if the day is valid
        $maxDay = $readingPlan->dailyReadings()->max('day_number');
        if ($day > $maxDay) {
            return back()->with('error', "The reading plan only has {$maxDay} days.");
        }
        
        // Update the current day
        $user->readingPlans()->updateExistingPivot($readingPlan->id, [
            'current_day' => $day
        ]);
        
        // Recalculate completion rate
        $completedDays = $user->readingProgress()
            ->where('reading_plan_id', $readingPlan->id)
            ->count();
            
        $completionRate = ($completedDays / $day) * 100;
        
        $user->readingPlans()->updateExistingPivot($readingPlan->id, [
            'completion_rate' => $completionRate
        ]);
        
        return redirect()->route('admin.dashboard')
            ->with('success', "You've skipped to day {$day} of the reading plan.");
    }
    /**
     * View all reading progress for a plan.
     */
    public function viewProgress(ReadingPlan $readingPlan)
    {
        $user = Auth::user();
        
        // Check if user is in this plan
        $existingPlan = $user->readingPlans()
            ->where('reading_plan_id', $readingPlan->id)
            ->first();
            
        if (!$existingPlan) {
            return redirect()->route('reading-plans.index')
                ->with('error', 'You are not following this reading plan.');
        }
        
        // Get all daily readings for this plan
        $dailyReadings = $readingPlan->dailyReadings()
            ->orderBy('day_number')
            ->get();
            
        // Get user's progress for each reading
        $progress = [];
        foreach ($dailyReadings as $reading) {
            $completed = $user->readingProgress()
                ->where('daily_reading_id', $reading->id)
                ->first();
                
            $progress[] = [
                'day' => $reading->day_number,
                'reading' => $reading->reading_range,
                'is_break_day' => $reading->is_break_day,
                'completed' => $completed !== null,
                'completed_date' => $completed ? $completed->completed_date : null,
            ];
        }
        
        return view('reading-plans.progress', compact('readingPlan', 'progress'));
    }

    /**
     * Remove the specified reading plan from storage.
     */
    public function destroy(ReadingPlan $readingPlan)
    {
        $readingPlan->delete();

        return redirect()->route('admin.reading-plans.index')
            ->with('success', 'Reading plan deleted successfully.');
    }
}