<?php

namespace App\Services\Hierarchy;

use App\Models\Hierarchy;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class HierarchyWorkflowService
{
    private const TYPE_LABELS = [
        'clan' => 'Clan',
        'squad' => 'Squad',
        'platoon' => 'Platoon',
        'batch' => 'Batch',
        'team' => 'Team',
    ];

    public function previewHorizontalMigration(Hierarchy $source, Hierarchy $destinationParent): array
    {
        $source = $source->loadMissing(['parent.parent.parent.parent', 'leader']);
        $destinationParent = $destinationParent->loadMissing(['parent.parent.parent.parent', 'leader']);

        $this->ensureValidHorizontalMigration($source, $destinationParent);

        $branchHierarchies = Hierarchy::query()
            ->with(['leader', 'parent.parent.parent.parent'])
            ->whereIn('id', $source->descendantIdsIncludingSelf())
            ->ordered()
            ->get();

        $impactedUsers = User::query()
            ->with(['hierarchy.parent.parent.parent.parent'])
            ->whereIn('hierarchy_id', $branchHierarchies->pluck('id'))
            ->orderBy('name')
            ->get();

        $impactedLeaders = $branchHierarchies
            ->filter(fn (Hierarchy $hierarchy) => $hierarchy->leader !== null)
            ->map(fn (Hierarchy $hierarchy) => [
                'hierarchy' => $hierarchy,
                'leader' => $hierarchy->leader,
                'current_path' => $hierarchy->displayPath(),
                'future_path' => $this->futurePathAfterMigration($hierarchy, $source, $destinationParent),
            ])
            ->values();

        return [
            'source' => $source,
            'current_parent' => $source->parent,
            'destination_parent' => $destinationParent,
            'branch_hierarchies' => $branchHierarchies->map(fn (Hierarchy $hierarchy) => [
                'hierarchy' => $hierarchy,
                'type_label' => $this->typeLabel($hierarchy->type),
                'current_path' => $hierarchy->displayPath(),
                'future_path' => $this->futurePathAfterMigration($hierarchy, $source, $destinationParent),
                'direct_members_count' => $impactedUsers->where('hierarchy_id', $hierarchy->id)->count(),
            ])->values(),
            'impacted_users' => $impactedUsers,
            'impacted_leaders' => $impactedLeaders,
            'summary' => [
                'direct_members' => $impactedUsers->where('hierarchy_id', $source->id)->count(),
                'branch_members' => $impactedUsers->count(),
                'descendant_groups' => max($branchHierarchies->count() - 1, 0),
                'branch_leaders' => $impactedLeaders->count(),
            ],
        ];
    }

    public function executeHorizontalMigration(Hierarchy $source, Hierarchy $destinationParent): array
    {
        $preview = $this->previewHorizontalMigration($source, $destinationParent);

        $source->update([
            'parent_id' => $destinationParent->id,
        ]);

        $source->refresh()->load(['parent.parent.parent.parent', 'leader']);

        return [
            'source' => $source,
            'previous_parent' => $preview['current_parent'],
            'destination_parent' => $destinationParent->fresh(['parent.parent.parent.parent']),
            'previous_path' => $preview['source']->displayPath(),
            'new_path' => $source->displayPath(),
            'summary' => $preview['summary'],
        ];
    }

    public function previewSiblingMerge(Hierarchy $source, Hierarchy $target): array
    {
        $source = $source->loadMissing(['parent.parent.parent.parent', 'leader']);
        $target = $target->loadMissing(['parent.parent.parent.parent', 'leader']);

        $this->ensureValidSiblingMerge($source, $target);

        $sourceBranch = Hierarchy::query()
            ->with(['leader', 'parent.parent.parent.parent'])
            ->whereIn('id', $source->descendantIdsIncludingSelf())
            ->ordered()
            ->get();

        $targetBranch = Hierarchy::query()
            ->with(['leader', 'parent.parent.parent.parent'])
            ->whereIn('id', $target->descendantIdsIncludingSelf())
            ->ordered()
            ->get();

        $sourceUsers = User::query()
            ->with(['hierarchy.parent.parent.parent.parent'])
            ->whereIn('hierarchy_id', $sourceBranch->pluck('id'))
            ->orderBy('name')
            ->get();

        $targetUsers = User::query()
            ->with(['hierarchy.parent.parent.parent.parent'])
            ->whereIn('hierarchy_id', $targetBranch->pluck('id'))
            ->orderBy('name')
            ->get();

        $destinationTeams = $this->destinationTeamsForMerge($source, $sourceBranch, $targetBranch, $target);

        $leaderOptions = collect([
            $target->leader ? [
                'id' => $target->leader->id,
                'label' => "Keep {$target->leader->name} as merged leader",
                'description' => "{$target->name} stays in place and {$source->name} is folded into it.",
            ] : null,
            $source->leader ? [
                'id' => $source->leader->id,
                'label' => "Move {$source->leader->name} into the merged leadership slot",
                'description' => "{$source->leader->name} becomes the leader of {$target->name}.",
            ] : null,
            [
                'id' => null,
                'label' => 'Leave the merged group vacant',
                'description' => 'No leader will be assigned after the merge is confirmed.',
            ],
        ])->filter()->values();

        return [
            'source' => $source,
            'target' => $target,
            'source_branch' => $sourceBranch,
            'source_users' => $sourceUsers,
            'target_users' => $targetUsers,
            'destination_teams' => $destinationTeams,
            'leader_options' => $leaderOptions,
            'default_merged_leader_id' => $target->leader_id ?: $source->leader_id,
            'summary' => [
                'source_direct_members' => $sourceUsers->where('hierarchy_id', $source->id)->count(),
                'source_branch_members' => $sourceUsers->count(),
                'source_descendant_groups' => max($sourceBranch->count() - 1, 0),
                'target_branch_members_after_merge' => $sourceUsers->count() + $targetUsers->count(),
                'source_branch_leaders' => $sourceBranch->whereNotNull('leader_id')->count(),
            ],
        ];
    }

    public function executeSiblingMerge(Hierarchy $source, Hierarchy $target, array $options): array
    {
        $preview = $this->previewSiblingMerge($source, $target);

        $source->refresh()->load('leader');
        $target->refresh()->load('leader');

        $sourceLeader = $source->leader;
        $targetLeader = $target->leader;
        $mergedLeaderId = array_key_exists('merged_leader_id', $options) && $options['merged_leader_id'] !== null
            ? (int) $options['merged_leader_id']
            : null;

        $allowedLeaderIds = collect([$sourceLeader?->id, $targetLeader?->id])->filter()->values();

        if ($mergedLeaderId !== null && ! $allowedLeaderIds->contains($mergedLeaderId)) {
            throw ValidationException::withMessages([
                'merged_leader_id' => 'Choose a merged leader from the two groups involved in this merge.',
            ]);
        }

        $destinationTeamIds = $preview['destination_teams']->pluck('id')->all();

        $sourceLeaderPlan = $this->normalizeOutgoingLeaderPlan(
            prefix: 'source',
            leader: $sourceLeader,
            mergedLeaderId: $mergedLeaderId,
            options: $options,
            destinationTeamIds: $destinationTeamIds,
        );

        $targetLeaderPlan = $this->normalizeOutgoingLeaderPlan(
            prefix: 'target',
            leader: $targetLeader,
            mergedLeaderId: $mergedLeaderId,
            options: $options,
            destinationTeamIds: $destinationTeamIds,
        );

        Hierarchy::query()
            ->where('parent_id', $source->id)
            ->update(['parent_id' => $target->id]);

        $usersToMoveQuery = User::query()->where('hierarchy_id', $source->id);

        if ($sourceLeaderPlan['leader']) {
            $usersToMoveQuery->where('id', '!=', $sourceLeaderPlan['leader']->id);
        }

        $usersToMoveQuery->update([
            'hierarchy_id' => $target->id,
        ]);

        $target->update([
            'leader_id' => $mergedLeaderId,
        ]);

        if ($mergedLeaderId !== null) {
            User::query()->whereKey($mergedLeaderId)->update([
                'role' => $this->expectedLeaderRoleForType($target->type),
                'hierarchy_id' => $target->id,
            ]);
        }

        $this->applyOutgoingLeaderPlan($sourceLeaderPlan);
        $this->applyOutgoingLeaderPlan($targetLeaderPlan);

        $source->delete();

        $target->refresh()->load(['leader', 'parent.parent.parent.parent']);

        return [
            'source_name' => $preview['source']->name,
            'target' => $target,
            'merged_leader_id' => $mergedLeaderId,
            'source_leader_plan' => $sourceLeaderPlan,
            'target_leader_plan' => $targetLeaderPlan,
            'summary' => $preview['summary'],
        ];
    }

    public function typeLabel(string $type): string
    {
        return self::TYPE_LABELS[$type] ?? ucfirst($type);
    }

    private function ensureValidHorizontalMigration(Hierarchy $source, Hierarchy $destinationParent): void
    {
        $currentParent = $source->parent;

        if (! $currentParent) {
            throw ValidationException::withMessages([
                'source_hierarchy_id' => 'This workflow only supports moving groups that already sit under a parent.',
            ]);
        }

        if ($destinationParent->id === $currentParent->id) {
            throw ValidationException::withMessages([
                'destination_parent_id' => 'Choose a different parent so the move actually changes the branch.',
            ]);
        }

        if ($destinationParent->type !== $currentParent->type) {
            throw ValidationException::withMessages([
                'destination_parent_id' => 'Pick a destination parent at the same hierarchy level as the current parent.',
            ]);
        }

        if ($source->descendantIdsIncludingSelf()->contains($destinationParent->id)) {
            throw ValidationException::withMessages([
                'destination_parent_id' => 'The destination parent cannot be inside the branch you are moving.',
            ]);
        }
    }

    private function ensureValidSiblingMerge(Hierarchy $source, Hierarchy $target): void
    {
        if ($source->is($target)) {
            throw ValidationException::withMessages([
                'target_hierarchy_id' => 'Choose a different target group for this merge.',
            ]);
        }

        if ($source->type !== $target->type) {
            throw ValidationException::withMessages([
                'target_hierarchy_id' => 'Only sibling groups at the same hierarchy level can be merged.',
            ]);
        }

        if ((int) $source->parent_id !== (int) $target->parent_id) {
            throw ValidationException::withMessages([
                'target_hierarchy_id' => 'The merge target must share the same parent as the source group.',
            ]);
        }
    }

    private function futurePathAfterMigration(Hierarchy $hierarchy, Hierarchy $source, Hierarchy $destinationParent): string
    {
        $segments = [];
        $current = $hierarchy;

        while ($current) {
            $segments[] = $current->name;

            if ($current->id === $source->id) {
                break;
            }

            $current = $current->relationLoaded('parent')
                ? $current->parent
                : $current->parent()->first();
        }

        return collect([$destinationParent->displayPath()])
            ->merge(array_reverse($segments))
            ->implode(' / ');
    }

    private function destinationTeamsForMerge(Hierarchy $source, Collection $sourceBranch, Collection $targetBranch, Hierarchy $target): Collection
    {
        $teamIds = collect();

        if ($target->type === 'team') {
            $teamIds->push($target->id);
        }

        $teamIds = $teamIds
            ->merge($sourceBranch->where('type', 'team')->pluck('id')->reject(fn (int $id) => $id === $source->id))
            ->merge($targetBranch->where('type', 'team')->pluck('id'))
            ->unique()
            ->values();

        if ($teamIds->isEmpty()) {
            return collect();
        }

        return Hierarchy::query()
            ->with('parent.parent.parent.parent')
            ->whereIn('id', $teamIds)
            ->ordered()
            ->get();
    }

    private function normalizeOutgoingLeaderPlan(
        string $prefix,
        ?User $leader,
        ?int $mergedLeaderId,
        array $options,
        array $destinationTeamIds,
    ): array {
        if (! $leader || $leader->id === $mergedLeaderId) {
            return [
                'leader' => null,
                'disposition' => null,
                'team_id' => null,
            ];
        }

        $disposition = $options["{$prefix}_leader_disposition"] ?? 'unassign';
        $teamId = $options["{$prefix}_leader_team_id"] ?? null;

        if (! in_array($disposition, ['descendant_team', 'unassign'], true)) {
            throw ValidationException::withMessages([
                "{$prefix}_leader_disposition" => 'Choose how to reassign the outgoing leader.',
            ]);
        }

        if ($disposition === 'descendant_team') {
            if (! $teamId || ! in_array((int) $teamId, $destinationTeamIds, true)) {
                throw ValidationException::withMessages([
                    "{$prefix}_leader_team_id" => 'Choose a valid team inside the merged branch for the outgoing leader.',
                ]);
            }
        }

        return [
            'leader' => $leader,
            'disposition' => $disposition,
            'team_id' => $disposition === 'descendant_team' ? (int) $teamId : null,
        ];
    }

    private function applyOutgoingLeaderPlan(array $plan): void
    {
        if (! $plan['leader']) {
            return;
        }

        $plan['leader']->update([
            'role' => User::ROLE_MEMBER,
            'hierarchy_id' => $plan['team_id'],
        ]);
    }

    private function expectedLeaderRoleForType(string $type): string
    {
        return match ($type) {
            'clan' => User::ROLE_CLAN_LEADER,
            'squad' => User::ROLE_SQUAD_LEADER,
            'platoon' => User::ROLE_PLATOON_LEADER,
            'batch' => User::ROLE_BATCH_LEADER,
            'team' => User::ROLE_TEAM_LEADER,
            default => User::ROLE_MEMBER,
        };
    }
}
