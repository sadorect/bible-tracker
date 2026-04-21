<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Reporting</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">User progress: {{ $user->name }}</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-700 text-white shadow-2xl shadow-slate-900/15">
            <div class="grid gap-6 px-6 py-8 sm:px-8 lg:grid-cols-[minmax(0,1.2fr)_minmax(18rem,0.8fr)] lg:px-10">
                <div class="flex items-start gap-4">
                    <img class="h-16 w-16 rounded-[1.5rem] object-cover" src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=ffffff&color=0f172a" alt="{{ $user->name }}">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-100">Progress profile</p>
                        <h2 class="mt-3 text-3xl font-semibold">{{ $user->name }}</h2>
                        <p class="mt-2 text-sm text-slate-200">{{ $user->email }}</p>
                        <p class="mt-1 text-sm text-slate-300">Member since {{ $user->created_at->format('M d, Y') }}</p>
                    </div>
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
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Current streak</p>
                <p class="mt-3 text-3xl font-semibold text-emerald-700">{{ number_format($currentStreak) }} days</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Active plans</p>
                <p class="mt-3 text-3xl font-semibold text-sky-700">{{ $userPlans->where('pivot.is_active', true)->count() }}</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Trendline</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Completion trend</h2>
                </div>
                <div class="mt-6 h-[320px]">
                    <canvas id="userCompletionChart"></canvas>
                </div>
            </div>

            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Enrollment summary</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Plan overview</h2>
                </div>
                <div class="mt-6 space-y-3">
                    @forelse($planStats as $stat)
                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-4">
                                <a href="{{ route('admin.progress.plan', $stat['plan']) }}" class="text-sm font-semibold text-slate-900 transition hover:text-emerald-700">
                                    {{ $stat['plan']->name }}
                                </a>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $stat['is_active'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $stat['is_active'] ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-slate-500">Day {{ $stat['current_day'] }} of {{ $stat['total_days'] }}</p>
                            <div class="mt-3 h-2.5 rounded-full bg-slate-200">
                                <div class="h-2.5 rounded-full bg-emerald-500" style="width: {{ min(100, $stat['completion_rate']) }}%"></div>
                            </div>
                            <p class="mt-2 text-xs text-slate-500">{{ number_format($stat['completion_rate'], 1) }}% complete</p>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-200 px-5 py-12 text-center text-sm text-slate-500">
                            User is not enrolled in any reading plans.
                        </div>
                    @endforelse
                </div>
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
                            <p class="text-sm font-semibold text-slate-900">Completed {{ $activity->dailyReading->reading_range }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $activity->readingPlan->name }}</p>
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
                const ctx = document.getElementById('userCompletionChart').getContext('2d');
                const labels = {!! $chartLabels !!};
                const data = {!! $chartData !!};

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
