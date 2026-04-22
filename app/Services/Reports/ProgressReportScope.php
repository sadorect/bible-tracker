<?php

namespace App\Services\Reports;

use App\Models\Hierarchy;
use App\Models\ReadingPlan;
use App\Models\SystemRole;
use App\Models\User;
use App\Services\Messaging\UserProgressSnapshotService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ProgressReportScope
{
    public function __construct(
        private readonly UserProgressSnapshotService $snapshotService,
    ) {
    }

    public function isGlobal(User $actor): bool
    {
        if ($actor->isAdmin() || $actor->hasSystemRole(SystemRole::SUPER_ADMIN)) {
            return true;
        }

        return ! $actor->isLeader();
    }

    public function leadHierarchy(User $actor): ?Hierarchy
    {
        return $this->isGlobal($actor) ? null : $actor->currentLeadershipHierarchy();
    }

    public function scopeLabel(User $actor): string
    {
        if ($this->isGlobal($actor)) {
            return 'Global reporting scope';
        }

        $hierarchy = $this->leadHierarchy($actor);

        return $hierarchy
            ? 'Reporting scope: '.$hierarchy->displayPath()
            : 'Reporting scope: no hierarchy assigned';
    }

    public function accessibleHierarchyIds(User $actor): ?Collection
    {
        if ($this->isGlobal($actor)) {
            return null;
        }

        $leadHierarchy = $this->leadHierarchy($actor);

        return $leadHierarchy?->descendantIdsIncludingSelf() ?? collect();
    }

    public function accessibleUsersQuery(User $actor): Builder
    {
        return $this->scopedUsersQuery($actor)->with(['hierarchy.parent']);
    }

    public function accessibleUsers(User $actor): Collection
    {
        return $this->accessibleUsersQuery($actor)
            ->orderBy('name')
            ->get();
    }

    public function accessibleUserIds(User $actor): ?Collection
    {
        if ($this->isGlobal($actor)) {
            return null;
        }

        return $this->scopedUsersQuery($actor)
            ->select('users.id')
            ->orderBy('users.id')
            ->pluck('users.id');
    }

    public function accessiblePlans(User $actor): Collection
    {
        if ($this->isGlobal($actor)) {
            return ReadingPlan::query()->orderBy('name')->get();
        }

        $userIds = $this->accessibleUserIds($actor) ?? collect();

        if ($userIds->isEmpty()) {
            return collect();
        }

        return ReadingPlan::query()
            ->whereHas('users', fn (Builder $query) => $query->whereIn('users.id', $userIds))
            ->orderBy('name')
            ->get();
    }

    public function accessibleHierarchies(User $actor): Collection
    {
        if ($this->isGlobal($actor)) {
            return Hierarchy::query()->with(['parent', 'leader'])->ordered()->get();
        }

        $hierarchyIds = $this->accessibleHierarchyIds($actor) ?? collect();

        if ($hierarchyIds->isEmpty()) {
            return collect();
        }

        return Hierarchy::query()
            ->with(['parent', 'leader'])
            ->whereIn('id', $hierarchyIds)
            ->ordered()
            ->get();
    }

    public function accessibleUserIdsMatchingDerivedFilters(User $actor, array $filters): ?array
    {
        $paceStatus = $filters['pace_status'] ?? '';
        $trainingStatus = $filters['training_status'] ?? '';

        if ($paceStatus === '' && $trainingStatus === '') {
            return null;
        }

        $users = $this->accessibleUsersQuery($actor)
            ->with([
                'hierarchy.parent',
                'readingPlans.trainingResources',
                'readingPlans.dailyReadings',
                'readingProgress.dailyReading',
                'trainingCompletions',
            ])
            ->orderBy('name')
            ->get();

        return $users
            ->map(fn (User $user) => $this->snapshotService->build($user))
            ->filter(function (array $snapshot) use ($paceStatus, $trainingStatus) {
                if ($paceStatus !== '' && $snapshot['status_key'] !== $paceStatus) {
                    return false;
                }

                if ($trainingStatus !== '' && $snapshot['training_status'] !== $trainingStatus) {
                    return false;
                }

                return true;
            })
            ->pluck('user.id')
            ->values()
            ->all();
    }

    public function canAccessUser(User $actor, User $subject): bool
    {
        if ($this->isGlobal($actor)) {
            return true;
        }

        return $this->accessibleUsersQuery($actor)
            ->whereKey($subject->id)
            ->exists();
    }

    public function canAccessPlan(User $actor, ReadingPlan $plan): bool
    {
        if ($this->isGlobal($actor)) {
            return true;
        }

        $userIds = $this->accessibleUserIds($actor) ?? collect();

        if ($userIds->isEmpty()) {
            return false;
        }

        return $plan->users()
            ->whereIn('users.id', $userIds)
            ->exists();
    }

    private function scopedUsersQuery(User $actor): Builder
    {
        $query = User::query();

        if ($this->isGlobal($actor)) {
            return $query;
        }

        $hierarchyIds = $this->accessibleHierarchyIds($actor) ?? collect();

        if ($hierarchyIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        $leaderIds = Hierarchy::query()
            ->whereIn('id', $hierarchyIds)
            ->pluck('leader_id')
            ->filter()
            ->values();

        return $query->where(function (Builder $scoped) use ($hierarchyIds, $leaderIds) {
            $scoped->whereIn('hierarchy_id', $hierarchyIds)
                ->orWhereIn('id', $leaderIds);
        });
    }
}
