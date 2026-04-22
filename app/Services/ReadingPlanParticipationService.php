<?php

namespace App\Services;

use App\Models\ReadingPlan;
use App\Models\ReadingPlanInvite;
use App\Models\ReadingPlanParticipation;
use App\Models\ReadingProgress;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReadingPlanParticipationService
{
    public function startNewParticipation(User $user, ReadingPlan $readingPlan, ?ReadingPlanInvite $invite = null, string $source = ReadingPlanParticipation::SOURCE_DIRECT): ReadingPlanParticipation
    {
        return DB::transaction(function () use ($user, $readingPlan, $invite, $source) {
            $today = Carbon::today();

            $this->endOtherActiveParticipations($user, $readingPlan->id, $today);
            $this->archiveExistingPlanParticipation($user, $readingPlan, $today);

            $nextParticipationNumber = (int) ReadingPlanParticipation::query()
                ->where('user_id', $user->id)
                ->where('reading_plan_id', $readingPlan->id)
                ->max('participation_number') + 1;

            $participation = ReadingPlanParticipation::create([
                'user_id' => $user->id,
                'reading_plan_id' => $readingPlan->id,
                'reading_plan_invite_id' => $invite?->id,
                'participation_number' => $nextParticipationNumber,
                'join_source' => $source,
                'started_on' => $today,
                'status' => ReadingPlanParticipation::STATUS_ACTIVE,
            ]);

            $existingPlan = $user->readingPlans()
                ->where('reading_plan_id', $readingPlan->id)
                ->first();

            if ($existingPlan) {
                $user->readingPlans()->updateExistingPivot($readingPlan->id, [
                    'joined_date' => $today,
                    'current_participation_id' => $participation->id,
                    'current_day' => 1,
                    'current_streak' => 0,
                    'completion_rate' => 0,
                    'is_active' => true,
                ]);
            } else {
                $user->readingPlans()->attach($readingPlan->id, [
                    'joined_date' => $today,
                    'current_participation_id' => $participation->id,
                    'current_day' => 1,
                    'current_streak' => 0,
                    'completion_rate' => 0,
                    'is_active' => true,
                ]);
            }

            return $participation;
        });
    }

    public function markParticipationLeft(User $user, ReadingPlan $readingPlan): void
    {
        $participationId = $user->currentParticipationIdForPlan($readingPlan);

        if ($participationId) {
            $participation = ReadingPlanParticipation::query()->find($participationId);

            if ($participation && $participation->status === ReadingPlanParticipation::STATUS_ACTIVE) {
                $participation->update([
                    'ended_on' => Carbon::today(),
                    'status' => ReadingPlanParticipation::STATUS_LEFT,
                    'summary' => $this->summaryForParticipation($participation),
                ]);
            }
        }
    }

    private function endOtherActiveParticipations(User $user, int $exceptPlanId, Carbon $today): void
    {
        $otherActivePlans = $user->readingPlans()
            ->where('user_reading_plans.is_active', true)
            ->where('reading_plans.id', '!=', $exceptPlanId)
            ->get();

        foreach ($otherActivePlans as $plan) {
            if ($plan->pivot?->current_participation_id) {
                $participation = ReadingPlanParticipation::query()->find($plan->pivot->current_participation_id);

                if ($participation && $participation->status === ReadingPlanParticipation::STATUS_ACTIVE) {
                    $participation->update([
                        'ended_on' => $today,
                        'status' => ReadingPlanParticipation::STATUS_SWITCHED,
                        'summary' => $this->summaryForParticipation($participation),
                    ]);
                }
            }

            $user->readingPlans()->updateExistingPivot($plan->id, [
                'is_active' => false,
            ]);
        }
    }

    private function archiveExistingPlanParticipation(User $user, ReadingPlan $readingPlan, Carbon $today): void
    {
        $existingPlan = $user->readingPlans()
            ->where('reading_plan_id', $readingPlan->id)
            ->first();

        if (! $existingPlan || ! $existingPlan->pivot?->current_participation_id) {
            return;
        }

        $participation = ReadingPlanParticipation::query()->find($existingPlan->pivot->current_participation_id);

        if (! $participation || $participation->status !== ReadingPlanParticipation::STATUS_ACTIVE) {
            return;
        }

        $participation->update([
            'ended_on' => $today,
            'status' => ReadingPlanParticipation::STATUS_RESTARTED,
            'summary' => $this->summaryForParticipation($participation),
        ]);
    }

    private function summaryForParticipation(ReadingPlanParticipation $participation): array
    {
        $progress = ReadingProgress::query()
            ->where('reading_plan_participation_id', $participation->id);

        return [
            'completed_days' => $progress->count(),
            'last_completed_on' => $progress->max('completed_date'),
        ];
    }
}
