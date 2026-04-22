<?php

namespace App\Services\Automation;

use App\Models\Hierarchy;
use App\Models\ReadingPlan;
use App\Models\ReadingProgress;
use App\Models\SystemRole;
use App\Models\User;
use App\Notifications\AutomationAlertNotification;
use App\Services\Auditing\AuditLogger;
use App\Services\Messaging\UserProgressSnapshotService;
use App\Services\Plans\PlanLifecycleSettings;
use App\Services\Reports\ProgressReportScope;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AutomationRunner
{
    public function __construct(
        private readonly AutomationSettings $settings,
        private readonly UserProgressSnapshotService $snapshotService,
        private readonly ProgressReportScope $reportScope,
        private readonly AuditLogger $auditLogger,
        private readonly PlanLifecycleSettings $planLifecycleSettings,
    ) {
    }

    public function run(?CarbonInterface $today = null, bool $manual = false, ?User $actor = null): array
    {
        $today = $today ? Carbon::instance($today) : Carbon::today();

        $summary = [
            'plans_activated' => 0,
            'plans_closed' => 0,
            'member_reading_reminders' => 0,
            'member_training_reminders' => 0,
            'leader_digests' => 0,
            'admin_digests' => 0,
            'vacancy_alerts' => 0,
        ];

        if ($this->settings->lifecycleAutomationEnabled()) {
            ['activated' => $summary['plans_activated'], 'closed' => $summary['plans_closed']] = $this->syncPlanLifecycle($today);
        }

        [$summary['member_reading_reminders'], $summary['member_training_reminders']] = $this->sendMemberReminders($today);
        $summary['leader_digests'] = $this->sendLeaderDigests($today);
        $summary['admin_digests'] = $this->sendAdminDigests($today);
        $summary['vacancy_alerts'] = $this->sendVacancyAlerts($today);

        $this->settings->update([
            AutomationSettings::KEY_LAST_RUN_AT => now()->toDateTimeString(),
        ]);

        $this->auditLogger->log(
            'automation.run_completed',
            $actor,
            null,
            [
                'manual' => $manual,
                'date' => $today->toDateString(),
                'summary' => $summary,
            ],
            'Completed the daily automation cycle.',
        );

        return $summary;
    }

    private function syncPlanLifecycle(Carbon $today): array
    {
        $activated = 0;
        $closed = 0;

        $activatablePlans = ReadingPlan::query()
            ->where('lifecycle_status', ReadingPlan::STATUS_RECRUITING)
            ->whereDate('start_date', '<=', $today)
            ->orderBy('start_date')
            ->get();

        foreach ($activatablePlans as $plan) {
            if (! $this->canAutoActivate($plan)) {
                continue;
            }

            $plan->update(['lifecycle_status' => ReadingPlan::STATUS_ACTIVE]);
            $activated++;
        }

        ReadingPlan::query()
            ->where('lifecycle_status', ReadingPlan::STATUS_ACTIVE)
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', $today)
            ->each(function (ReadingPlan $plan) use (&$closed) {
                $plan->update(['lifecycle_status' => ReadingPlan::STATUS_CLOSED]);
                $closed++;
            });

        return [
            'activated' => $activated,
            'closed' => $closed,
        ];
    }

    private function canAutoActivate(ReadingPlan $plan): bool
    {
        $livePlans = ReadingPlan::query()->live()->whereKeyNot($plan->id);

        $typeLimit = $plan->type === ReadingPlan::TYPE_NEW_TESTAMENT
            ? $this->planLifecycleSettings->maxLiveNewTestament()
            : $this->planLifecycleSettings->maxLiveOldTestament();

        $typeLiveCount = (clone $livePlans)
            ->where('type', $plan->type)
            ->count();

        if ($typeLimit !== null && $typeLiveCount >= $typeLimit) {
            return false;
        }

        $totalLimit = $this->planLifecycleSettings->maxLiveTotal();
        $totalLiveCount = (clone $livePlans)->count();

        if ($totalLimit !== null && $totalLiveCount >= $totalLimit) {
            return false;
        }

        return true;
    }

    private function sendMemberReminders(Carbon $today): array
    {
        $readingReminders = 0;
        $trainingReminders = 0;

        $users = User::query()
            ->with([
                'readingPlans' => fn ($query) => $query->where('user_reading_plans.is_active', true)->with(['trainingResources', 'dailyReadings']),
                'hierarchy.parent',
                'readingProgress.dailyReading',
                'trainingCompletions',
            ])
            ->whereHas('readingPlans', fn (Builder $query) => $query->where('user_reading_plans.is_active', true))
            ->orderBy('name')
            ->get();

        foreach ($users as $user) {
            $snapshot = $this->snapshotService->build($user, $today);
            $activePlan = $snapshot['active_plan'];

            if (! $activePlan instanceof ReadingPlan) {
                continue;
            }

            if ($snapshot['status_key'] === 'in_training' && $this->settings->memberTrainingRemindersEnabled()) {
                $notificationKey = 'training-reminder-'.$today->toDateString().'-'.$activePlan->id;

                if (! $this->hasNotificationForToday($user, $notificationKey, $today)) {
                    $user->notify(new AutomationAlertNotification(
                        'Training still needs attention',
                        "Complete your {$activePlan->name} training resources so your reading days can unlock on time.",
                        $notificationKey,
                        route('dashboard'),
                        'Open dashboard',
                        'amber',
                        'training_reminder',
                    ));
                    $trainingReminders++;
                }
            }

            if (! $this->settings->memberReadingRemindersEnabled()) {
                continue;
            }

            if (! $activePlan->canRecordReadings($user, $today)) {
                continue;
            }

            $expectedDay = $activePlan->expectedCurrentDay($today);
            $expectedReading = $activePlan->dailyReadings()
                ->where('day_number', $expectedDay)
                ->first();

            if (! $expectedReading || $expectedReading->is_break_day) {
                continue;
            }

            $participationId = $user->currentParticipationIdForPlan($activePlan);
            $completedToday = ReadingProgress::query()
                ->where('user_id', $user->id)
                ->where('reading_plan_id', $activePlan->id)
                ->where('daily_reading_id', $expectedReading->id)
                ->when($participationId, fn (Builder $query) => $query->where('reading_plan_participation_id', $participationId))
                ->exists();

            if ($completedToday) {
                continue;
            }

            $notificationKey = 'reading-reminder-'.$today->toDateString().'-'.$activePlan->id.'-'.$expectedReading->id;

            if ($this->hasNotificationForToday($user, $notificationKey, $today)) {
                continue;
            }

            $user->notify(new AutomationAlertNotification(
                'Today\'s reading is still open',
                "Your {$activePlan->name} reading for day {$expectedReading->day_number} is waiting for today’s completion.",
                $notificationKey,
                route('dashboard'),
                'Record progress',
                $snapshot['status_key'] === 'catching_up' ? 'rose' : 'emerald',
                'reading_reminder',
            ));

            $readingReminders++;
        }

        return [$readingReminders, $trainingReminders];
    }

    private function sendLeaderDigests(Carbon $today): int
    {
        if (! $this->settings->leaderDigestsEnabled()) {
            return 0;
        }

        $sent = 0;

        $leaders = User::query()
            ->whereIn('role', User::leaderRoles())
            ->with(['hierarchy.parent', 'readingPlans.trainingResources', 'readingPlans.dailyReadings', 'readingProgress.dailyReading', 'trainingCompletions'])
            ->orderBy('name')
            ->get()
            ->filter(fn (User $leader) => $leader->currentLeadershipHierarchy() !== null);

        foreach ($leaders as $leader) {
            $users = $this->reportScope->accessibleUsers($leader)
                ->reject(fn (User $member) => $member->id === $leader->id)
                ->values();

            if ($users->isEmpty()) {
                continue;
            }

            $summary = $this->summarizeSnapshots(
                $users->map(fn (User $user) => $this->snapshotService->build($user, $today))
            );

            $notificationKey = 'leader-digest-'.$today->toDateString();

            if ($this->hasNotificationForToday($leader, $notificationKey, $today)) {
                continue;
            }

            $leader->notify(new AutomationAlertNotification(
                'Branch progress digest is ready',
                "{$summary['total']} people are in your span today: {$summary['catching_up']} catching up, {$summary['in_training']} in training, and {$summary['reading_ahead']} reading ahead.",
                $notificationKey,
                route('hierarchy.manage'),
                'Review branch',
                'indigo',
                'leader_digest',
            ));

            $sent++;
        }

        return $sent;
    }

    private function sendAdminDigests(Carbon $today): int
    {
        if (! $this->settings->adminDigestsEnabled()) {
            return 0;
        }

        $sent = 0;
        $recipients = User::query()
            ->with(['readingPlans.trainingResources', 'readingPlans.dailyReadings', 'readingProgress.dailyReading', 'trainingCompletions'])
            ->get()
            ->filter(fn (User $user) => $user->canAccessAdminPanel() && $user->hasPermissionTo('dashboard.view'));

        $memberSnapshots = User::query()
            ->with(['hierarchy.parent', 'readingPlans.trainingResources', 'readingPlans.dailyReadings', 'readingProgress.dailyReading', 'trainingCompletions'])
            ->where('role', User::ROLE_MEMBER)
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => $this->snapshotService->build($user, $today));

        $summary = $this->summarizeSnapshots($memberSnapshots);

        foreach ($recipients as $recipient) {
            $notificationKey = 'admin-digest-'.$today->toDateString();

            if ($this->hasNotificationForToday($recipient, $notificationKey, $today)) {
                continue;
            }

            $recipient->notify(new AutomationAlertNotification(
                'Daily ministry operations digest',
                "{$summary['total']} active members are being tracked today: {$summary['catching_up']} catching up, {$summary['in_training']} in training, {$summary['on_track']} on track, and {$summary['reading_ahead']} reading ahead.",
                $notificationKey,
                route('admin.dashboard'),
                'Open command center',
                'emerald',
                'admin_digest',
            ));

            $sent++;
        }

        return $sent;
    }

    private function summarizeSnapshots(Collection $snapshots): array
    {
        return [
            'total' => $snapshots->count(),
            'in_training' => $snapshots->where('status_key', 'in_training')->count(),
            'catching_up' => $snapshots->where('status_key', 'catching_up')->count(),
            'on_track' => $snapshots->where('status_key', 'on_track')->count(),
            'reading_ahead' => $snapshots->where('status_key', 'reading_ahead')->count(),
            'awaiting_start' => $snapshots->where('status_key', 'awaiting_start')->count(),
            'no_active_plan' => $snapshots->where('status_key', 'no_active_plan')->count(),
        ];
    }

    private function sendVacancyAlerts(Carbon $today): int
    {
        if (! $this->settings->vacancyAlertsEnabled()) {
            return 0;
        }

        $vacancies = Hierarchy::query()
            ->with('parent')
            ->whereNull('leader_id')
            ->ordered()
            ->get();

        if ($vacancies->isEmpty()) {
            return 0;
        }

        $sent = 0;
        $vacancyList = $vacancies
            ->take(5)
            ->map(fn (Hierarchy $hierarchy) => $hierarchy->displayPath())
            ->implode(', ');
        $vacancyActions = $vacancies
            ->take(5)
            ->map(fn (Hierarchy $hierarchy) => [
                'hierarchy_id' => $hierarchy->id,
                'path' => $hierarchy->displayPath(),
                'resolve_url' => route('admin.hierarchies.resolve-vacancy', $hierarchy),
                'detail_url' => route('admin.hierarchies.show', $hierarchy),
            ])
            ->values()
            ->all();
        $additionalCount = max($vacancies->count() - 5, 0);
        $body = "{$vacancies->count()} hierarchy slot(s) are currently vacant: {$vacancyList}";

        if ($additionalCount > 0) {
            $body .= " and {$additionalCount} more.";
        }

        $recipients = User::query()
            ->get()
            ->filter(fn (User $user) => $user->canAccessAdminPanel() && $user->hasPermissionTo('hierarchies.manage'));

        foreach ($recipients as $recipient) {
            $notificationKey = 'vacancy-alert-'.$today->toDateString();

            if ($this->hasNotificationForToday($recipient, $notificationKey, $today)) {
                continue;
            }

            $recipient->notify(new AutomationAlertNotification(
                'Leadership vacancies need attention',
                $body,
                $notificationKey,
                $vacancyActions[0]['resolve_url'] ?? route('admin.hierarchies.index'),
                'Resolve first vacancy',
                'amber',
                'vacancy_alert',
                [
                    'vacancies' => $vacancyActions,
                    'additional_count' => $additionalCount,
                ],
            ));

            $sent++;
        }

        return $sent;
    }

    private function hasNotificationForToday(User $user, string $notificationKey, Carbon $today): bool
    {
        return $user->notifications()
            ->where('type', AutomationAlertNotification::class)
            ->where('data->notification_key', $notificationKey)
            ->whereDate('created_at', $today)
            ->exists();
    }
}
