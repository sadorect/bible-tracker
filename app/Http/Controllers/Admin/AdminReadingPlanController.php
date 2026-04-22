<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReadingPlan;
use App\Models\ReadingPlanInvite;
use App\Models\TrainingResource;
use Artisan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        return view('admin.reading-plans.create', [
            'typeDefaults' => ReadingPlan::typeConfigurations(),
        ]);
    }

    /**
     * Store a newly created reading plan in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateReadingPlan($request);

        $readingPlan = ReadingPlan::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? '',
            'chapters_per_day' => $validated['chapters_per_day'],
            'streak_days' => $validated['streak_days'],
            'break_days' => $validated['break_days'],
            'start_date' => Carbon::parse($validated['start_date']),
            'end_date' => null,
            'is_active' => $request->boolean('is_active'),
            'additional_info' => $validated['additional_info'] ?? '',
        ]);

        $this->regenerateDailyReadings($readingPlan);

        return redirect()->route('admin.reading-plans.index')
            ->with('success', 'Reading plan created successfully with daily readings.');
    }

    /**
     * Show the form for editing the specified reading plan.
     */
    public function edit(ReadingPlan $readingPlan)
    {
        $readingPlan->load(['trainingResources', 'users', 'invites.creator']);

        return view('admin.reading-plans.edit', [
            'readingPlan' => $readingPlan,
            'typeDefaults' => ReadingPlan::typeConfigurations(),
        ]);
    }

    public function show(ReadingPlan $readingPlan)
    {
        return redirect()->route('admin.reading-plans.edit', $readingPlan);
    }

    /**
     * Update the specified reading plan in storage.
     */
    public function update(Request $request, ReadingPlan $readingPlan)
    {
        $validated = $this->validateReadingPlan($request);
        $requiresRegeneration = $this->requiresScheduleRegeneration($readingPlan, $validated);

        if ($requiresRegeneration && $readingPlan->readingProgress()->exists()) {
            throw ValidationException::withMessages([
                'chapters_per_day' => 'This plan already has recorded progress. Create a new plan if you need to change the stage or reading cadence.',
            ]);
        }

        $readingPlan->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? '',
            'chapters_per_day' => $validated['chapters_per_day'],
            'streak_days' => $validated['streak_days'],
            'break_days' => $validated['break_days'],
            'start_date' => Carbon::parse($validated['start_date']),
            'is_active' => $request->boolean('is_active'),
            'additional_info' => $validated['additional_info'] ?? '',
        ]);

        if ($requiresRegeneration || ! $readingPlan->dailyReadings()->exists()) {
            $this->regenerateDailyReadings($readingPlan->fresh());
        } else {
            $readingPlan->refresh();
            $readingPlan->syncScheduleDates();
        }

        return redirect()->route('admin.reading-plans.index')
            ->with('success', 'Reading plan updated successfully.');
    }

    /**
     * Skip to a specific day in the reading plan.
     */
    public function skipToDay(Request $request, ReadingPlan $readingPlan)
    {
        $request->validate([
            'day' => 'required|integer|min:1',
        ]);

        $user = Auth::user();
        $day = $request->input('day');

        // Check if user is in this plan
        $existingPlan = $user->readingPlans()
            ->where('reading_plan_id', $readingPlan->id)
            ->first();

        if (! $existingPlan) {
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
            'current_day' => $day,
        ]);

        // Recalculate completion rate
        $completedDays = $user->readingProgress()
            ->where('reading_plan_id', $readingPlan->id)
            ->count();

        $completionRate = ($completedDays / $day) * 100;

        $user->readingPlans()->updateExistingPivot($readingPlan->id, [
            'completion_rate' => $completionRate,
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

        if (! $existingPlan) {
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

    public function storeTrainingResource(Request $request, ReadingPlan $readingPlan)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'resource_url' => ['nullable', 'url'],
            'resource_file' => ['nullable', 'file', 'mimes:pdf'],
            'description' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
        ]);

        if (! $request->filled('resource_url') && ! $request->hasFile('resource_file')) {
            throw ValidationException::withMessages([
                'resource_url' => 'Add a YouTube link, a PDF upload, or both.',
            ]);
        }

        $resourcePath = null;
        if ($request->file('resource_file')) {
            $resourcePath = $request->file('resource_file')->store('training-resources', 'public');
        }

        $readingPlan->trainingResources()->create([
            'title' => $validated['title'],
            'resource_type' => TrainingResource::resolveResourceType(
                $validated['resource_url'] ?? null,
                $resourcePath,
            ),
            'resource_url' => $validated['resource_url'] ?? null,
            'resource_path' => $resourcePath,
            'description' => $validated['description'] ?? null,
            'sort_order' => $validated['sort_order']
                ?? ((int) $readingPlan->trainingResources()->max('sort_order') + 1),
        ]);

        $readingPlan->refresh();
        $readingPlan->syncScheduleDates();

        return redirect()->route('admin.reading-plans.edit', $readingPlan)
            ->with('success', 'Training resource added successfully.');
    }

    public function destroyTrainingResource(ReadingPlan $readingPlan, TrainingResource $trainingResource)
    {
        abort_unless($trainingResource->reading_plan_id === $readingPlan->id, 404);

        if ($trainingResource->resource_path) {
            Storage::disk('public')->delete($trainingResource->resource_path);
        }

        $trainingResource->delete();

        $readingPlan->refresh();
        $readingPlan->syncScheduleDates();

        return redirect()->route('admin.reading-plans.edit', $readingPlan)
            ->with('success', 'Training resource removed successfully.');
    }

    public function storeInvite(Request $request, ReadingPlan $readingPlan)
    {
        $validated = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'expires_at' => ['required', 'date', 'after:now'],
        ]);

        $readingPlan->invites()->create([
            'created_by' => $request->user()->id,
            'label' => $validated['label'] ?? null,
            'expires_at' => Carbon::parse($validated['expires_at']),
        ]);

        return redirect()->route('admin.reading-plans.edit', $readingPlan)
            ->with('success', 'Enrollment link generated successfully.');
    }

    public function revokeInvite(ReadingPlan $readingPlan, ReadingPlanInvite $readingPlanInvite)
    {
        abort_unless($readingPlanInvite->reading_plan_id === $readingPlan->id, 404);

        $readingPlanInvite->update([
            'revoked_at' => now(),
        ]);

        return redirect()->route('admin.reading-plans.edit', $readingPlan)
            ->with('success', 'Enrollment link revoked successfully.');
    }

    private function validateReadingPlan(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(ReadingPlan::supportedTypes())],
            'description' => ['nullable', 'string'],
            'chapters_per_day' => ['required', 'integer', 'min:1', 'max:100'],
            'streak_days' => ['required', 'integer', 'min:1', 'max:365'],
            'break_days' => ['required', 'integer', 'min:0', 'max:60'],
            'start_date' => ['required', 'date'],
            'is_active' => ['boolean'],
            'additional_info' => ['nullable', 'string'],
        ]);
    }

    private function requiresScheduleRegeneration(ReadingPlan $readingPlan, array $validated): bool
    {
        return $readingPlan->type !== $validated['type']
            || (int) $readingPlan->chapters_per_day !== (int) $validated['chapters_per_day']
            || (int) $readingPlan->streak_days !== (int) $validated['streak_days']
            || (int) $readingPlan->break_days !== (int) $validated['break_days'];
    }

    private function regenerateDailyReadings(ReadingPlan $readingPlan): void
    {
        Artisan::call('reading:generate', [
            'plan_id' => $readingPlan->id,
        ]);

        $readingPlan->refresh();
        $readingPlan->syncScheduleDates();
    }
}
