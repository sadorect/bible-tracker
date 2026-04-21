<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Reporting</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Plan progress: {{ $readingPlan->name }}</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-sky-700 text-white shadow-2xl shadow-slate-900/15">
            <div class="grid gap-6 px-6 py-8 sm:px-8 lg:grid-cols-[minmax(0,1.2fr)_minmax(18rem,0.8fr)] lg:px-10">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-100">Plan analytics</p>
                    <h2 class="mt-3 text-3xl font-semibold">{{ $readingPlan->name }}</h2>
                    <p class="mt-2 max-w-2xl text-sm text-slate-200">{{ $readingPlan->description ?: 'No description yet.' }}</p>
                    <p class="mt-3 text-sm text-slate-300">{{ ucfirst(str_replace('_', ' ', $readingPlan->type)) }} · {{ $readingPlan->streak_days }} days on, {{ $readingPlan->break_days }} day break</p>
                </div>
                <div class="flex items-end">
                    <a href="{{ route('admin.progress.index') }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-100">
                        Back to progress dashboard
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-3">
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Total completions</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($totalCompletions) }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Total users</p>
                <p class="mt-3 text-3xl font-semibold text-emerald-700">{{ number_format($totalUsers) }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Active users</p>
                <p class="mt-3 text-3xl font-semibold text-sky-700">{{ number_format($activeUsers) }}</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Trendline</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Completion trend</h2>
                </div>
                <div class="mt-6 h-[320px]">
                    <canvas id="planCompletionChart"></canvas>
                </div>
            </div>

            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Cohort summary</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Plan details</h2>
                </div>
                <div class="mt-6 grid gap-4 sm:grid-cols-2">
                    <div class="rounded-[1.5rem] bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Started</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ \Carbon\Carbon::parse($readingPlan->start_date)->format('M d, Y') }}</p>
                    </div>
                    <div class="rounded-[1.5rem] bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Status</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $readingPlan->is_active ? 'Active' : 'Inactive' }}</p>
                    </div>
                    <div class="rounded-[1.5rem] bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Training days</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $readingPlan->training_days }}</p>
                    </div>
                    <div class="rounded-[1.5rem] bg-slate-50 p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Reading cadence</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $readingPlan->cadence_description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section
            class="overflow-hidden rounded-[2rem] bg-white shadow-xl shadow-slate-900/5"
            data-table-columns="admin-plan-progress-users"
            data-default-columns='{"status":true,"current-day":false,"current-streak":false,"completion-rate":false}'
            data-default-columns-md='{"current-day":true,"completion-rate":true}'
            data-default-columns-xl='{"current-streak":true}'
        >
            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Participant ranking</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">User progress</h2>
                </div>
                <details class="relative">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm shadow-slate-900/5 transition hover:border-slate-300 hover:text-slate-900">
                        <i class="fas fa-table-columns text-slate-400"></i>
                        Display columns
                        <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                    </summary>
                    <div class="absolute right-0 z-10 mt-3 w-72 rounded-3xl border border-slate-200 bg-white p-4 shadow-2xl shadow-slate-900/10">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Participant table</p>
                        <p class="mt-2 text-sm text-slate-500">Choose how much progress detail stays visible in this ranking view.</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <label class="flex items-center gap-3 text-sm text-slate-700">
                                <input type="checkbox" data-column-toggle="status" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                Status
                            </label>
                            <label class="flex items-center gap-3 text-sm text-slate-700">
                                <input type="checkbox" data-column-toggle="current-day" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                Current day
                            </label>
                            <label class="flex items-center gap-3 text-sm text-slate-700">
                                <input type="checkbox" data-column-toggle="current-streak" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                Current streak
                            </label>
                            <label class="flex items-center gap-3 text-sm text-slate-700">
                                <input type="checkbox" data-column-toggle="completion-rate" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                Completion rate
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
                            <th class="px-6 py-4" data-column="status">Status</th>
                            <th class="px-6 py-4" data-column="current-day">Current day</th>
                            <th class="px-6 py-4" data-column="current-streak">Current streak</th>
                            <th class="px-6 py-4" data-column="completion-rate">Completion rate</th>
                            <th class="px-6 py-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse($userStats as $stat)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-6 py-5">
                                    <div>
                                        <a href="{{ route('admin.progress.user', $stat['user']) }}" class="text-sm font-semibold text-slate-900 transition hover:text-emerald-700">
                                            {{ $stat['user']->name }}
                                        </a>
                                        <div class="mt-2 flex flex-wrap gap-2 text-xs md:hidden">
                                            <span class="inline-flex rounded-full px-2.5 py-1 font-semibold {{ $stat['is_active'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                                {{ $stat['is_active'] ? 'Active' : 'Inactive' }}
                                            </span>
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-700">
                                                Day {{ $stat['current_day'] }}
                                            </span>
                                            <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 font-medium text-sky-700">
                                                {{ number_format($stat['completion_rate'], 0) }}%
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5" data-column="status">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $stat['is_active'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $stat['is_active'] ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-sm text-slate-600" data-column="current-day">Day {{ $stat['current_day'] }} of {{ $stat['total_days'] }}</td>
                                <td class="px-6 py-5 text-sm text-slate-600" data-column="current-streak">{{ $stat['current_streak'] }} days</td>
                                <td class="px-6 py-5" data-column="completion-rate">
                                    <div class="flex items-center gap-3">
                                        <div class="h-2.5 w-28 rounded-full bg-slate-200">
                                            <div class="h-2.5 rounded-full bg-emerald-500" style="width: {{ min(100, $stat['completion_rate']) }}%"></div>
                                        </div>
                                        <span class="text-sm text-slate-600">{{ number_format($stat['completion_rate'], 1) }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <a href="{{ route('admin.progress.user', $stat['user']) }}" class="inline-flex rounded-full bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200">
                                        View user
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-14 text-center text-sm text-slate-500">
                                    No users are enrolled in this reading plan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-[2rem] bg-white shadow-xl shadow-slate-900/5">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Recent activity</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Latest completions</h2>
            </div>

            <div class="divide-y divide-slate-200">
                @forelse($recentActivity as $activity)
                    <div class="flex flex-col gap-3 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-slate-600">
                                <a href="{{ route('admin.progress.user', $activity->user) }}" class="font-semibold text-slate-900 transition hover:text-emerald-700">{{ $activity->user->name }}</a>
                                completed <span class="font-semibold">{{ $activity->dailyReading->reading_range }}</span>
                            </p>
                        </div>
                        <div class="text-left sm:text-right">
                            <p class="text-sm font-medium text-slate-900">{{ \Carbon\Carbon::parse($activity->completed_date)->format('M d, Y') }}</p>
                            <p class="text-xs text-slate-500">{{ \Carbon\Carbon::parse($activity->completed_date)->format('g:i A') }}</p>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-14 text-center text-sm text-slate-500">
                        No recent activity found.
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('planCompletionChart').getContext('2d');
                const labels = {!! $chartLabels !!};
                const data = {!! $chartData !!};

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Reading Completions',
                            data: data,
                            backgroundColor: 'rgba(14, 165, 233, 0.12)',
                            borderColor: 'rgba(14, 165, 233, 1)',
                            borderWidth: 2,
                            tension: 0.3,
                            pointBackgroundColor: 'rgba(14, 165, 233, 1)',
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
