<?php

namespace App\Services\Manual;

use App\Models\User;
use Illuminate\Support\Collection;

class UserManualService
{
    public function guidesFor(User $user): Collection
    {
        return collect($this->allGuides())
            ->filter(fn (array $guide) => $this->userCanSeeGuide($user, $guide))
            ->map(fn (array $guide) => $this->withResolvedLinks($guide))
            ->values();
    }

    public function findGuideFor(User $user, string $slug): ?array
    {
        return $this->guidesFor($user)
            ->first(fn (array $guide) => $guide['slug'] === $slug);
    }

    private function userCanSeeGuide(User $user, array $guide): bool
    {
        $audiences = $guide['audiences'] ?? ['all'];

        if (! $this->matchesAudience($user, $audiences)) {
            return false;
        }

        foreach ($guide['permissions'] ?? [] as $permission) {
            if (! $user->hasPermissionTo($permission)) {
                return false;
            }
        }

        return true;
    }

    private function matchesAudience(User $user, array $audiences): bool
    {
        if (in_array('all', $audiences, true)) {
            return true;
        }

        if (in_array('admin', $audiences, true) && $user->canAccessAdminPanel()) {
            return true;
        }

        if (in_array('leader', $audiences, true) && $user->isLeader()) {
            return true;
        }

        if (in_array('member', $audiences, true) && ! $user->isLeader() && ! $user->canAccessAdminPanel()) {
            return true;
        }

        return false;
    }

    private function withResolvedLinks(array $guide): array
    {
        $guide['links'] = collect($guide['links'] ?? [])
            ->map(function (array $link) {
                if (isset($link['url'])) {
                    return $link;
                }

                return [
                    'label' => $link['label'],
                    'url' => route($link['route'], $link['parameters'] ?? []),
                ];
            })
            ->values()
            ->all();

        return $guide;
    }

    private function allGuides(): array
    {
        return [
            [
                'slug' => 'join-a-reading-plan',
                'category' => 'Getting Started',
                'title' => 'Join a reading plan',
                'summary' => 'Find an open cohort, review the plan, and join it.',
                'steps' => [
                    'Open Reading Plans from the main menu.',
                    'Choose a plan that is currently open for joining.',
                    'Review the description, schedule, and training requirements.',
                    'Use the join action to start your participation.',
                ],
                'links' => [
                    ['label' => 'Open Reading Plans', 'route' => 'reading-plans.index'],
                ],
                'audiences' => ['all'],
            ],
            [
                'slug' => 'complete-training-and-start-reading',
                'category' => 'Getting Started',
                'title' => 'Complete training and start reading',
                'summary' => 'Finish required training resources before the reading schedule opens.',
                'steps' => [
                    'Open your active plan from Reading Plans.',
                    'Work through each required training resource.',
                    'Return to the dashboard or progress view to check whether reading has unlocked.',
                    'Begin recording daily reading once the plan is ready.',
                ],
                'links' => [
                    ['label' => 'Open Dashboard', 'route' => 'dashboard'],
                    ['label' => 'Open Reading Plans', 'route' => 'reading-plans.index'],
                ],
                'audiences' => ['all'],
            ],
            [
                'slug' => 'record-todays-reading',
                'category' => 'Daily Reading',
                'title' => 'Record today\'s reading',
                'summary' => 'Mark your current assigned reading complete and keep your progress up to date.',
                'steps' => [
                    'Open Progress from the main menu.',
                    'Review the expected reading day and assigned passage.',
                    'Use the completion action to record the reading.',
                    'Confirm that your completion count and pace status update.',
                ],
                'links' => [
                    ['label' => 'Open Progress', 'route' => 'reading.progress'],
                    ['label' => 'Open Reading History', 'route' => 'reading-history'],
                ],
                'audiences' => ['all'],
            ],
            [
                'slug' => 'review-your-history',
                'category' => 'Daily Reading',
                'title' => 'Review your history and progress',
                'summary' => 'See how much you have completed and look back through previous reading activity.',
                'steps' => [
                    'Open Reading History to review recent completions.',
                    'Use Progress to compare completed days with the current schedule.',
                    'Open plan progress when you want more detail on a specific cohort.',
                ],
                'links' => [
                    ['label' => 'Open Reading History', 'route' => 'reading-history'],
                    ['label' => 'Open Progress', 'route' => 'reading.progress'],
                ],
                'audiences' => ['all'],
            ],
            [
                'slug' => 'use-messages-and-alerts',
                'category' => 'Communication',
                'title' => 'Use messages and alerts',
                'summary' => 'Check reminders, manage alerts, and keep message threads organised.',
                'steps' => [
                    'Open Alerts to review reminders and actionable notifications.',
                    'Open Profile when you want to change how reminders and digests reach you.',
                    'Open Message Centre to read inbox items, archive finished threads, or restore something from trash.',
                    'Mark alerts as read after you finish the related task.',
                ],
                'links' => [
                    ['label' => 'Open Alerts', 'route' => 'notifications.index'],
                    ['label' => 'Open Profile', 'route' => 'profile.edit'],
                    ['label' => 'Open Message Centre', 'route' => 'messages.inbox'],
                ],
                'audiences' => ['all'],
            ],
            [
                'slug' => 'monitor-your-branch',
                'category' => 'Leadership',
                'title' => 'Monitor your branch',
                'summary' => 'Filter your branch by pace, search for people, and see who needs follow-up.',
                'steps' => [
                    'Open Manage Team from the main menu.',
                    'Use the search, status, and group filters to narrow the list.',
                    'Review pace badges, training progress, and last completion dates.',
                    'Reassign a member to another team in your allowed scope when needed.',
                ],
                'links' => [
                    ['label' => 'Open Manage Team', 'route' => 'hierarchy.manage'],
                    ['label' => 'Open My Branch', 'route' => 'leader.hierarchies.tree'],
                ],
                'audiences' => ['leader', 'admin'],
            ],
            [
                'slug' => 'open-member-records',
                'category' => 'Leadership',
                'title' => 'Open member records and cycle history',
                'summary' => 'View a person\'s detailed record and inspect individual participation cycles.',
                'steps' => [
                    'Open Manage Team and find the person you want to review.',
                    'Use the View record action on their row.',
                    'Open any participation cycle to inspect that cycle\'s readings and completion history.',
                    'Use the scoped report link when you need the reporting view for the same person.',
                ],
                'links' => [
                    ['label' => 'Open Manage Team', 'route' => 'hierarchy.manage'],
                ],
                'audiences' => ['leader', 'admin'],
            ],
            [
                'slug' => 'work-with-scoped-reports',
                'category' => 'Leadership',
                'title' => 'Work with scoped reports',
                'summary' => 'Use reports to focus on pace, training, plans, groups, or individual people inside your allowed scope.',
                'steps' => [
                    'Open Progress Reports from the admin console.',
                    'Use the filters to narrow by plan, group, pace, training, or date range.',
                    'Open a user detail or plan detail page when you need deeper context.',
                    'Export the current view when you need to share or archive the results.',
                ],
                'links' => [
                    ['label' => 'Open Progress Reports', 'route' => 'admin.progress.index'],
                ],
                'audiences' => ['leader', 'admin'],
                'permissions' => ['progress.view'],
            ],
            [
                'slug' => 'manage-hierarchy-and-vacancies',
                'category' => 'Administration',
                'title' => 'Manage hierarchy and resolve vacancies',
                'summary' => 'Create groups, review vacancies, and assign or promote leaders into empty slots.',
                'steps' => [
                    'Open Hierarchies from the admin console.',
                    'Review the vacancy area or open the hierarchy that needs attention.',
                    'Use Resolve now to assign an existing leader or promote someone already in the group.',
                    'Save the change and confirm the hierarchy now shows an active leader.',
                ],
                'links' => [
                    ['label' => 'Open Hierarchies', 'route' => 'admin.hierarchies.index'],
                    ['label' => 'Open Hierarchy Tree', 'route' => 'admin.hierarchies.tree'],
                ],
                'audiences' => ['admin'],
                'permissions' => ['hierarchies.manage'],
            ],
            [
                'slug' => 'move-or-merge-groups',
                'category' => 'Administration',
                'title' => 'Move or merge groups',
                'summary' => 'Use guided workflows for same-level migrations and sibling merges.',
                'steps' => [
                    'Open Hierarchies from the admin console.',
                    'Use the migration workflow when a branch needs to move under a different same-level parent.',
                    'Use the merge workflow when two sibling groups need to become one.',
                    'Review the preview screen before confirming either workflow.',
                ],
                'links' => [
                    ['label' => 'Open Hierarchies', 'route' => 'admin.hierarchies.index'],
                ],
                'audiences' => ['admin'],
                'permissions' => ['hierarchies.manage'],
            ],
            [
                'slug' => 'rebalance-teams',
                'category' => 'Administration',
                'title' => 'Rebalance teams',
                'summary' => 'Launch balancing directly from hierarchy insights and distribute selected members across sibling teams.',
                'steps' => [
                    'Open Hierarchies and find a balancing insight.',
                    'Use Launch rebalance action on the batch that needs attention.',
                    'Select the members you want to move from the suggested source team.',
                    'Run the distribute action to spread them across the preselected teams.',
                ],
                'links' => [
                    ['label' => 'Open Hierarchies', 'route' => 'admin.hierarchies.index'],
                    ['label' => 'Open User Directory', 'route' => 'admin.users.index'],
                ],
                'audiences' => ['admin'],
                'permissions' => ['users.manage'],
            ],
            [
                'slug' => 'manage-users-from-the-directory',
                'category' => 'Administration',
                'title' => 'Manage users from the directory',
                'summary' => 'Use filters, bulk actions, promotions, and safe demotions from the main people directory.',
                'steps' => [
                    'Open Users from the admin console.',
                    'Filter the directory to find the right people or group.',
                    'Select one or more rows and choose a bulk action.',
                    'Use promote or demote directory actions when a leadership change should happen from the user list.',
                ],
                'links' => [
                    ['label' => 'Open User Directory', 'route' => 'admin.users.index'],
                ],
                'audiences' => ['admin'],
                'permissions' => ['users.manage'],
            ],
            [
                'slug' => 'run-automation-and-check-alerts',
                'category' => 'Administration',
                'title' => 'Run automation and check alerts',
                'summary' => 'Review automation settings, run the cycle manually, and confirm the resulting alerts.',
                'steps' => [
                    'Open Automation from the admin console.',
                    'Review the current settings and latest run information.',
                    'Run automation manually when you need an immediate refresh.',
                    'Open Alerts to review any reminders, digests, or vacancy follow-up items.',
                ],
                'links' => [
                    ['label' => 'Open Automation', 'route' => 'admin.automation.index'],
                    ['label' => 'Open Alerts', 'route' => 'notifications.index'],
                ],
                'audiences' => ['admin'],
                'permissions' => ['automation.manage'],
            ],
        ];
    }
}
