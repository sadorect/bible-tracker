<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Progress Report Export</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; }
        h1 { margin: 0 0 8px; font-size: 22px; }
        p { margin: 0 0 6px; }
        .meta { margin-bottom: 18px; }
        .badge { display: inline-block; margin-right: 8px; padding: 4px 8px; background: #e2e8f0; border-radius: 999px; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f8fafc; font-size: 10px; text-transform: uppercase; }
        tbody tr:nth-child(even) { background: #f8fafc; }
    </style>
</head>
<body>
    <h1>{{ $reportType === 'hierarchy_summary' ? 'Hierarchy Summary Report' : 'Progress Report' }}</h1>

    <div class="meta">
        <p><strong>Scope:</strong> {{ $scopeLabel }}</p>
        <p><strong>Generated:</strong> {{ $generatedAt->format('M d, Y g:i A') }}</p>
        <p>
            <span class="badge">User: {{ $filters['user_id'] ?: 'All' }}</span>
            <span class="badge">Plan: {{ $filters['plan_id'] ?: 'All' }}</span>
            <span class="badge">Hierarchy: {{ $filters['hierarchy_id'] ?: 'All' }}</span>
            <span class="badge">Role: {{ $filters['role'] ?: 'All' }}</span>
            <span class="badge">Pace: {{ $filters['pace_status'] ?: 'Any' }}</span>
            <span class="badge">Training: {{ $filters['training_status'] ?: 'Any' }}</span>
            <span class="badge">Range: {{ $filters['date_range'] ?: 'all' }}</span>
        </p>
    </div>

    <table>
        <thead>
            <tr>
                @foreach(array_keys($rows[0] ?? ['User' => '']) as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="16">No report rows matched the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
