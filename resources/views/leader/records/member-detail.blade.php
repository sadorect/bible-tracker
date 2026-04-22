<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Leader Record View</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ $member->name }}</h2>
        </div>
    </x-slot>

    @php
        $toneClasses = match ($snapshot['status_tone']) {
            'amber' => 'bg-amber-100 text-amber-800',
            'rose' => 'bg-rose-100 text-rose-800',
            'indigo' => 'bg-indigo-100 text-indigo-800',
            'green' => 'bg-green-100 text-green-800',
            'sky' => 'bg-sky-100 text-sky-800',
            default => 'bg-slate-100 text-slate-800',
        };
    @endphp

    <div class="space-y-6">
        <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-700 text-white shadow-2xl shadow-slate-900/15">
            <div class="grid gap-6 px-6 py-8 sm:px-8 lg:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-100">Monitoring scope</p>
                    <h3 class="mt-3 text-3xl font-semibold">{{ $member->name }}</h3>
                    <p class="mt-2 text-sm text-slate-200">{{ $member->email }}</p>
                    <p class="mt-1 text-sm text-slate-300">{{ $member->hierarchy?->displayPath() ?? 'Unassigned to a group' }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-white">{{ $member->roleLabel() }}</span>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $toneClasses }}">{{ $snapshot['status_label'] }}</span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 lg:justify-end">
                    <a href="{{ route('hierarchy.manage') }}" class="inline-flex items-center justify-center rounded-2xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-slate-100">
                        Back to monitor
                    </a>
                    @if($reportsIndexUrl)
                        <a href="{{ $reportsIndexUrl }}" class="inline-flex items-center justify-center rounded-2xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/15">
                            Open scoped report
                        </a>
                    @endif
                    @if($reportUrl)
                        <a href="{{ $reportUrl }}" class="inline-flex items-center justify-center rounded-2xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/15">
                            Admin detail
                        </a>
                    @endif
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Training</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $snapshot['training_progress'] }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Expected day</p>
                <p class="mt-3 text-3xl font-semibold text-emerald-700">{{ $snapshot['expected_day'] ?? '—' }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Completed days</p>
                <p class="mt-3 text-3xl font-semibold text-sky-700">{{ $snapshot['completed_days'] }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Behind</p>
                <p class="mt-3 text-3xl font-semibold text-rose-700">{{ $snapshot['behind_days'] }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Ahead</p>
                <p class="mt-3 text-3xl font-semibold text-indigo-700">{{ $snapshot['ahead_days'] }}</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
            <div class="rounded-[2rem] bg-white shadow-xl shadow-slate-900/5">
                <div class="border-b border-slate-200 px-6 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Participation history</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Cycle-by-cycle drilldown</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($participations as $item)
                        <div class="px-6 py-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-lg font-semibold text-slate-900">{{ $item['plan']?->name ?? 'Unknown plan' }}</p>
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $item['is_current'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                            {{ $item['is_current'] ? 'Current cycle' : ucfirst($item['participation']->status) }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm text-slate-500">
                                        Started {{ $item['participation']->started_on?->format('M d, Y') ?? '—' }}
                                        @if($item['participation']->ended_on)
                                            · Ended {{ $item['participation']->ended_on->format('M d, Y') }}
                                        @endif
                                    </p>
                                </div>
                                <a href="{{ route('hierarchy.members.participations.show', [$member, $item['participation']]) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                    View cycle detail
                                </a>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Completed</p>
                                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ $item['completed_days'] }} / {{ $item['required_days'] }}</p>
                                </div>
                                <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Completion rate</p>
                                    <p class="mt-2 text-lg font-semibold text-emerald-700">{{ number_format($item['completion_rate'], 1) }}%</p>
                                </div>
                                <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Last completion</p>
                                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ $item['last_completion_date']?->format('M d, Y') ?? 'None yet' }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-14 text-center text-sm text-slate-500">
                            No participation records are available for this person yet.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-[2rem] bg-white shadow-xl shadow-slate-900/5">
                <div class="border-b border-slate-200 px-6 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Recent activity</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Latest reported readings</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($recentActivity as $activity)
                        <div class="px-6 py-4">
                            <p class="text-sm font-semibold text-slate-900">{{ $activity->dailyReading?->reading_range ?? 'Reading recorded' }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $activity->readingPlan?->name ?? 'Unknown plan' }}</p>
                            <p class="mt-2 text-xs uppercase tracking-[0.2em] text-slate-400">{{ $activity->completed_date?->format('M d, Y') ?? 'Unknown date' }}</p>
                        </div>
                    @empty
                        <div class="px-6 py-14 text-center text-sm text-slate-500">
                            No readings have been reported yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</x-app-layout>
