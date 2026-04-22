<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DailyReading;
use App\Models\ReadingPlan;
use App\Models\ReadingProgress;
use App\Models\ReportPreset;
use App\Models\User;
use App\Services\Auditing\AuditLogger;
use App\Services\Messaging\UserProgressSnapshotService;
use App\Services\Reports\ProgressReportScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserProgressController extends Controller
{
    public function __construct(
        private readonly ProgressReportScope $reportScope,
        private readonly UserProgressSnapshotService $snapshotService,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function index(Request $request)
    {
        $actor = $request->user();
        $filters = $this->filtersFromRequest($request);
        $scopeData = $this->scopeData($actor, $filters);
        $progressQuery = $this->progressQuery($filters, $scopeData);

        $progress = $progressQuery
            ->orderByDesc('reading_progress.completed_date')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.progress.index', [
            'users' => $scopeData['users'],
            'readingPlans' => $scopeData['plans'],
            'hierarchies' => $scopeData['hierarchies'],
            'hierarchyDisplayPaths' => $scopeData['hierarchy_display_paths'],
            'progress' => $progress,
            'stats' => $this->buildStats($filters, $scopeData),
            'filters' => $filters,
            'scopeLabel' => $this->reportScope->scopeLabel($actor),
            'isScopedToHierarchy' => ! $this->reportScope->isGlobal($actor),
            'roleOptions' => User::roleOptions(),
            'paceStatusOptions' => $this->paceStatusOptions(),
            'trainingStatusOptions' => $this->trainingStatusOptions(),
            'reportPresets' => $actor->reportPresets()->latest()->get(),
            'reportTypeOptions' => $this->reportTypeOptions(),
            'allowedPerPage' => [15, 25, 50, 100],
        ]);
    }

    public function userDetail(Request $request, User $user)
    {
        $actor = $request->user();

        abort_unless($this->reportScope->canAccessUser($actor, $user), 403);

        $user->loadMissing('readingPlans');
        $userPlans = $user->readingPlans;
        $totalCompletions = $user->readingProgress()->count();
        $currentStreak = $user->readingPlans()->wherePivot('is_active', true)->first()?->pivot->current_streak ?? 0;
        $planIds = $userPlans->pluck('id');
        $totalDaysByPlan = DailyReading::query()
            ->select('reading_plan_id', DB::raw('count(*) as total_days'))
            ->whereIn('reading_plan_id', $planIds)
            ->where('is_break_day', false)
            ->groupBy('reading_plan_id')
            ->pluck('total_days', 'reading_plan_id');
        $completedDaysByPlan = $user->readingProgress()
            ->select('reading_plan_id', DB::raw('count(*) as completed_days'))
            ->whereIn('reading_plan_id', $planIds)
            ->groupBy('reading_plan_id')
            ->pluck('completed_days', 'reading_plan_id');

        $planStats = [];
        foreach ($userPlans as $plan) {
            $totalDays = (int) ($totalDaysByPlan[$plan->id] ?? 0);
            $completedDays = (int) ($completedDaysByPlan[$plan->id] ?? 0);
            $completionRate = $totalDays > 0 ? ($completedDays / $totalDays) * 100 : 0;

            $planStats[] = [
                'plan' => $plan,
                'total_days' => $totalDays,
                'completed_days' => $completedDays,
                'completion_rate' => $completionRate,
                'is_active' => $plan->pivot->is_active,
                'current_day' => $plan->pivot->current_day,
            ];
        }

        $recentActivity = $user->readingProgress()
            ->with(['readingPlan', 'dailyReading'])
            ->orderByDesc('completed_date')
            ->limit(10)
            ->get();

        $completionTrend = ReadingProgress::query()
            ->select(DB::raw('DATE(completed_date) as date'), DB::raw('count(*) as count'))
            ->where('user_id', $user->id)
            ->where('completed_date', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.progress.user-detail', [
            'user' => $user,
            'userPlans' => $userPlans,
            'totalCompletions' => $totalCompletions,
            'currentStreak' => $currentStreak,
            'planStats' => $planStats,
            'recentActivity' => $recentActivity,
            'chartLabels' => $completionTrend->pluck('date')->toJson(),
            'chartData' => $completionTrend->pluck('count')->toJson(),
            'scopeLabel' => $this->reportScope->scopeLabel($actor),
            'isScopedToHierarchy' => ! $this->reportScope->isGlobal($actor),
        ]);
    }

    public function planDetail(Request $request, ReadingPlan $readingPlan)
    {
        $actor = $request->user();

        abort_unless($this->reportScope->canAccessPlan($actor, $readingPlan), 403);

        $scopedUserIds = $this->reportScope->accessibleUserIds($actor) ?? collect();
        $limitToScopedUsers = ! $this->reportScope->isGlobal($actor);
        $planUsers = $readingPlan->users()
            ->with(['hierarchy.parent'])
            ->when($limitToScopedUsers, fn (Builder $query) => $query->whereIn('users.id', $scopedUserIds))
            ->get();
        $totalDays = (int) DailyReading::query()
            ->where('reading_plan_id', $readingPlan->id)
            ->where('is_break_day', false)
            ->count();
        $completedDaysByUser = ReadingProgress::query()
            ->select('user_id', DB::raw('count(*) as completed_days'))
            ->where('reading_plan_id', $readingPlan->id)
            ->when($limitToScopedUsers, fn (Builder $query) => $query->whereIn('user_id', $scopedUserIds))
            ->groupBy('user_id')
            ->pluck('completed_days', 'user_id');

        $totalCompletions = ReadingProgress::query()
            ->where('reading_plan_id', $readingPlan->id)
            ->when($limitToScopedUsers, fn (Builder $query) => $query->whereIn('user_id', $scopedUserIds))
            ->count();
        $totalUsers = $planUsers->count();
        $activeUsers = $planUsers->where('pivot.is_active', true)->count();

        $userStats = [];
        foreach ($planUsers as $user) {
            $completedDays = (int) ($completedDaysByUser[$user->id] ?? 0);
            $completionRate = $totalDays > 0 ? ($completedDays / $totalDays) * 100 : 0;

            $userStats[] = [
                'user' => $user,
                'total_days' => $totalDays,
                'completed_days' => $completedDays,
                'completion_rate' => $completionRate,
                'is_active' => $user->pivot->is_active,
                'current_day' => $user->pivot->current_day,
                'current_streak' => $user->pivot->current_streak,
            ];
        }

        usort($userStats, fn ($a, $b) => $b['completion_rate'] <=> $a['completion_rate']);

        $recentActivity = ReadingProgress::query()
            ->with(['user', 'dailyReading'])
            ->where('reading_plan_id', $readingPlan->id)
            ->when($limitToScopedUsers, fn (Builder $query) => $query->whereIn('user_id', $scopedUserIds))
            ->orderByDesc('completed_date')
            ->limit(10)
            ->get();

        $completionTrend = ReadingProgress::query()
            ->select(DB::raw('DATE(completed_date) as date'), DB::raw('count(*) as count'))
            ->where('reading_plan_id', $readingPlan->id)
            ->when($limitToScopedUsers, fn (Builder $query) => $query->whereIn('user_id', $scopedUserIds))
            ->where('completed_date', '>=', Carbon::now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.progress.plan-detail', [
            'readingPlan' => $readingPlan,
            'planUsers' => $planUsers,
            'totalCompletions' => $totalCompletions,
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'userStats' => $userStats,
            'recentActivity' => $recentActivity,
            'chartLabels' => $completionTrend->pluck('date')->toJson(),
            'chartData' => $completionTrend->pluck('count')->toJson(),
            'scopeLabel' => $this->reportScope->scopeLabel($actor),
            'isScopedToHierarchy' => ! $this->reportScope->isGlobal($actor),
        ]);
    }

    public function export(Request $request)
    {
        $actor = $request->user();
        $filters = $this->filtersFromRequest($request);
        $scopeData = $this->scopeData($actor, $filters);
        $reportType = $request->string('report_type')->toString() ?: 'detail';
        $progress = $this->progressQuery($filters, $scopeData)
            ->orderByDesc('reading_progress.completed_date')
            ->get();

        $format = $request->string('format')->toString() ?: 'csv';
        $filenameBase = 'reading_progress_'.Carbon::now()->format('Y-m-d_His');
        $snapshotMap = $this->snapshotMapForUsers($progress->pluck('user')->filter()->unique('id')->values());
        $rows = $reportType === 'hierarchy_summary'
            ? $this->hierarchySummaryRows($filters, $scopeData, $snapshotMap)
            : $this->exportRows($progress, $snapshotMap);

        $this->auditLogger->log(
            'reports.exported',
            $actor,
            null,
            [
                'filters' => array_filter($filters, fn ($value) => $value !== '' && $value !== 0),
                'format' => $format,
                'report_type' => $reportType,
                'row_count' => count($rows),
                'scope_label' => $scopeData['scope_label'],
            ],
            "Exported a {$format} {$reportType} report covering {$scopeData['scope_label']}.",
        );

        return match ($format) {
            'excel' => $this->excelResponse($rows, "{$filenameBase}.xls"),
            'pdf' => $this->pdfResponse($rows, $filters, $scopeData, $reportType, "{$filenameBase}.pdf"),
            default => $this->csvResponse($rows, "{$filenameBase}.csv"),
        };
    }

    public function storePreset(Request $request)
    {
        $actor = $request->user();
        $filters = $this->filtersFromRequest($request);
        unset($filters['per_page']);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $actor->reportPresets()->create([
            'name' => $validated['name'],
            'filters' => $filters,
        ]);

        return redirect()->route('admin.progress.index', $filters)
            ->with('success', 'Report preset saved successfully.');
    }

    public function destroyPreset(Request $request, ReportPreset $reportPreset)
    {
        abort_unless($reportPreset->user_id === $request->user()->id, 403);

        $reportPreset->delete();

        return back()->with('success', 'Report preset deleted successfully.');
    }

    private function filtersFromRequest(Request $request): array
    {
        $perPage = (int) $request->integer('per_page', 15);
        $allowedPerPage = [15, 25, 50, 100];

        return [
            'user_id' => $request->integer('user_id'),
            'plan_id' => $request->integer('plan_id'),
            'hierarchy_id' => $request->integer('hierarchy_id'),
            'role' => $request->string('role')->toString(),
            'pace_status' => $request->string('pace_status')->toString(),
            'training_status' => $request->string('training_status')->toString(),
            'date_range' => $request->string('date_range')->toString() ?: 'all',
            'start_date' => $request->string('start_date')->toString(),
            'end_date' => $request->string('end_date')->toString(),
            'per_page' => in_array($perPage, $allowedPerPage, true) ? $perPage : 15,
        ];
    }

    private function scopeData(User $actor, array $filters): array
    {
        $users = $this->reportScope->accessibleUsers($actor);
        $plans = $this->reportScope->accessiblePlans($actor);
        $hierarchies = $this->reportScope->accessibleHierarchies($actor);
        $derivedUserIds = $this->reportScope->accessibleUserIdsMatchingDerivedFilters($actor, $filters);

        return [
            'users' => $users,
            'user_ids' => $users->pluck('id')->all(),
            'plans' => $plans,
            'hierarchies' => $hierarchies,
            'hierarchy_display_paths' => \App\Models\Hierarchy::buildDisplayPaths($hierarchies),
            'derived_user_ids' => $derivedUserIds,
            'scope_label' => $this->reportScope->scopeLabel($actor),
        ];
    }

    private function progressQuery(array $filters, array $scopeData): Builder
    {
        $query = ReadingProgress::query()
            ->with(['user.hierarchy.parent', 'readingPlan', 'dailyReading'])
            ->select('reading_progress.*')
            ->join('users', 'users.id', '=', 'reading_progress.user_id')
            ->join('reading_plans', 'reading_plans.id', '=', 'reading_progress.reading_plan_id');

        if ($scopeData['user_ids'] === []) {
            return $query->whereRaw('1 = 0');
        }

        $query->whereIn('reading_progress.user_id', $scopeData['user_ids']);

        if (is_array($scopeData['derived_user_ids'])) {
            if ($scopeData['derived_user_ids'] === []) {
                return $query->whereRaw('1 = 0');
            }

            $query->whereIn('reading_progress.user_id', $scopeData['derived_user_ids']);
        }

        if ($filters['user_id'] > 0) {
            $query->where('reading_progress.user_id', $filters['user_id']);
        }

        if ($filters['plan_id'] > 0) {
            $query->where('reading_progress.reading_plan_id', $filters['plan_id']);
        }

        if ($filters['hierarchy_id'] > 0) {
            $query->where('users.hierarchy_id', $filters['hierarchy_id']);
        }

        if ($filters['role'] !== '') {
            $query->where('users.role', $filters['role']);
        }

        [$startDate, $endDate] = $this->resolveDateRange($filters);

        if ($startDate) {
            $query->whereBetween('reading_progress.completed_date', [$startDate, $endDate]);
        }

        return $query;
    }

    private function buildStats(array $filters, array $scopeData): array
    {
        $baseQuery = $this->progressQuery($filters, $scopeData);
        $totalCompletions = (clone $baseQuery)->count();

        $completionsByUser = (clone $baseQuery)
            ->select('users.id', 'users.name', DB::raw('count(*) as count'))
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $completionsByPlan = (clone $baseQuery)
            ->select('reading_plans.id', 'reading_plans.name', DB::raw('count(*) as count'))
            ->groupBy('reading_plans.id', 'reading_plans.name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        $completionsByDay = (clone $baseQuery)
            ->select(DB::raw('DATE(reading_progress.completed_date) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->when($filters['date_range'] === 'all', fn (Builder $query) => $query->where('reading_progress.completed_date', '>=', Carbon::now()->subDays(30)))
            ->get();

        $activeUsers = User::query()
            ->when($scopeData['user_ids'] === [], fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->whereIn('id', $scopeData['user_ids'])
            ->when(is_array($scopeData['derived_user_ids']), fn (Builder $query) => $query->whereIn('id', $scopeData['derived_user_ids']))
            ->when($filters['hierarchy_id'] > 0, fn (Builder $query) => $query->where('hierarchy_id', $filters['hierarchy_id']))
            ->when($filters['role'] !== '', fn (Builder $query) => $query->where('role', $filters['role']))
            ->whereHas('readingPlans', fn (Builder $query) => $query->where('user_reading_plans.is_active', true))
            ->count();

        $activePlans = ReadingPlan::query()
            ->whereHas('readingProgress', function (Builder $query) use ($scopeData, $filters) {
                $query->whereIn('user_id', $scopeData['user_ids']);

                if (is_array($scopeData['derived_user_ids'])) {
                    $query->whereIn('user_id', $scopeData['derived_user_ids']);
                }

                if ($filters['user_id'] > 0) {
                    $query->where('user_id', $filters['user_id']);
                }
            })
            ->count();

        return [
            'total_completions' => $totalCompletions,
            'completions_by_user' => $completionsByUser,
            'completions_by_plan' => $completionsByPlan,
            'chart_labels' => $completionsByDay->pluck('date')->toJson(),
            'chart_data' => $completionsByDay->pluck('count')->toJson(),
            'active_users' => $activeUsers,
            'active_plans' => $activePlans,
        ];
    }

    private function resolveDateRange(array $filters): array
    {
        $startDate = null;
        $endDate = Carbon::today();

        switch ($filters['date_range']) {
            case 'today':
                $startDate = Carbon::today();
                break;
            case 'yesterday':
                $startDate = Carbon::yesterday();
                $endDate = Carbon::yesterday();
                break;
            case 'this_week':
                $startDate = Carbon::now()->startOfWeek();
                break;
            case 'last_week':
                $startDate = Carbon::now()->subWeek()->startOfWeek();
                $endDate = Carbon::now()->subWeek()->endOfWeek();
                break;
            case 'this_month':
                $startDate = Carbon::now()->startOfMonth();
                break;
            case 'last_month':
                $startDate = Carbon::now()->subMonth()->startOfMonth();
                $endDate = Carbon::now()->subMonth()->endOfMonth();
                break;
            case 'custom':
                $startDate = $filters['start_date'] !== '' ? Carbon::parse($filters['start_date']) : null;
                $endDate = $filters['end_date'] !== '' ? Carbon::parse($filters['end_date']) : Carbon::today();
                break;
        }

        return [$startDate, $endDate];
    }

    private function snapshotMapForUsers(Collection $users): Collection
    {
        return $users
            ->each(function (User $user) {
                $user->loadMissing([
                    'hierarchy.parent',
                    'readingPlans.trainingResources',
                    'readingPlans.dailyReadings',
                    'readingProgress.dailyReading',
                    'trainingCompletions',
                ]);
            })
            ->mapWithKeys(fn (User $user) => [$user->id => $this->snapshotService->build($user)]);
    }

    private function exportRows(Collection $progress, Collection $snapshotMap): array
    {
        return $progress->map(function (ReadingProgress $record) use ($snapshotMap) {
            $snapshot = $snapshotMap->get($record->user_id, []);

            return [
                'User' => $record->user?->name ?? 'Unknown',
                'Email' => $record->user?->email ?? '',
                'Role' => $record->user?->roleLabel() ?? '',
                'Group' => $record->user?->hierarchy?->displayPath() ?? 'Unassigned',
                'Reading Plan' => $record->readingPlan?->name ?? '',
                'Plan Type' => $record->readingPlan?->type_label ?? '',
                'Reading' => $record->dailyReading?->reading_range ?? '',
                'Completed Date' => optional($record->completed_date)->format('Y-m-d') ?? '',
                'Pace Status' => $snapshot['status_label'] ?? '',
                'Training Status' => $snapshot['training_status'] ?? '',
                'Training Progress' => $snapshot['training_progress'] ?? '',
                'Expected Day' => $snapshot['expected_day'] ?? '',
                'Completed Days' => $snapshot['completed_days'] ?? '',
                'Ahead Days' => $snapshot['ahead_days'] ?? '',
                'Behind Days' => $snapshot['behind_days'] ?? '',
                'Last Completion Date' => isset($snapshot['last_completion_date']) && $snapshot['last_completion_date']
                    ? Carbon::parse($snapshot['last_completion_date'])->format('Y-m-d')
                    : '',
            ];
        })->all();
    }

    private function hierarchySummaryRows(array $filters, array $scopeData, Collection $snapshotMap): array
    {
        $users = $this->filteredUsersForSummary($filters, $scopeData);
        $usersByHierarchy = $users->groupBy(fn (User $user) => $user->hierarchy_id ?: 'unassigned');
        $completionCounts = $this->progressQuery($filters, $scopeData)
            ->select('users.hierarchy_id', DB::raw('count(*) as total'))
            ->groupBy('users.hierarchy_id')
            ->pluck('total', 'users.hierarchy_id');

        return $usersByHierarchy->map(function (Collection $members, $hierarchyKey) use ($completionCounts, $snapshotMap) {
            $hierarchy = $members->first()?->hierarchy;
            $snapshots = $members->map(fn (User $user) => $snapshotMap->get($user->id));
            $activePlanCount = $members
                ->map(fn (User $user) => $user->activeReadingPlanFromLoaded()?->id)
                ->filter()
                ->unique()
                ->count();
            $memberCount = $members->where('role', User::ROLE_MEMBER)->count();
            $leaderCount = $members->count() - $memberCount;
            $completionTotal = (int) ($completionCounts[$hierarchy?->id] ?? ($hierarchyKey === 'unassigned' ? ($completionCounts[null] ?? 0) : 0));

            return [
                'Hierarchy' => $hierarchy?->displayPath() ?? 'Unassigned',
                'Hierarchy Type' => $hierarchy ? ucfirst($hierarchy->type) : 'None',
                'Leader' => $hierarchy?->leader?->name ?? 'No assigned leader',
                'People' => $members->count(),
                'Members' => $memberCount,
                'Leaders' => $leaderCount,
                'Active Plans' => $activePlanCount,
                'Total Completions' => $completionTotal,
                'Avg Completions Per Person' => $members->isNotEmpty() ? number_format($completionTotal / $members->count(), 1) : '0.0',
                'In Training' => $snapshots->where('status_key', 'in_training')->count(),
                'Awaiting Start' => $snapshots->where('status_key', 'awaiting_start')->count(),
                'Catching Up' => $snapshots->where('status_key', 'catching_up')->count(),
                'On Track' => $snapshots->where('status_key', 'on_track')->count(),
                'Reading Ahead' => $snapshots->where('status_key', 'reading_ahead')->count(),
                'No Active Plan' => $snapshots->where('status_key', 'no_active_plan')->count(),
            ];
        })->values()->all();
    }

    private function csvResponse(array $rows, string $filename)
    {
        return response()->streamDownload(function () use ($rows) {
            $file = fopen('php://output', 'w');

            fputcsv($file, array_keys($rows[0] ?? [
                'User' => '',
                'Email' => '',
                'Role' => '',
                'Group' => '',
                'Reading Plan' => '',
                'Plan Type' => '',
                'Reading' => '',
                'Completed Date' => '',
                'Pace Status' => '',
                'Training Status' => '',
                'Training Progress' => '',
                'Expected Day' => '',
                'Completed Days' => '',
                'Ahead Days' => '',
                'Behind Days' => '',
                'Last Completion Date' => '',
            ]));

            foreach ($rows as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function excelResponse(array $rows, string $filename)
    {
        $headers = array_keys($rows[0] ?? [
            'User' => '',
            'Email' => '',
            'Role' => '',
            'Group' => '',
            'Reading Plan' => '',
            'Plan Type' => '',
            'Reading' => '',
            'Completed Date' => '',
            'Pace Status' => '',
            'Training Status' => '',
            'Training Progress' => '',
            'Expected Day' => '',
            'Completed Days' => '',
            'Ahead Days' => '',
            'Behind Days' => '',
            'Last Completion Date' => '',
        ]);

        $xml = collect($headers)->map(fn (string $header) => $this->excelCell($header, 'String'))->implode('');
        $headerRow = "<Row>{$xml}</Row>";
        $dataRows = collect($rows)->map(function (array $row) {
            $cells = collect($row)->map(fn ($value) => $this->excelCell((string) $value, 'String'))->implode('');

            return "<Row>{$cells}</Row>";
        })->implode('');

        $content = <<<XML
<?xml version="1.0"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:html="http://www.w3.org/TR/REC-html40">
    <Worksheet ss:Name="Progress Report">
        <Table>
            {$headerRow}
            {$dataRows}
        </Table>
    </Worksheet>
</Workbook>
XML;

        return response($content, 200, [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function pdfResponse(array $rows, array $filters, array $scopeData, string $reportType, string $filename)
    {
        $pdf = Pdf::loadView('admin.progress.export-pdf', [
            'rows' => $rows,
            'filters' => $filters,
            'scopeLabel' => $scopeData['scope_label'] ?? null,
            'generatedAt' => now(),
            'reportType' => $reportType,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    private function excelCell(string $value, string $type): string
    {
        $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return "<Cell><Data ss:Type=\"{$type}\">{$escaped}</Data></Cell>";
    }

    private function paceStatusOptions(): array
    {
        return [
            'in_training' => 'In Training',
            'awaiting_start' => 'Awaiting Start',
            'catching_up' => 'Catching Up',
            'on_track' => 'On Track',
            'reading_ahead' => 'Reading Ahead',
            'no_active_plan' => 'No Active Plan',
        ];
    }

    private function trainingStatusOptions(): array
    {
        return [
            'not_required' => 'Not Required',
            'not_started' => 'Not Started',
            'partial' => 'Partially Complete',
            'completed' => 'Completed',
        ];
    }

    private function reportTypeOptions(): array
    {
        return [
            'detail' => 'Detailed rows',
            'hierarchy_summary' => 'Hierarchy summary',
        ];
    }

    private function filteredUsersForSummary(array $filters, array $scopeData): Collection
    {
        $query = $this->reportScope->accessibleUsersQuery(request()->user())
            ->with([
                'hierarchy.parent',
                'hierarchy.leader',
                'readingPlans.trainingResources',
                'readingPlans.dailyReadings',
                'readingProgress.dailyReading',
                'trainingCompletions',
            ]);

        if ($scopeData['user_ids'] === []) {
            return collect();
        }

        $query->whereIn('users.id', $scopeData['user_ids']);

        if (is_array($scopeData['derived_user_ids'])) {
            if ($scopeData['derived_user_ids'] === []) {
                return collect();
            }

            $query->whereIn('users.id', $scopeData['derived_user_ids']);
        }

        if ($filters['user_id'] > 0) {
            $query->where('users.id', $filters['user_id']);
        }

        if ($filters['hierarchy_id'] > 0) {
            $query->where('users.hierarchy_id', $filters['hierarchy_id']);
        }

        if ($filters['role'] !== '') {
            $query->where('users.role', $filters['role']);
        }

        if ($filters['plan_id'] > 0) {
            $query->whereHas('readingPlans', fn (Builder $builder) => $builder->where('reading_plans.id', $filters['plan_id']));
        }

        return $query->orderBy('users.name')->get();
    }
}
