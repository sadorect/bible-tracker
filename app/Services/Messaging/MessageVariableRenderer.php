<?php

namespace App\Services\Messaging;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class MessageVariableRenderer
{
    public function __construct(
        private readonly HierarchyMessageRoutingService $routingService,
        private readonly UserProgressSnapshotService $snapshotService,
    ) {
    }

    public function availableVariables(): array
    {
        return [
            'user.name' => 'Recipient name',
            'user.email' => 'Recipient email',
            'leader.name' => 'Immediate leader name',
            'hierarchy.name' => 'Recipient group name',
            'hierarchy.type' => 'Recipient group type',
            'parent_hierarchy.name' => 'Parent group name',
            'plan.name' => 'Active plan name',
            'plan.type' => 'Active plan type',
            'progress.training_progress' => 'Training completion summary',
            'progress.expected_day' => 'Expected reading day',
            'progress.completed_days' => 'Completed reading days',
            'progress.pace_status' => 'Current pace label',
            'progress.last_completion_date' => 'Last completion date',
            'app.name' => 'Application name',
        ];
    }

    public function render(string $template, array $context): string
    {
        return preg_replace_callback('/\{\{\s*([a-z0-9_\.]+)\s*\}\}/i', function (array $matches) use ($context) {
            $value = Arr::get($context, $matches[1]);

            return $value === null ? '' : (string) $value;
        }, $template) ?? $template;
    }

    public function renderForRecipient(string $subjectTemplate, string $bodyTemplate, User $recipient, ?User $sender = null): array
    {
        $context = $this->contextForRecipient($recipient, $sender);

        return [
            'subject' => $this->render($subjectTemplate, $context),
            'body' => $this->render($bodyTemplate, $context),
        ];
    }

    public function contextForRecipient(User $recipient, ?User $sender = null): array
    {
        $recipient->loadMissing(['hierarchy.parent', 'readingPlans.trainingResources', 'readingPlans.dailyReadings', 'readingProgress.dailyReading', 'trainingCompletions']);

        $snapshot = $this->snapshotService->build($recipient);
        $hierarchy = $snapshot['hierarchy'];
        $parentHierarchy = $hierarchy?->parent;
        $activePlan = $snapshot['active_plan'];
        $leader = $this->routingService->immediateLeaderFor($recipient) ?: $sender;

        return [
            'user' => [
                'name' => $recipient->name,
                'email' => $recipient->email,
            ],
            'leader' => [
                'name' => $leader?->name,
            ],
            'hierarchy' => [
                'name' => $hierarchy?->name,
                'type' => $hierarchy?->type ? ucfirst($hierarchy->type) : null,
            ],
            'parent_hierarchy' => [
                'name' => $parentHierarchy?->name,
            ],
            'plan' => [
                'name' => $activePlan?->name,
                'type' => $activePlan?->type_label,
            ],
            'progress' => [
                'training_progress' => $snapshot['training_progress'],
                'expected_day' => $snapshot['expected_day'],
                'completed_days' => $snapshot['completed_days'],
                'pace_status' => $snapshot['status_label'],
                'last_completion_date' => $snapshot['last_completion_date']
                    ? Carbon::parse($snapshot['last_completion_date'])->format('M d, Y')
                    : null,
            ],
            'app' => [
                'name' => config('app.name'),
            ],
        ];
    }
}
