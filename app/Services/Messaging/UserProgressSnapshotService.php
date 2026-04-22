<?php

namespace App\Services\Messaging;

use App\Models\ReadingPlan;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class UserProgressSnapshotService
{
    public function build(User $user, ?CarbonInterface $today = null): array
    {
        $today = $today ? Carbon::instance($today) : Carbon::today();

        $user->loadMissing([
            'hierarchy.parent',
            'readingPlans.trainingResources',
            'readingPlans.dailyReadings',
            'readingProgress.dailyReading',
            'trainingCompletions',
        ]);

        $activePlan = $user->activeReadingPlanFromLoaded();

        if (! $activePlan) {
            return [
                'user' => $user,
                'hierarchy' => $user->hierarchy,
                'active_plan' => null,
                'status_key' => 'no_active_plan',
                'status_label' => 'No Active Plan',
                'status_tone' => 'slate',
                'training_progress' => '0 / 0',
                'training_completed_count' => 0,
                'training_total' => 0,
                'training_status' => 'not_required',
                'expected_day' => null,
                'completed_days' => 0,
                'behind_days' => 0,
                'ahead_days' => 0,
                'last_completion_date' => null,
            ];
        }

        return $this->buildForActivePlan($user, $activePlan, $today);
    }

    private function buildForActivePlan(User $user, ReadingPlan $activePlan, Carbon $today): array
    {
        $trainingResources = $activePlan->trainingResources;
        $trainingResourceIds = $trainingResources->pluck('id');
        $trainingCompletedCount = $user->trainingCompletions
            ->whereIn('training_resource_id', $trainingResourceIds)
            ->count();
        $trainingTotal = $trainingResources->count();
        $trainingComplete = $trainingTotal === 0 || $trainingCompletedCount >= $trainingTotal;

        $trainingStatus = match (true) {
            $trainingTotal === 0 => 'not_required',
            $trainingCompletedCount === 0 => 'not_started',
            $trainingComplete => 'completed',
            default => 'partial',
        };

        $dailyReadings = $activePlan->dailyReadings;
        $readingDays = $dailyReadings->where('is_break_day', false);
        $maxReadingDay = max($readingDays->max('day_number') ?? 1, 1);
        $expectedDay = min($activePlan->expectedCurrentDay($today), $maxReadingDay);

        $completedDayNumbers = $user->readingProgress
            ->filter(fn ($progress) => $progress->reading_plan_id === $activePlan->id
                && $progress->dailyReading
                && (! $user->currentParticipationIdForPlan($activePlan) || $progress->reading_plan_participation_id === $user->currentParticipationIdForPlan($activePlan)))
            ->map(fn ($progress) => $progress->dailyReading->day_number)
            ->unique()
            ->sort()
            ->values();

        $completedDays = $completedDayNumbers->count();
        $completedThroughExpected = $completedDayNumbers->filter(fn ($dayNumber) => $dayNumber <= $expectedDay)->count();
        $expectedReadingDays = $readingDays->where('day_number', '<=', $expectedDay)->count();
        $behindDays = max($expectedReadingDays - $completedThroughExpected, 0);
        $aheadDays = $completedDayNumbers->filter(fn ($dayNumber) => $dayNumber > $expectedDay)->count();
        $readingUnlocked = $activePlan->canRecordReadings($user, $today);
        $lastCompletion = $user->readingProgress
            ->filter(fn ($progress) => ! $user->currentParticipationIdForPlan($activePlan) || $progress->reading_plan_participation_id === $user->currentParticipationIdForPlan($activePlan))
            ->sortByDesc('completed_date')
            ->first()?->completed_date;

        [$statusKey, $statusLabel, $statusTone] = match (true) {
            ! $trainingComplete => ['in_training', 'In Training', 'amber'],
            ! $readingUnlocked => ['awaiting_start', 'Awaiting Reading Start', 'sky'],
            $aheadDays > 0 => ['reading_ahead', 'Reading Ahead', 'indigo'],
            $behindDays > 0 => ['catching_up', 'Catching Up', 'rose'],
            default => ['on_track', 'On Track', 'green'],
        };

        return [
            'user' => $user,
            'hierarchy' => $user->hierarchy,
            'active_plan' => $activePlan,
            'status_key' => $statusKey,
            'status_label' => $statusLabel,
            'status_tone' => $statusTone,
            'training_progress' => "{$trainingCompletedCount} / {$trainingTotal}",
            'training_completed_count' => $trainingCompletedCount,
            'training_total' => $trainingTotal,
            'training_status' => $trainingStatus,
            'expected_day' => $expectedDay,
            'completed_days' => $completedDays,
            'behind_days' => $behindDays,
            'ahead_days' => $aheadDays,
            'last_completion_date' => $lastCompletion,
        ];
    }
}
