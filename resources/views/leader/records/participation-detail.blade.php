<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Participation Drilldown</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ $member->name }} · {{ $participation->readingPlan?->name ?? 'Unknown plan' }}</h2>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Cycle summary</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $participation->readingPlan?->name ?? 'Unknown plan' }}</h3>
                    <p class="mt-2 text-sm text-slate-500">
                        Participation #{{ $participation->participation_number }}
                        · Started {{ $participation->started_on?->format('M d, Y') ?? '—' }}
                        @if($participation->ended_on)
                            · Ended {{ $participation->ended_on->format('M d, Y') }}
                        @endif
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('hierarchy.members.show', $member) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Back to member record
                    </a>
                    @if($reportUrl)
                        <a href="{{ $reportUrl }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Admin detail
                        </a>
                    @endif
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-3">
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Completed days</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $completedDays }} / {{ $requiredDays }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Completion rate</p>
                <p class="mt-3 text-3xl font-semibold text-emerald-700">{{ number_format($completionRate, 1) }}%</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Status</p>
                <p class="mt-3 text-3xl font-semibold text-sky-700">{{ ucfirst($participation->status) }}</p>
            </article>
        </section>

        <section class="rounded-[2rem] bg-white shadow-xl shadow-slate-900/5">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Recorded completions</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Participation activity log</h2>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($participation->progress->sortByDesc('completed_date') as $progress)
                    <div class="px-6 py-4">
                        <p class="text-sm font-semibold text-slate-900">{{ $progress->dailyReading?->reading_range ?? 'Reading recorded' }}</p>
                        <p class="mt-1 text-sm text-slate-500">Day {{ $progress->dailyReading?->day_number ?? '—' }}</p>
                        <p class="mt-2 text-xs uppercase tracking-[0.2em] text-slate-400">{{ $progress->completed_date?->format('M d, Y') ?? 'Unknown date' }}</p>
                    </div>
                @empty
                    <div class="px-6 py-14 text-center text-sm text-slate-500">
                        No progress has been recorded for this participation yet.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
