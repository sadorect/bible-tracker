<?php

namespace App\Services\Messaging;

use App\Models\Hierarchy;
use App\Models\User;
use Illuminate\Support\Collection;

class HierarchyMessageRoutingService
{
    public function adminRecipients(): Collection
    {
        return User::query()
            ->where('role', User::ROLE_ADMIN)
            ->orderBy('name')
            ->get();
    }

    public function upwardRecipientsFor(User $sender): Collection
    {
        if ($sender->isAdmin()) {
            return collect();
        }

        $hierarchy = $this->hierarchyFor($sender);

        if (! $hierarchy) {
            return $this->adminRecipients()->where('id', '!=', $sender->id)->values();
        }

        $currentLeader = $hierarchy->relationLoaded('leader')
            ? $hierarchy->leader
            : $hierarchy->leader()->first();

        if ($currentLeader && $currentLeader->id !== $sender->id) {
            return collect([$currentLeader]);
        }

        $parent = $hierarchy->relationLoaded('parent')
            ? $hierarchy->parent
            : $hierarchy->parent()->with('leader')->first();

        if ($parent?->leader && $parent->leader->id !== $sender->id) {
            return collect([$parent->leader]);
        }

        return $this->adminRecipients()->where('id', '!=', $sender->id)->values();
    }

    public function immediateLeaderFor(User $user): ?User
    {
        return $this->upwardRecipientsFor($user)->first();
    }

    public function downwardHierarchyIdsFor(User $sender, array $selectedHierarchyIds = []): Collection
    {
        if ($sender->isAdmin()) {
            $baseIds = Hierarchy::query()->pluck('id');
        } else {
            $leadHierarchy = $sender->currentLeadershipHierarchy();

            if (! $leadHierarchy) {
                return collect();
            }

            $baseIds = $leadHierarchy->descendantIdsIncludingSelf();
        }

        if ($selectedHierarchyIds === []) {
            return $baseIds->values();
        }

        $selectedIds = collect($selectedHierarchyIds)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($selectedIds->isEmpty()) {
            return $baseIds->values();
        }

        $selectedHierarchies = Hierarchy::query()
            ->whereIn('id', $selectedIds)
            ->with('children')
            ->get()
            ->filter(fn (Hierarchy $hierarchy) => $baseIds->contains($hierarchy->id));

        if ($selectedHierarchies->isEmpty()) {
            return collect();
        }

        return $selectedHierarchies
            ->flatMap(fn (Hierarchy $hierarchy) => $hierarchy->descendantIdsIncludingSelf())
            ->intersect($baseIds)
            ->unique()
            ->values();
    }

    public function hierarchyFor(User $user): ?Hierarchy
    {
        if ($user->relationLoaded('hierarchy') && $user->hierarchy) {
            return $user->hierarchy;
        }

        return $user->hierarchy()->with('parent', 'leader')->first()
            ?? $user->currentLeadershipHierarchy();
    }
}
