<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Reporting</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Track reading momentum across the movement.</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Reporting filters</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Slice the progress data</h2>
                    <p class="mt-2 text-sm text-slate-500">{{ $scopeLabel }}</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.progress.export', array_merge(request()->query(), ['format' => 'csv'])) }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Export CSV
                    </a>
                    <a href="{{ route('admin.progress.export', array_merge(request()->query(), ['format' => 'excel'])) }}" class="inline-flex items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 px-4 py-2.5 text-sm font-semibold text-sky-700 transition hover:bg-sky-100">
                        Export Excel
                    </a>
                    <a href="{{ route('admin.progress.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" class="inline-flex items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                        Export PDF
                    </a>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <a href="{{ route('admin.progress.export', array_merge(request()->query(), ['format' => 'csv', 'report_type' => 'hierarchy_summary'])) }}" class="inline-flex items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100">
                    Summary CSV
                </a>
                <a href="{{ route('admin.progress.export', array_merge(request()->query(), ['format' => 'excel', 'report_type' => 'hierarchy_summary'])) }}" class="inline-flex items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 px-4 py-2.5 text-sm font-semibold text-sky-700 transition hover:bg-sky-100">
                    Summary Excel
                </a>
                <a href="{{ route('admin.progress.export', array_merge(request()->query(), ['format' => 'pdf', 'report_type' => 'hierarchy_summary'])) }}" class="inline-flex items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                    Summary PDF
                </a>
            </div>

            <form action="{{ route('admin.progress.index') }}" method="GET" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">User</span>
                    <select name="user_id" id="user_id" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        <option value="">All users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (int) $filters['user_id'] === (int) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Reading plan</span>
                    <select name="plan_id" id="plan_id" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        <option value="">All plans</option>
                        @foreach($readingPlans as $plan)
                            <option value="{{ $plan->id }}" {{ (int) $filters['plan_id'] === (int) $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Hierarchy</span>
                    <select name="hierarchy_id" id="hierarchy_id" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        <option value="">All groups</option>
                        @foreach($hierarchies as $hierarchy)
                            <option value="{{ $hierarchy->id }}" {{ (int) $filters['hierarchy_id'] === (int) $hierarchy->id ? 'selected' : '' }}>
                                {{ $hierarchy->displayPath() }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Role</span>
                    <select name="role" id="role" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        <option value="">All roles</option>
                        @foreach($roleOptions as $value => $label)
                            <option value="{{ $value }}" {{ $filters['role'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Pace status</span>
                    <select name="pace_status" id="pace_status" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        <option value="">Any pace</option>
                        @foreach($paceStatusOptions as $value => $label)
                            <option value="{{ $value }}" {{ $filters['pace_status'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Training status</span>
                    <select name="training_status" id="training_status" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        <option value="">Any training state</option>
                        @foreach($trainingStatusOptions as $value => $label)
                            <option value="{{ $value }}" {{ $filters['training_status'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Date range</span>
                    <select name="date_range" id="date_range" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        <option value="all" {{ $filters['date_range'] == 'all' ? 'selected' : '' }}>All time</option>
                        <option value="today" {{ $filters['date_range'] == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="yesterday" {{ $filters['date_range'] == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="this_week" {{ $filters['date_range'] == 'this_week' ? 'selected' : '' }}>This week</option>
                        <option value="last_week" {{ $filters['date_range'] == 'last_week' ? 'selected' : '' }}>Last week</option>
                        <option value="this_month" {{ $filters['date_range'] == 'this_month' ? 'selected' : '' }}>This month</option>
                        <option value="last_month" {{ $filters['date_range'] == 'last_month' ? 'selected' : '' }}>Last month</option>
                        <option value="custom" {{ $filters['date_range'] == 'custom' ? 'selected' : '' }}>Custom range</option>
                    </select>
                </label>

                <div id="custom_date_range" class="grid gap-4 md:grid-cols-2 md:col-span-2 xl:col-span-2 {{ $filters['date_range'] == 'custom' ? '' : 'hidden' }}">
                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Start date</span>
                        <input type="date" name="start_date" id="start_date" value="{{ $filters['start_date'] }}" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">End date</span>
                        <input type="date" name="end_date" id="end_date" value="{{ $filters['end_date'] }}" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                    </label>
                </div>

                <div class="flex flex-col justify-end gap-3 sm:flex-row xl:col-span-2 xl:flex-row xl:justify-end">
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Apply filters
                    </button>
                    <a href="{{ route('admin.progress.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                        Reset
                    </a>
                </div>
            </form>

            <div class="mt-6 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Saved presets</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">Reuse report filters quickly</h3>
                        <p class="mt-2 text-sm text-slate-500">Presets save your current filters and reopen this page with the same scope and criteria.</p>
                    </div>
                    <form method="POST" action="{{ route('admin.progress.presets.store') }}" class="grid gap-3 sm:grid-cols-[minmax(15rem,1fr)_auto]">
                        @csrf
                        @foreach($filters as $key => $value)
                            @if(!is_array($value))
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <input type="text" name="name" placeholder="Save current filter set as..." class="w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500" required>
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Save preset
                        </button>
                    </form>
                </div>

                <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @forelse($reportPresets as $preset)
                        <div class="rounded-[1.35rem] border border-slate-200 bg-white p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $preset->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Saved {{ $preset->created_at->format('M d, Y g:i A') }}</p>
                                </div>
                                <form method="POST" action="{{ route('admin.progress.presets.destroy', $preset) }}" onsubmit="return confirm('Delete this report preset?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex rounded-full bg-rose-100 px-3 py-1.5 text-xs font-semibold text-rose-700 transition hover:bg-rose-200">
                                        Delete
                                    </button>
                                </form>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                <a href="{{ route('admin.progress.index', $preset->filters ?? []) }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700">
                                    Open preset
                                </a>
                                <a href="{{ route('admin.progress.export', array_merge($preset->filters ?? [], ['format' => 'csv'])) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-white">
                                    Export CSV
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.35rem] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500 md:col-span-2 xl:col-span-3">
                            No saved report presets yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Total completions</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($stats['total_completions']) }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Active users</p>
                <p class="mt-3 text-3xl font-semibold text-emerald-700">{{ number_format($stats['active_users']) }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Active plans</p>
                <p class="mt-3 text-3xl font-semibold text-sky-700">{{ number_format($stats['active_plans']) }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Avg completions per active user</p>
                <p class="mt-3 text-3xl font-semibold text-amber-700">{{ $stats['active_users'] > 0 ? number_format($stats['total_completions'] / $stats['active_users'], 1) : '0' }}</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Trendline</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Completion trend</h2>
                </div>
                <div class="mt-6 h-[320px]">
                    <canvas id="completionChart"></canvas>
                </div>
            </div>

            <div class="grid gap-6">
                <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Top users</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Most reported completions</h2>
                    </div>
                    <div class="mt-6 space-y-3">
                        @if($stats['completions_by_user']->isNotEmpty())
                            @foreach($stats['completions_by_user'] as $userStat)
                                <div class="flex items-center justify-between rounded-[1.35rem] border border-slate-200 bg-slate-50 px-4 py-4">
                                    <a href="{{ route('admin.progress.user', ['user' => $userStat->id]) }}" class="text-sm font-semibold text-slate-900 transition hover:text-emerald-700">
                                        {{ $userStat->name }}
                                    </a>
                                    <span class="text-sm font-medium text-emerald-700">{{ number_format($userStat->count) }} completions</span>
                                </div>
                            @endforeach
                        @else
                            <div class="rounded-[1.35rem] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                No data available.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Top plans</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Highest activity</h2>
                    </div>
                    <div class="mt-6 space-y-3">
                        @if($stats['completions_by_plan']->isNotEmpty())
                            @foreach($stats['completions_by_plan'] as $planStat)
                                <div class="flex items-center justify-between rounded-[1.35rem] border border-slate-200 bg-slate-50 px-4 py-4">
                                    <a href="{{ route('admin.progress.plan', ['readingPlan' => $planStat->id]) }}" class="text-sm font-semibold text-slate-900 transition hover:text-emerald-700">
                                        {{ $planStat->name }}
                                    </a>
                                    <span class="text-sm font-medium text-emerald-700">{{ number_format($planStat->count) }} completions</span>
                                </div>
                            @endforeach
                        @else
                            <div class="rounded-[1.35rem] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                No data available.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section
            class="overflow-hidden rounded-[2rem] bg-white shadow-xl shadow-slate-900/5"
            data-table-columns="admin-progress-index"
            data-default-columns='{"reading-plan":false,"reading":false,"completed-date":true}'
            data-default-columns-md='{"reading-plan":true,"reading":true}'
        >
            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Latest entries</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Recent reading progress</h2>
                </div>
                <details class="relative">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm shadow-slate-900/5 transition hover:border-slate-300 hover:text-slate-900">
                        <i class="fas fa-table-columns text-slate-400"></i>
                        Display columns
                        <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                    </summary>
                    <div class="absolute right-0 z-10 mt-3 w-72 rounded-3xl border border-slate-200 bg-white p-4 shadow-2xl shadow-slate-900/10">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Progress table</p>
                        <p class="mt-2 text-sm text-slate-500">Choose how much recent-entry detail stays visible on this device.</p>
                        <div class="mt-4 grid gap-3">
                            <label class="flex items-center gap-3 text-sm text-slate-700">
                                <input type="checkbox" data-column-toggle="reading-plan" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                Reading plan
                            </label>
                            <label class="flex items-center gap-3 text-sm text-slate-700">
                                <input type="checkbox" data-column-toggle="reading" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                Reading
                            </label>
                            <label class="flex items-center gap-3 text-sm text-slate-700">
                                <input type="checkbox" data-column-toggle="completed-date" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                Completed date
                            </label>
                        </div>
                        <button type="button" data-table-columns-reset class="mt-4 inline-flex items-center rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-slate-900">
                            Reset compact defaults
                        </button>
                    </div>
                </details>
            </div>

            <div class="overflow-x-auto" data-table-columns-root>
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4" data-column="reading-plan">Reading plan</th>
                            <th class="px-6 py-4" data-column="reading">Reading</th>
                            <th class="px-6 py-4" data-column="completed-date">Completed date</th>
                            <th class="px-6 py-4">Scope</th>
                            <th class="px-6 py-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse($progress as $record)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-6 py-5">
                                    <div>
                                        <a href="{{ route('admin.progress.user', $record->user) }}" class="text-sm font-semibold text-slate-900 transition hover:text-emerald-700">
                                            {{ $record->user->name }}
                                        </a>
                                        <div class="mt-2 space-y-1 text-xs text-slate-500 md:hidden">
                                            <p>{{ $record->readingPlan->name }}</p>
                                            <p>{{ \Carbon\Carbon::parse($record->completed_date)->format('M d, Y g:i A') }}</p>
                                            <p>{{ $record->user->hierarchy?->name ?? 'Unassigned' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5" data-column="reading-plan">
                                    <a href="{{ route('admin.progress.plan', $record->readingPlan) }}" class="text-sm text-slate-600 transition hover:text-emerald-700">
                                        {{ $record->readingPlan->name }}
                                    </a>
                                </td>
                                <td class="px-6 py-5 text-sm text-slate-600" data-column="reading">{{ $record->dailyReading->reading_range }}</td>
                                <td class="px-6 py-5 text-sm text-slate-500" data-column="completed-date">{{ \Carbon\Carbon::parse($record->completed_date)->format('M d, Y g:i A') }}</td>
                                <td class="px-6 py-5 text-sm text-slate-500">
                                    {{ $record->user->hierarchy?->displayPath() ?? 'Unassigned' }}
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex flex-wrap gap-2 text-sm font-medium">
                                        <a href="{{ route('admin.progress.user', $record->user) }}" class="inline-flex rounded-full bg-slate-100 px-3 py-1.5 text-slate-700 transition hover:bg-slate-200">
                                            View user
                                        </a>
                                        <a href="{{ route('admin.progress.plan', $record->readingPlan) }}" class="inline-flex rounded-full bg-sky-100 px-3 py-1.5 text-sky-700 transition hover:bg-sky-200">
                                            View plan
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-14 text-center text-sm text-slate-500">
                                    No reading progress records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $progress->links() }}
            </div>
        </section>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.getElementById('date_range').addEventListener('change', function() {
                const customDateRange = document.getElementById('custom_date_range');

                if (this.value === 'custom') {
                    customDateRange.classList.remove('hidden');
                } else {
                    customDateRange.classList.add('hidden');
                }
            });

            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('completionChart').getContext('2d');
                const labels = {!! $stats['chart_labels'] !!};
                const data = {!! $stats['chart_data'] !!};

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Reading Completions',
                            data: data,
                            backgroundColor: 'rgba(16, 185, 129, 0.12)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 2,
                            tension: 0.3,
                            pointBackgroundColor: 'rgba(16, 185, 129, 1)',
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            });
        </script>
    @endpush
</x-admin-layout>
