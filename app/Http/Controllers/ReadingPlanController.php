<?php

namespace App\Http\Controllers;

use App\Models\ReadingPlan;
use App\Models\User;
use App\Services\ReadingPlanParticipationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReadingPlanController extends Controller
{
    public function __construct(
        private readonly ReadingPlanParticipationService $participationService,
    ) {
    }

    /**
     * Display a listing of the reading plans.
     */
    public function index()
    {
        $user = Auth::user();
        $readingPlans = ReadingPlan::query()
            ->publiclyVisible()
            ->with(['dailyReadings', 'trainingResources'])
            ->withCount('users')
            ->orderByRaw("case when lifecycle_status = '".ReadingPlan::STATUS_RECRUITING."' then 0 else 1 end")
            ->orderBy('start_date')
            ->get();

        $activePlan = $user->activeReadingPlan();
        $joinedPlans = $user->readingPlans()->get()->keyBy('id');

        foreach ($readingPlans as $plan) {
            [$canJoin, $lockedReason] = $this->getPlanAvailability($user, $plan);
            $joinedPlan = $joinedPlans->get($plan->id);

            $plan->setAttribute('user_has_history', $joinedPlan !== null);
            $plan->setAttribute('user_is_active_participant', (bool) $joinedPlan?->pivot?->is_active);
            $plan->setAttribute('user_can_join', $canJoin);
            $plan->setAttribute('locked_reason', $lockedReason);
        }

        $visiblePlanIds = $readingPlans->pluck('id')->all();
        $pastPlans = $user->readingPlans()
            ->when($visiblePlanIds !== [], fn ($query) => $query->whereNotIn('reading_plans.id', $visiblePlanIds))
            ->with(['dailyReadings', 'trainingResources'])
            ->get()
            ->sortByDesc(fn (ReadingPlan $plan) => $plan->pivot?->joined_date)
            ->values();

        $recommendedPlan = $this->recommendedPlan($user, $readingPlans, $activePlan);

        return view('reading-plans.index', [
            'readingPlans' => $readingPlans,
            'activePlan' => $activePlan,
            'pastPlans' => $pastPlans,
            'recommendedPlan' => $recommendedPlan,
        ]);
    }

    /**
     * Display the details of a specific reading plan.
     */
    public function show(ReadingPlan $readingPlan)
    {
        $user = Auth::user();
        $readingPlan->load(['dailyReadings', 'trainingResources']);
        $userPlan = $user->readingPlans()
            ->where('reading_plan_id', $readingPlan->id)
            ->first();

        abort_unless($readingPlan->isPubliclyVisible() || $userPlan, 404);

        [$canJoin, $lockedReason] = $this->getPlanAvailability($user, $readingPlan);
        $participationHistory = $user->readingPlanParticipations()
            ->where('reading_plan_id', $readingPlan->id)
            ->latest('started_on')
            ->get();
        $participationHistorySummary = $this->summarizeParticipationHistory($readingPlan, $participationHistory);

        return view('reading-plans.show', [
            'readingPlan' => $readingPlan,
            'userPlan' => $userPlan,
            'canJoin' => $canJoin,
            'lockedReason' => $lockedReason,
            'participationHistory' => $participationHistory,
            'participationHistorySummary' => $participationHistorySummary,
            'nextSteps' => $this->nextStepsForPlan($user, $readingPlan, $userPlan, $canJoin),
        ]);
    }

    /**
     * Join a reading plan.
     */
    public function join(ReadingPlan $readingPlan)
    {
        $user = Auth::user();
        [$canJoin, $lockedReason] = $this->getPlanAvailability($user, $readingPlan);

        // Check if user is already in this plan
        $existingPlan = $user->readingPlans()
            ->where('reading_plan_id', $readingPlan->id)
            ->first();

        if ($existingPlan) {
            if ($existingPlan->pivot->is_active) {
                return redirect()->route('reading-plans.show', $readingPlan)
                    ->with('info', 'You are already participating in this reading plan.');
            }
        }

        if (! $canJoin) {
            return redirect()->route('reading-plans.show', $readingPlan)
                ->with('error', $lockedReason);
        }

        $this->participationService->startNewParticipation($user, $readingPlan);

        return redirect()->route('reading-plans.show', $readingPlan)
            ->with('success', $existingPlan
                ? 'A fresh participation cycle has been started for '.$readingPlan->name.'.'
                : 'You have joined the '.$readingPlan->name.' reading plan. You can now start your reading journey!');
    }

    /**
     * Reset progress in a reading plan.
     */
    public function resetProgress(ReadingPlan $readingPlan)
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

        // Delete all reading progress for this user and plan
        $currentParticipationId = $user->currentParticipationIdForPlan($readingPlan);

        $user->readingProgress()
            ->where('reading_plan_id', $readingPlan->id)
            ->when($currentParticipationId, fn ($query) => $query->where('reading_plan_participation_id', $currentParticipationId))
            ->delete();

        // Reset the pivot data
        $user->readingPlans()->updateExistingPivot($readingPlan->id, [
            'current_day' => 1,
            'current_streak' => 0,
            'completion_rate' => 0,
            'is_active' => true,
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Your reading progress has been reset.');
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
            ->when($user->currentParticipationIdForPlan($readingPlan), fn ($query, $participationId) => $query->where('reading_plan_participation_id', $participationId))
            ->count();

        $completionRate = ($completedDays / $day) * 100;

        $user->readingPlans()->updateExistingPivot($readingPlan->id, [
            'completion_rate' => $completionRate,
        ]);

        return redirect()->route('dashboard')
            ->with('success', "You've skipped to day {$day} of the reading plan.");
    }

    /**
     * View all reading progress for a plan.
     */
    public function viewProgress(ReadingPlan $readingPlan)
    {
        $user = Auth::user();

        $existingPlan = $user->readingPlans()
            ->where('reading_plan_id', $readingPlan->id)
            ->first();

        if (! $existingPlan) {
            return redirect()->route('reading-plans.index')
                ->with('error', 'You are not following this reading plan.');
        }

        $dailyReadings = $readingPlan->dailyReadings()
            ->orderBy('day_number')
            ->get();

        $progress = [];
        foreach ($dailyReadings as $reading) {
            $completed = $user->readingProgress()
                ->where('daily_reading_id', $reading->id)
                ->when($user->currentParticipationIdForPlan($readingPlan), fn ($query, $participationId) => $query->where('reading_plan_participation_id', $participationId))
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
     * Leave a reading plan.
     */
    public function leave(ReadingPlan $readingPlan)
    {
        $user = Auth::user();

        // Check if user is in this plan
        $existingPlan = $user->readingPlans()
            ->where('reading_plan_id', $readingPlan->id)
            ->first();

        if (! $existingPlan) {
            return redirect()->route('reading-plans.index')
                ->with('error', 'You are not participating in this reading plan.');
        }

        // Just mark as inactive instead of detaching to preserve history
        $user->readingPlans()->updateExistingPivot($readingPlan->id, [
            'is_active' => false,
        ]);
        $this->participationService->markParticipationLeft($user, $readingPlan);

        return redirect()->route('reading-plans.index')
            ->with('success', 'You have left the '.$readingPlan->name.' reading plan.');
    }

    private function getPlanAvailability(User $user, ReadingPlan $readingPlan): array
    {
        if (! $readingPlan->isPubliclyVisible()) {
            return [false, 'This reading plan is not currently visible to participants.'];
        }

        if (! $readingPlan->acceptsEnrollment()) {
            return [false, 'Enrollment is currently closed for this reading plan.'];
        }

        if ($readingPlan->isOldTestament() && ! $user->hasCompletedPlanType(ReadingPlan::TYPE_NEW_TESTAMENT)) {
            return [false, 'Old Testament plans unlock after you complete a New Testament plan.'];
        }

        return [true, null];
    }

    private function recommendedPlan(User $user, $readingPlans, ?ReadingPlan $activePlan): ?array
    {
        if ($activePlan) {
            $currentPlan = $readingPlans->firstWhere('id', $activePlan->id);

            if ($currentPlan) {
                return [
                    'plan' => $currentPlan,
                    'label' => 'Current cohort',
                    'reason' => 'This is your active reading journey.',
                    'action_label' => 'Open cohort',
                ];
            }
        }

        $joinable = $readingPlans
            ->filter(fn (ReadingPlan $plan) => $plan->user_can_join && ! $plan->user_is_active_participant)
            ->sortBy([
                [fn (ReadingPlan $plan) => $plan->isRecruiting() ? 0 : 1, 'asc'],
                [fn (ReadingPlan $plan) => $plan->start_date?->timestamp ?? PHP_INT_MAX, 'asc'],
                ['name', 'asc'],
            ])
            ->values();

        if ($joinable->isEmpty()) {
            return null;
        }

        $firstNewTestament = $joinable->first(fn (ReadingPlan $plan) => $plan->isNewTestament());
        $firstOldTestament = $joinable->first(fn (ReadingPlan $plan) => $plan->isOldTestament());
        $historyPlan = $joinable->first(fn (ReadingPlan $plan) => $plan->user_has_history);

        if (! $user->hasCompletedPlanType(ReadingPlan::TYPE_NEW_TESTAMENT) && $firstNewTestament) {
            return [
                'plan' => $firstNewTestament,
                'label' => 'Recommended next',
                'reason' => 'Start with a New Testament cohort first.',
                'action_label' => $firstNewTestament->user_has_history ? 'Start fresh cycle' : 'Join cohort',
            ];
        }

        if ($firstOldTestament) {
            return [
                'plan' => $firstOldTestament,
                'label' => 'Recommended next',
                'reason' => 'Old Testament cohorts are unlocked on your profile now.',
                'action_label' => $firstOldTestament->user_has_history ? 'Start fresh cycle' : 'Join cohort',
            ];
        }

        if ($historyPlan) {
            return [
                'plan' => $historyPlan,
                'label' => 'Recommended next',
                'reason' => 'You already know this cohort and can begin a fresh cycle.',
                'action_label' => 'Start fresh cycle',
            ];
        }

        $plan = $joinable->first();

        return [
            'plan' => $plan,
            'label' => 'Recommended next',
            'reason' => $plan->isRecruiting()
                ? 'Enrollment is open now.'
                : 'This cohort is already underway and still open to eligible readers.',
            'action_label' => 'Join cohort',
        ];
    }

    private function summarizeParticipationHistory(ReadingPlan $readingPlan, $participationHistory): array
    {
        $requiredDays = $readingPlan->dailyReadings->where('is_break_day', false)->count();

        $cycles = $participationHistory->map(function ($participation) use ($requiredDays) {
            $completedDays = $participation->progress()
                ->distinct('daily_reading_id')
                ->count('daily_reading_id');
            $lastCompletedOn = $participation->progress()
                ->max('completed_date');
            $durationDays = $participation->started_on
                ? $participation->started_on->diffInDays($participation->ended_on ?? now()) + 1
                : null;

            return [
                'participation' => $participation,
                'completed_days' => $completedDays,
                'required_days' => $requiredDays,
                'completion_rate' => $requiredDays > 0 ? round(($completedDays / $requiredDays) * 100, 1) : 0,
                'last_completed_on' => $lastCompletedOn ? Carbon::parse($lastCompletedOn) : null,
                'duration_days' => $durationDays,
                'join_source_label' => $participation->join_source === \App\Models\ReadingPlanParticipation::SOURCE_INVITE
                    ? 'Invite link'
                    : 'Direct join',
            ];
        })->values();

        return [
            'cycles' => $cycles,
            'total_cycles' => $cycles->count(),
            'best_cycle' => $cycles->sortByDesc('completion_rate')->first(),
            'latest_cycle' => $cycles->first(),
        ];
    }

    private function nextStepsForPlan(User $user, ReadingPlan $readingPlan, ?ReadingPlan $userPlan, bool $canJoin): array
    {
        $steps = [];

        if (! $userPlan) {
            if ($canJoin) {
                $steps[] = 'Join this cohort from the action panel.';
                if ($readingPlan->training_days > 0) {
                    $steps[] = "Complete {$readingPlan->training_days} training step(s) when your dashboard opens.";
                }
                $steps[] = $readingPlan->reading_start_date && $readingPlan->reading_start_date->isFuture()
                    ? 'Reading begins on '.$readingPlan->reading_start_date->format('M d, Y').'.'
                    : 'Open your dashboard and begin as soon as reading is unlocked.';
            }

            return $steps;
        }

        if ($readingPlan->training_days > 0 && ! $readingPlan->isTrainingCompleteFor($user)) {
            $steps[] = "Finish the {$readingPlan->training_days} training step(s) on your dashboard.";
        }

        if (! $readingPlan->canRecordReadings($user, Carbon::today())) {
            $steps[] = 'Reading unlocks on '.($readingPlan->reading_start_date?->format('M d, Y') ?? 'the cohort start date').'.';
        } else {
            $steps[] = 'Open your dashboard to record today’s reading.';
        }

        $steps[] = 'Use Reading History to review your progress and missed days.';

        return $steps;
    }
}
