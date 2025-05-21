<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReadingPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        // This would typically call a service or use the seeder's method
        // For now, we'll redirect with a message to use the command
        
        return redirect()->route('admin.reading-plans.index')
            ->with('success', 'Reading plan created successfully. Use the command "php artisan reading:generate" to generate daily readings.');
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
     * Remove the specified reading plan from storage.
     */
    public function destroy(ReadingPlan $readingPlan)
    {
        $readingPlan->delete();

        return redirect()->route('admin.reading-plans.index')
            ->with('success', 'Reading plan deleted successfully.');
    }
}
