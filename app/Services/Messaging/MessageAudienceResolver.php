<?php

namespace App\Services\Messaging;

use App\Models\Hierarchy;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class MessageAudienceResolver
{
    public function __construct(
        private readonly HierarchyMessageRoutingService $routingService,
        private readonly UserProgressSnapshotService $snapshotService,
    ) {
    }

    public function resolveDownwardRecipients(User $sender, array $filters = []): EloquentCollection
    {
        $selectedHierarchyIds = array_map('intval', $filters['hierarchy_ids'] ?? []);
        $branchIds = $this->routingService->downwardHierarchyIdsFor($sender, $selectedHierarchyIds);

        if (! $sender->isAdmin() && $branchIds->isEmpty()) {
            return new EloquentCollection();
        }

        $query = User::query()
            ->with([
                'hierarchy.parent',
                'readingPlans.trainingResources',
                'readingPlans.dailyReadings',
                'readingProgress.dailyReading',
                'trainingCompletions',
            ])
            ->whereKeyNot($sender->id);

        if (! $sender->isAdmin() || $selectedHierarchyIds !== []) {
            $leadersInScope = Hierarchy::query()
                ->whereIn('id', $branchIds)
                ->pluck('leader_id')
                ->filter()
                ->values();

            $query->where(function ($scope) use ($branchIds, $leadersInScope) {
                $scope->whereIn('hierarchy_id', $branchIds);

                if ($leadersInScope->isNotEmpty()) {
                    $scope->orWhereIn('id', $leadersInScope);
                }
            });
        }

        $roles = collect($filters['roles'] ?? [])
            ->filter()
            ->values();

        if ($roles->isNotEmpty()) {
            $query->whereIn('role', $roles->all());
        }

        if (($filters['active_state'] ?? '') === 'active') {
            $query->whereNotNull('email_verified_at');
        } elseif (($filters['active_state'] ?? '') === 'inactive') {
            $query->whereNull('email_verified_at');
        }

        if (! empty($filters['active_plan_id'])) {
            $activePlanId = (int) $filters['active_plan_id'];

            $query->whereHas('readingPlans', function ($plans) use ($activePlanId) {
                $plans->where('reading_plans.id', $activePlanId)
                    ->wherePivot('is_active', true);
            });
        }

        if (! empty($filters['plan_type'])) {
            $planType = $filters['plan_type'];

            $query->whereHas('readingPlans', function ($plans) use ($planType) {
                $plans->where('reading_plans.type', $planType)
                    ->wherePivot('is_active', true);
            });
        }

        $recipients = $query->orderBy('name')->get();

        $trainingStatus = $filters['training_status'] ?? '';
        $paceStatus = $filters['pace_status'] ?? '';

        if ($trainingStatus === '' && $paceStatus === '') {
            return $recipients;
        }

        return $recipients
            ->filter(function (User $recipient) use ($trainingStatus, $paceStatus) {
                $snapshot = $this->snapshotService->build($recipient);

                if ($trainingStatus !== '' && $snapshot['training_status'] !== $trainingStatus) {
                    return false;
                }

                if ($paceStatus !== '' && $snapshot['status_key'] !== $paceStatus) {
                    return false;
                }

                return true;
            })
            ->values();
    }

    public function resolveUpwardRecipients(User $sender): EloquentCollection
    {
        return new EloquentCollection($this->routingService->upwardRecipientsFor($sender)->all());
    }

    public function resolveReplyRecipients(User $sender, Message $threadRoot): EloquentCollection
    {
        if (! $sender->isAdmin() && ! $sender->isLeader()) {
            return $this->resolveUpwardRecipients($sender);
        }

        $participants = $this->threadParticipantIds($threadRoot)->diff([$sender->id])->values();

        if ($participants->isEmpty()) {
            return new EloquentCollection();
        }

        $allowedDownward = $this->resolveDownwardRecipients($sender);
        $allowedIds = $allowedDownward->pluck('id');

        return $allowedDownward
            ->whereIn('id', $participants->intersect($allowedIds))
            ->values();
    }

    public function threadParticipantIds(Message $threadRoot): Collection
    {
        $messages = Message::query()
            ->where('thread_root_id', $threadRoot->thread_root_id ?: $threadRoot->id)
            ->with('recipients')
            ->get();

        return $messages
            ->flatMap(function (Message $message) {
                return collect([$message->sender_id])->merge($message->recipients->pluck('recipient_id'));
            })
            ->filter()
            ->unique()
            ->values();
    }
}
