<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAuditLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $baseQuery = $this->filteredQuery($filters);

        $auditLogs = (clone $baseQuery)
            ->paginate(20)
            ->withQueryString();

        return view('admin.audits.index', [
            'auditLogs' => $auditLogs,
            'filters' => $filters,
            'eventOptions' => AuditLog::query()
                ->select('event')
                ->distinct()
                ->orderBy('event')
                ->pluck('event'),
            'actorOptions' => User::query()
                ->whereIn('id', AuditLog::query()->whereNotNull('actor_id')->select('actor_id'))
                ->orderBy('name')
                ->get(['id', 'name']),
            'eventSummary' => (clone $baseQuery)
                ->selectRaw('event, count(*) as total')
                ->groupBy('event')
                ->orderByDesc('total')
                ->limit(6)
                ->get(),
            'stats' => [
                'total' => AuditLog::query()->count(),
                'today' => AuditLog::query()->whereDate('created_at', today())->count(),
                'last_7_days' => AuditLog::query()->where('created_at', '>=', now()->subDays(7))->count(),
                'unique_actors' => AuditLog::query()->whereNotNull('actor_id')->distinct('actor_id')->count('actor_id'),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $filename = 'audit-log-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($filters) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Event', 'Actor', 'Subject', 'Description', 'Route', 'Created At']);

            $this->filteredQuery($filters)
                ->chunk(250, function ($logs) use ($handle) {
                    foreach ($logs as $auditLog) {
                        fputcsv($handle, [
                            $auditLog->event,
                            $auditLog->actor?->name ?? 'System',
                            $auditLog->subject_label,
                            $auditLog->description,
                            $auditLog->route_name,
                            $auditLog->created_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function filters(Request $request): array
    {
        return [
            'event' => $request->string('event')->toString(),
            'actor_id' => $request->integer('actor_id'),
            'search' => trim($request->string('search')->toString()),
            'date_range' => $request->string('date_range')->toString() ?: '30_days',
        ];
    }

    private function filteredQuery(array $filters): Builder
    {
        return AuditLog::query()
            ->with(['actor', 'subject'])
            ->when($filters['event'] !== '', fn (Builder $query) => $query->where('event', $filters['event']))
            ->when($filters['actor_id'] > 0, fn (Builder $query) => $query->where('actor_id', $filters['actor_id']))
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $query->where(function (Builder $inner) use ($filters) {
                    $inner->where('event', 'like', '%'.$filters['search'].'%')
                        ->orWhere('description', 'like', '%'.$filters['search'].'%')
                        ->orWhere('subject_label', 'like', '%'.$filters['search'].'%')
                        ->orWhereHas('actor', fn (Builder $actorQuery) => $actorQuery->where('name', 'like', '%'.$filters['search'].'%'));
                });
            })
            ->when($filters['date_range'] !== 'all', function (Builder $query) use ($filters) {
                $days = match ($filters['date_range']) {
                    '24_hours' => 1,
                    '7_days' => 7,
                    default => 30,
                };

                $query->where('created_at', '>=', now()->subDays($days));
            })
            ->latest('created_at');
    }
}
