<?php

namespace App\Http\Controllers;

use App\Models\BibleChapter;
use App\Models\Hierarchy;
use App\Models\ReadingProgress;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $newTestamentProgress = [
            'current_day' => ReadingProgress::getNextDay($user->id, 'new'),
            'total_days' => BibleChapter::getTotalDays('new'),
            'completion_rate' => $this->calculateCompletionRate($user->id, 'new'),
            'today_chapters' => $this->getTodayChapters($user->id, 'new'),
        ];

        $oldTestamentProgress = [
            'current_day' => ReadingProgress::getNextDay($user->id, 'old'),
            'total_days' => BibleChapter::getTotalDays('old'),
            'completion_rate' => $this->calculateCompletionRate($user->id, 'old'),
            'today_chapters' => $this->getTodayChapters($user->id, 'old'),
        ];

        $hierarchyData = $this->getHierarchyData($user);

        return view('dashboard-content', compact(
            'newTestamentProgress',
            'oldTestamentProgress',
            'hierarchyData'
        ));
    }

    private function calculateCompletionRate($userId, $testament)
    {
        $completed = ReadingProgress::getCurrentProgress($userId, $testament);
        $total = BibleChapter::getTotalDays($testament);

        return ($completed / $total) * 100;
    }

    private function getTodayChapters($userId, $testament)
    {
        $nextDay = ReadingProgress::getNextDay($userId, $testament);

        return BibleChapter::getDayRange($nextDay, $testament);
    }

    protected function getHierarchyData($user)
    {
        $data = [];

        if ($user->role === 'platoon_leader') {
            $data['squads'] = Hierarchy::where('parent_id', $user->platoon->id)
                ->with(['leader', 'members', 'batches.teams.leader', 'batches.teams.members'])
                ->get();
        } elseif ($user->role === 'squad_leader') {
            $data['batches'] = Hierarchy::where('parent_id', $user->squad->id)
                ->with(['leader', 'members', 'teams.leader', 'teams.members'])
                ->get();
        } elseif ($user->role === 'batch_leader') {
            $data['teams'] = Hierarchy::where('parent_id', $user->batch->id)
                ->with(['leader', 'members'])
                ->get();
        }

        return $data;
    }

    protected function getPlatoonData($user)
    {
        return [
            'squads' => $user->platoon->squads()->with(['leader', 'batches'])->get(),
            'totalMembers' => $user->platoon->getAllMembers()->count(),
            'activeToday' => $user->platoon->getActiveMembersToday()->count(),
        ];
    }

    protected function getSquadData($user)
    {
        return [
            'batches' => $user->squad->batches()->with(['leader', 'teams'])->get(),
            'totalMembers' => $user->squad->getAllMembers()->count(),
            'activeToday' => $user->squad->getActiveMembersToday()->count(),
        ];
    }

    protected function getBatchData($user)
    {
        return [
            'teams' => $user->batch->teams()->with(['leader', 'members'])->get(),
            'totalMembers' => $user->batch->getAllMembers()->count(),
            'activeToday' => $user->batch->getActiveMembersToday()->count(),
        ];
    }

    protected function getTeamData($user)
    {
        return [
            'members' => $user->team->members()->with('readingProgress')->get(),
            'totalMembers' => $user->team->members()->count(),
            'activeToday' => $user->team->getActiveMembersToday()->count(),
        ];
    }

    protected function getMemberData($user)
    {
        return [
            'progress' => $user->readingProgress()->orderBy('day_number')->get(),
            'completedToday' => $user->hasCompletedToday(),
            'totalProgress' => $user->getProgressPercentage(),
        ];
    }

    public function manageHierarchy(Request $request)
    {
        $user = auth()->user();

        abort_unless($user->canManageHierarchy(), 403);

        $leadHierarchy = $user->currentLeadershipHierarchy();

        if (! $leadHierarchy) {
            return view('admin.hierarchy.manage-hierarchy', [
                'leadHierarchy' => null,
                'scopeHierarchies' => collect(),
                'memberSnapshots' => collect(),
                'summary' => [
                    'total_monitored' => 0,
                    'in_training' => 0,
                    'awaiting_start' => 0,
                    'catching_up' => 0,
                    'on_track' => 0,
                    'reading_ahead' => 0,
                    'no_active_plan' => 0,
                ],
            ]);
        }

        $scopeHierarchies = Hierarchy::with(['parent', 'leader', 'children'])
            ->whereIn('id', $leadHierarchy->descendantIdsIncludingSelf())
            ->get();
        $manageableTeams = $leadHierarchy->descendantTeamsIncludingSelf();

        $scopeHierarchyIds = $scopeHierarchies->pluck('id');
        $leaderIds = $scopeHierarchies->pluck('leader_id')->filter();

        $members = User::with([
            'hierarchy.parent',
            'readingPlans.trainingResources',
            'readingPlans.dailyReadings',
            'readingProgress.dailyReading',
            'trainingCompletions',
        ])
            ->where(function ($query) use ($scopeHierarchyIds, $leaderIds) {
                $query->whereIn('hierarchy_id', $scopeHierarchyIds)
                    ->orWhereIn('id', $leaderIds);
            })
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get();

        $allMemberSnapshots = $members->map(fn (User $member) => $this->buildLeaderSnapshot($member));

        $filters = [
            'status' => $request->string('status')->toString(),
            'hierarchy_id' => $request->integer('hierarchy_id'),
            'search' => trim($request->string('search')->toString()),
        ];

        $memberSnapshots = $allMemberSnapshots
            ->when($filters['status'] !== '', function ($collection) use ($filters) {
                return $collection->where('status_key', $filters['status']);
            })
            ->when($filters['hierarchy_id'] > 0, function ($collection) use ($filters) {
                return $collection->where('hierarchy.id', $filters['hierarchy_id']);
            })
            ->when($filters['search'] !== '', function ($collection) use ($filters) {
                $search = mb_strtolower($filters['search']);

                return $collection->filter(function ($snapshot) use ($search) {
                    return str_contains(mb_strtolower($snapshot['user']->name), $search)
                        || str_contains(mb_strtolower($snapshot['user']->email), $search);
                });
            })
            ->sortBy([
                ['status_key', 'asc'],
                [fn ($snapshot) => $snapshot['user']->name, 'asc'],
            ])
            ->values();

        $summary = [
            'total_monitored' => $memberSnapshots->count(),
            'in_training' => $memberSnapshots->where('status_key', 'in_training')->count(),
            'awaiting_start' => $memberSnapshots->where('status_key', 'awaiting_start')->count(),
            'catching_up' => $memberSnapshots->where('status_key', 'catching_up')->count(),
            'on_track' => $memberSnapshots->where('status_key', 'on_track')->count(),
            'reading_ahead' => $memberSnapshots->where('status_key', 'reading_ahead')->count(),
            'no_active_plan' => $memberSnapshots->where('status_key', 'no_active_plan')->count(),
        ];

        $statusOptions = [
            'in_training' => 'In Training',
            'awaiting_start' => 'Awaiting Start',
            'catching_up' => 'Catching Up',
            'on_track' => 'On Track',
            'reading_ahead' => 'Reading Ahead',
            'no_active_plan' => 'No Active Plan',
        ];

        return view('admin.hierarchy.manage-hierarchy', compact(
            'leadHierarchy',
            'scopeHierarchies',
            'manageableTeams',
            'memberSnapshots',
            'summary',
            'filters',
            'statusOptions',
            'allMemberSnapshots',
        ));
    }

    public function updateManagedMember(Request $request, User $member)
    {
        $user = auth()->user();

        abort_unless($user->canManageHierarchy(), 403);

        $leadHierarchy = $user->currentLeadershipHierarchy();

        abort_unless($leadHierarchy, 403);

        $scopeHierarchyIds = $leadHierarchy->descendantIdsIncludingSelf();
        $manageableTeams = $leadHierarchy->descendantTeamsIncludingSelf();
        $manageableTeamIds = $manageableTeams->pluck('id')->all();

        abort_unless($member->role === User::ROLE_MEMBER, 403);
        abort_unless($member->hierarchy_id === null || $scopeHierarchyIds->contains($member->hierarchy_id), 403);

        $validated = $request->validate([
            'hierarchy_id' => ['required', Rule::in($manageableTeamIds)],
        ]);

        $member->update([
            'hierarchy_id' => (int) $validated['hierarchy_id'],
        ]);

        return redirect()->route('hierarchy.manage')
            ->with('success', "{$member->name} was reassigned successfully.");
    }

    protected function buildLeaderSnapshot(User $member): array
    {
        $today = Carbon::today();
        $activePlan = $member->activeReadingPlanFromLoaded();

        if (! $activePlan) {
            return [
                'user' => $member,
                'hierarchy' => $member->hierarchy,
                'active_plan' => null,
                'status_key' => 'no_active_plan',
                'status_label' => 'No Active Plan',
                'status_tone' => 'slate',
                'training_progress' => '0 / 0',
                'expected_day' => null,
                'completed_days' => 0,
                'behind_days' => 0,
                'ahead_days' => 0,
                'last_completion_date' => null,
            ];
        }

        $trainingResources = $activePlan->relationLoaded('trainingResources')
            ? $activePlan->trainingResources
            : $activePlan->trainingResources()->get();
        $trainingResourceIds = $trainingResources->pluck('id');
        $trainingCompletedCount = $member->trainingCompletions
            ->whereIn('training_resource_id', $trainingResourceIds)
            ->count();
        $trainingTotal = $trainingResources->count();
        $trainingComplete = $trainingTotal === 0 || $trainingCompletedCount >= $trainingTotal;

        $dailyReadings = $activePlan->relationLoaded('dailyReadings')
            ? $activePlan->dailyReadings
            : $activePlan->dailyReadings()->get();
        $readingDays = $dailyReadings->where('is_break_day', false);
        $maxReadingDay = max($readingDays->max('day_number') ?? 1, 1);
        $expectedDay = min($activePlan->expectedCurrentDay($today), $maxReadingDay);

        $completedDayNumbers = $member->readingProgress
            ->filter(fn ($progress) => $progress->reading_plan_id === $activePlan->id && $progress->dailyReading)
            ->map(fn ($progress) => $progress->dailyReading->day_number)
            ->unique()
            ->sort()
            ->values();

        $completedDays = $completedDayNumbers->count();
        $completedThroughExpected = $completedDayNumbers->filter(fn ($dayNumber) => $dayNumber <= $expectedDay)->count();
        $expectedReadingDays = $readingDays->where('day_number', '<=', $expectedDay)->count();
        $behindDays = max($expectedReadingDays - $completedThroughExpected, 0);
        $aheadDays = $completedDayNumbers->filter(fn ($dayNumber) => $dayNumber > $expectedDay)->count();
        $readingUnlocked = $activePlan->canRecordReadings($member, $today);
        $lastCompletion = $member->readingProgress->sortByDesc('completed_date')->first()?->completed_date;

        [$statusKey, $statusLabel, $statusTone] = match (true) {
            ! $trainingComplete => ['in_training', 'In Training', 'amber'],
            ! $readingUnlocked => ['awaiting_start', 'Awaiting Reading Start', 'sky'],
            $aheadDays > 0 => ['reading_ahead', 'Reading Ahead', 'indigo'],
            $behindDays > 0 => ['catching_up', 'Catching Up', 'rose'],
            default => ['on_track', 'On Track', 'green'],
        };

        return [
            'user' => $member,
            'hierarchy' => $member->hierarchy,
            'active_plan' => $activePlan,
            'status_key' => $statusKey,
            'status_label' => $statusLabel,
            'status_tone' => $statusTone,
            'training_progress' => "{$trainingCompletedCount} / {$trainingTotal}",
            'expected_day' => $expectedDay,
            'completed_days' => $completedDays,
            'behind_days' => $behindDays,
            'ahead_days' => $aheadDays,
            'last_completion_date' => $lastCompletion,
        ];
    }
}
