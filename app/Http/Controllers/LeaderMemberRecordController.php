<?php

namespace App\Http\Controllers;

use App\Models\Hierarchy;
use App\Models\ReadingPlanParticipation;
use App\Models\User;
use App\Services\Messaging\UserProgressSnapshotService;
use Illuminate\Support\Facades\Auth;

class LeaderMemberRecordController extends Controller
{
    public function __construct(
        private readonly UserProgressSnapshotService $snapshotService,
    ) {
    }

    public function show(User $member)
    {
        $actor = Auth::user();

        abort_unless($actor?->isLeader(), 403);

        $leadHierarchy = $actor->currentLeadershipHierarchy();

        abort_unless($leadHierarchy, 403);

        $member = $this->resolveScopedMember($leadHierarchy, $member);
        $member->load([
            'hierarchy.parent.parent.parent.parent',
            'readingPlans.dailyReadings',
            'readingPlans.trainingResources',
            'readingProgress.dailyReading.readingPlan',
            'readingPlanParticipations.readingPlan.dailyReadings',
            'readingPlanParticipations.progress.dailyReading',
            'trainingCompletions',
        ]);

        $snapshot = $this->snapshotService->build($member);
        $currentParticipationIds = $member->readingPlans
            ->pluck('pivot.current_participation_id', 'id')
            ->map(fn ($value) => $value ? (int) $value : null);

        $participations = $member->readingPlanParticipations
            ->sortByDesc(fn (ReadingPlanParticipation $participation) => $participation->started_on?->timestamp ?? $participation->created_at?->timestamp ?? 0)
            ->values()
            ->map(function (ReadingPlanParticipation $participation) use ($currentParticipationIds) {
                $plan = $participation->readingPlan;
                $requiredDays = $plan?->dailyReadings?->where('is_break_day', false)->count() ?? 0;
                $completedDays = $participation->progress
                    ->pluck('daily_reading_id')
                    ->filter()
                    ->unique()
                    ->count();

                return [
                    'participation' => $participation,
                    'plan' => $plan,
                    'required_days' => $requiredDays,
                    'completed_days' => $completedDays,
                    'completion_rate' => $requiredDays > 0 ? round(($completedDays / $requiredDays) * 100, 1) : 0,
                    'last_completion_date' => $participation->progress->sortByDesc('completed_date')->first()?->completed_date,
                    'is_current' => (int) ($currentParticipationIds[$plan?->id] ?? 0) === $participation->id,
                ];
            });

        $recentActivity = $member->readingProgress
            ->sortByDesc('completed_date')
            ->take(12)
            ->values();

        return view('leader.records.member-detail', [
            'member' => $member,
            'leadHierarchy' => $leadHierarchy,
            'snapshot' => $snapshot,
            'participations' => $participations,
            'recentActivity' => $recentActivity,
            'reportUrl' => $actor->canAccessAdminPanel() && $actor->hasPermissionTo('progress.view')
                ? route('admin.progress.user', $member)
                : null,
            'reportsIndexUrl' => $actor->canAccessAdminPanel() && $actor->hasPermissionTo('progress.view')
                ? route('admin.progress.index', ['user_id' => $member->id])
                : null,
        ]);
    }

    public function participation(User $member, ReadingPlanParticipation $participation)
    {
        $actor = Auth::user();

        abort_unless($actor?->isLeader(), 403);

        $leadHierarchy = $actor->currentLeadershipHierarchy();

        abort_unless($leadHierarchy, 403);

        $member = $this->resolveScopedMember($leadHierarchy, $member);

        abort_unless($participation->user_id === $member->id, 404);

        $participation->load([
            'readingPlan.dailyReadings',
            'progress.dailyReading',
        ]);

        $requiredDays = $participation->readingPlan?->dailyReadings?->where('is_break_day', false)->count() ?? 0;
        $completedDays = $participation->progress
            ->pluck('daily_reading_id')
            ->filter()
            ->unique()
            ->count();

        return view('leader.records.participation-detail', [
            'member' => $member,
            'leadHierarchy' => $leadHierarchy,
            'participation' => $participation,
            'requiredDays' => $requiredDays,
            'completedDays' => $completedDays,
            'completionRate' => $requiredDays > 0 ? round(($completedDays / $requiredDays) * 100, 1) : 0,
            'reportUrl' => $actor->canAccessAdminPanel() && $actor->hasPermissionTo('progress.view')
                ? route('admin.progress.user', $member)
                : null,
        ]);
    }

    private function resolveScopedMember(Hierarchy $leadHierarchy, User $member): User
    {
        $scopeHierarchyIds = $leadHierarchy->descendantIdsIncludingSelf();
        $leaderIds = Hierarchy::query()
            ->whereIn('id', $scopeHierarchyIds)
            ->pluck('leader_id')
            ->filter();

        $scopedMember = User::query()
            ->whereKey($member->id)
            ->where(function ($query) use ($scopeHierarchyIds, $leaderIds) {
                $query->whereIn('hierarchy_id', $scopeHierarchyIds)
                    ->orWhereIn('id', $leaderIds);
            })
            ->first();

        abort_unless($scopedMember, 403);

        return $scopedMember;
    }
}
