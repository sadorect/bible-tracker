<x-admin-layout>
    <div class="space-y-6">
        <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-700 text-white shadow-2xl shadow-slate-900/15">
            <div class="grid gap-8 px-6 py-8 sm:px-8 lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,0.8fr)] lg:px-10 lg:py-10">
                <div class="space-y-5">
                    <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.28em] text-emerald-100">
                        Ministry Operations
                    </span>
                    <div class="space-y-3">
                        <h1 class="max-w-3xl text-3xl font-semibold tracking-tight sm:text-4xl">Oversee cohorts, training, breaks, and reading momentum from one place.</h1>
                        <p class="max-w-2xl text-sm leading-7 text-slate-200 sm:text-base">
                            This dashboard gives you the operational picture: who is active, what cohorts are upcoming, where completions are flowing, and which plans are carrying the strongest engagement.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm text-slate-100">
                        <span class="rounded-full bg-white/10 px-4 py-2">{{ number_format($stats['active_users']) }} active readers</span>
                        <span class="rounded-full bg-white/10 px-4 py-2">{{ number_format($stats['training_resources']) }} training resources published</span>
                        <span class="rounded-full bg-white/10 px-4 py-2">{{ number_format($stats['upcoming_plans']) }} upcoming cohorts</span>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                    <div class="rounded-[1.75rem] border border-white/10 bg-white/10 p-5 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.24em] text-emerald-100">Today</p>
                        <p class="mt-3 text-3xl font-semibold">{{ number_format($stats['today_completions']) }}</p>
                        <p class="mt-1 text-sm text-slate-200">Reported completions</p>
                    </div>
                    <div class="rounded-[1.75rem] border border-white/10 bg-white/10 p-5 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.24em] text-emerald-100">This week</p>
                        <p class="mt-3 text-3xl font-semibold">{{ number_format($stats['this_week_completions']) }}</p>
                        <p class="mt-1 text-sm text-slate-200">Completed reading entries</p>
                    </div>
                    <div class="rounded-[1.75rem] border border-white/10 bg-white/10 p-5 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.24em] text-emerald-100">This month</p>
                        <p class="mt-3 text-3xl font-semibold">{{ number_format($stats['this_month_completions']) }}</p>
                        <p class="mt-1 text-sm text-slate-200">Recorded reading progress</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Total users</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($stats['total_users']) }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ number_format($stats['inactive_users']) }} currently without an active plan.</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Active plans</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($stats['active_plans']) }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ number_format($stats['upcoming_plans']) }} cohorts are still waiting to start.</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Published plans</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($stats['total_plans']) }}</p>
                <p class="mt-2 text-sm text-slate-500">Each cohort can run with its own reading streak, break window, and training load.</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Total completions</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($stats['total_completions']) }}</p>
                <p class="mt-2 text-sm text-slate-500">All recorded reading reports across cohorts.</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Cohort Watch</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Plan health and readiness</h2>
                    </div>
                    <a href="{{ route('admin.reading-plans.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-white">
                        Manage plans
                    </a>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-2">
                    @forelse($planSnapshots as $snapshot)
                        @php
                            $toneClasses = match ($snapshot['status_tone']) {
                                'emerald' => 'bg-emerald-100 text-emerald-700',
                                'sky' => 'bg-sky-100 text-sky-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">{{ $snapshot['plan']->name }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">{{ $snapshot['plan']->type_label }} · {{ $snapshot['plan']->cadence_description }}</p>
                                </div>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $toneClasses }}">
                                    {{ $snapshot['status_label'] }}
                                </span>
                            </div>

                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-[1.2rem] bg-white px-4 py-3">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Start</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $snapshot['plan']->start_date?->format('M d, Y') ?? 'TBD' }}</p>
                                </div>
                                <div class="rounded-[1.2rem] bg-white px-4 py-3">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Reading opens</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $snapshot['plan']->reading_start_date?->format('M d, Y') ?? 'TBD' }}</p>
                                </div>
                                <div class="rounded-[1.2rem] bg-white px-4 py-3">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Participants</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ number_format($snapshot['plan']->active_participants_count) }}</p>
                                </div>
                                <div class="rounded-[1.2rem] bg-white px-4 py-3">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Training resources</p>
                                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ number_format($snapshot['plan']->training_resources_count) }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center justify-between text-sm text-slate-500">
                                <span>{{ number_format($snapshot['plan']->daily_readings_count) }} schedule days</span>
                                <span>{{ number_format($snapshot['plan']->reading_progress_count) }} completions</span>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-200 px-5 py-12 text-center text-sm text-slate-500 lg:col-span-2">
                            No cohorts have been created yet.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="grid gap-6">
                <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Quick Actions</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-900">Keep operations moving</h2>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3">
                        <a href="{{ route('admin.reading-plans.index') }}" class="inline-flex items-center justify-between rounded-[1.35rem] bg-slate-900 px-5 py-4 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Manage reading plans
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="{{ route('admin.progress.index') }}" class="inline-flex items-center justify-between rounded-[1.35rem] bg-emerald-600 px-5 py-4 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            Review progress reports
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-between rounded-[1.35rem] bg-white px-5 py-4 text-sm font-semibold text-slate-800 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
                            Manage users and leaders
                            <i class="fas fa-arrow-right text-slate-400"></i>
                        </a>
                        <a href="{{ route('admin.hierarchies.index') }}" class="inline-flex items-center justify-between rounded-[1.35rem] bg-white px-5 py-4 text-sm font-semibold text-slate-800 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
                            Build hierarchy structure
                            <i class="fas fa-arrow-right text-slate-400"></i>
                        </a>
                        @if(auth()->user()->hasPermissionTo('automation.manage'))
                            <a href="{{ route('admin.automation.index') }}" class="inline-flex items-center justify-between rounded-[1.35rem] bg-white px-5 py-4 text-sm font-semibold text-slate-800 shadow-sm ring-1 ring-slate-200 transition hover:bg-slate-50">
                                Manage automation
                                <i class="fas fa-arrow-right text-slate-400"></i>
                            </a>
                        @endif
                    </div>
                </div>

                <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Top Performers</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-900">This week’s strongest pace</h2>
                        </div>
                    </div>

                    <div class="mt-5 space-y-3">
                        @forelse($topPerformers as $index => $performer)
                            <div class="flex items-center justify-between rounded-[1.35rem] border border-slate-200 bg-slate-50 px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-amber-400/20 text-sm font-semibold text-amber-700">
                                        {{ $index + 1 }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ $performer->name }}</p>
                                        <p class="text-xs capitalize text-slate-500">{{ str_replace('_', ' ', $performer->role) }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-semibold text-emerald-700">{{ $performer->reading_progress_count }}</p>
                                    <p class="text-xs text-slate-500">completions</p>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-[1.35rem] border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-500">
                                No activity has been recorded this week yet.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Recent Activity</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Latest reported completions</h2>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse($recentActivity as $activity)
                        <div class="flex flex-col gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <x-user-avatar :name="$activity->user->name" class="h-11 w-11 rounded-2xl bg-slate-900 text-white" />
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $activity->user->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $activity->dailyReading->readingPlan->name ?? 'Unknown Plan' }}</p>
                                </div>
                            </div>
                            <div class="text-left sm:text-right">
                                <p class="text-sm font-semibold text-emerald-700">Completed</p>
                                <p class="text-xs text-slate-500">{{ $activity->completed_date->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.35rem] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                            No recent activity yet.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Engagement</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Most active plans in the last 30 days</h2>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse($popularPlans as $plan)
                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $plan->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $plan->type_label }} · {{ $plan->cadence_description }}</p>
                                </div>
                                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                    {{ number_format($plan->reading_progress_count) }} completions
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-[1.35rem] border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                            No plan activity has been recorded in the last 30 days.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- Site Visit Analytics --}}
        <section class="space-y-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Site Analytics</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Visitor activity overview</h2>
            </div>

            {{-- Summary cards --}}
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Total page views</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($visitAnalytics['total']) }}</p>
                    <p class="mt-2 text-sm text-slate-500">All time</p>
                </article>
                <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Today</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($visitAnalytics['today']) }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ number_format($visitAnalytics['unique_today']) }} unique sessions</p>
                </article>
                <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">This week</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($visitAnalytics['this_week']) }}</p>
                    <p class="mt-2 text-sm text-slate-500">{{ number_format($visitAnalytics['unique_week']) }} unique sessions</p>
                </article>
                <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">This month</p>
                    <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($visitAnalytics['this_month']) }}</p>
                    <p class="mt-2 text-sm text-slate-500">Page views in {{ now()->format('F') }}</p>
                </article>
            </div>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,0.6fr)]">
                {{-- 14-day chart --}}
                <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Daily traffic</p>
                    <h3 class="mt-2 text-xl font-semibold text-slate-900">Last 14 days</h3>
                    <div class="mt-6" style="height:220px">
                        <canvas id="visitChart"></canvas>
                    </div>
                    @push('scripts')
                    <script>
                        (function () {
                            const labels  = @json($visitSeries->pluck('label'));
                            const visits  = @json($visitSeries->pluck('visits'));
                            const unique  = @json($visitSeries->pluck('unique_visitors'));

                            const ctx = document.getElementById('visitChart');
                            new Chart(ctx, {
                                type: 'bar',
                                data: {
                                    labels,
                                    datasets: [
                                        {
                                            label: 'Page views',
                                            data: visits,
                                            backgroundColor: 'rgba(5, 150, 105, 0.75)',
                                            borderRadius: 6,
                                            order: 2,
                                        },
                                        {
                                            label: 'Unique sessions',
                                            data: unique,
                                            type: 'line',
                                            borderColor: '#f59e0b',
                                            backgroundColor: 'rgba(245,158,11,0.12)',
                                            pointBackgroundColor: '#f59e0b',
                                            tension: 0.4,
                                            fill: true,
                                            order: 1,
                                        },
                                    ],
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: false,
                                    plugins: { legend: { position: 'bottom' } },
                                    scales: {
                                        x: { grid: { display: false } },
                                        y: { beginAtZero: true, ticks: { precision: 0 } },
                                    },
                                },
                            });
                        })();
                    </script>
                    @endpush
                </div>

                {{-- Top pages --}}
                <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Top pages</p>
                    <h3 class="mt-2 text-xl font-semibold text-slate-900">Last 30 days</h3>
                    <div class="mt-5 space-y-2">
                        @forelse($topPages as $page)
                            @php
                                $path = parse_url($page->url, PHP_URL_PATH) ?: '/';
                                $maxVisits = $topPages->first()->visits ?: 1;
                                $pct = round(($page->visits / $maxVisits) * 100);
                            @endphp
                            <div>
                                <div class="flex items-center justify-between text-xs text-slate-600">
                                    <span class="truncate max-w-[180px]" title="{{ $path }}">{{ $path }}</span>
                                    <span class="ml-2 flex-shrink-0 font-semibold text-slate-800">{{ number_format($page->visits) }}</span>
                                </div>
                                <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-emerald-500" style="width:{{ $pct }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-slate-400">No page view data yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-admin-layout>
