<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">{{ $readingPlan->type_label }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ $readingPlan->name }}</h2>
        </div>
    </x-slot>

    @php
        $isActive = $userPlan && $userPlan->pivot->is_active;
        $isJoined = $userPlan !== null;
        $previewReadings = $readingPlan->dailyReadings()->orderBy('day_number')->take(12)->get();
    @endphp

    <div class="space-y-6">
        <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-700 text-white shadow-2xl shadow-slate-900/15">
            <div class="grid gap-6 px-6 py-8 sm:px-8 lg:grid-cols-[minmax(0,1.45fr)_minmax(18rem,0.75fr)]">
                <div>
                    <div class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-100">
                        Cohort Overview
                    </div>
                    <h3 class="mt-4 text-3xl font-semibold tracking-tight">{{ $readingPlan->name }}</h3>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-200">{{ $readingPlan->description }}</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/10 px-4 py-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-emerald-100">Cadence</p>
                        <p class="mt-2 text-lg font-semibold">{{ $readingPlan->streak_days }} days on / {{ $readingPlan->break_days }} day off</p>
                    </div>
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/10 px-4 py-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-emerald-100">Commences</p>
                        <p class="mt-2 text-lg font-semibold">{{ $readingPlan->start_date?->format('M d, Y') ?? 'TBD' }}</p>
                    </div>
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/10 px-4 py-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-emerald-100">Scheduled days</p>
                        <p class="mt-2 text-lg font-semibold">{{ $readingPlan->duration_days }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(19rem,0.7fr)]">
            <article class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 lg:p-8">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-[1.35rem] bg-stone-50 px-4 py-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Stage</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $readingPlan->type_label }}</p>
                    </div>
                    <div class="rounded-[1.35rem] bg-stone-50 px-4 py-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Reading opens</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $readingPlan->reading_start_date?->format('M d, Y') ?? 'TBD' }}</p>
                    </div>
                    <div class="rounded-[1.35rem] bg-stone-50 px-4 py-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Plan ends</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $readingPlan->end_date?->format('M d, Y') ?? 'TBD' }}</p>
                    </div>
                    <div class="rounded-[1.35rem] bg-stone-50 px-4 py-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Community</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ $readingPlan->users()->count() }} people enrolled</p>
                    </div>
                </div>

                @if(!$canJoin && !$isJoined)
                    <div class="mt-6 rounded-[1.35rem] border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        {{ $lockedReason }}
                    </div>
                @endif

                <div class="mt-8">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-semibold text-slate-900">Reading schedule preview</h3>
                        @if($readingPlan->dailyReadings()->count() > $previewReadings->count())
                            <span class="text-sm text-slate-500">Showing first {{ $previewReadings->count() }} days</span>
                        @endif
                    </div>

                    <div
                        class="mt-4 overflow-hidden rounded-[1.5rem] border border-stone-200"
                        data-table-columns="reading-plan-preview"
                        data-default-columns='{"type":false}'
                        data-default-columns-md='{"type":true}'
                    >
                        <div class="border-b border-stone-200 bg-white px-4 py-3 sm:flex sm:items-center sm:justify-end">
                            <details class="relative">
                                <summary class="flex cursor-pointer list-none items-center gap-2 rounded-2xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm shadow-slate-900/5 transition hover:border-stone-300 hover:text-slate-900">
                                    <i class="fas fa-table-columns text-slate-400"></i>
                                    Display columns
                                    <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                                </summary>
                                <div class="absolute right-0 z-10 mt-3 w-72 rounded-3xl border border-stone-200 bg-white p-4 shadow-2xl shadow-slate-900/10">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Schedule preview</p>
                                    <p class="mt-2 text-sm text-slate-500">Choose whether the schedule type column stays visible on this device.</p>
                                    <div class="mt-4 grid gap-3">
                                        <label class="flex items-center gap-3 text-sm text-slate-700">
                                            <input type="checkbox" data-column-toggle="type" class="rounded border-stone-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                            Type
                                        </label>
                                    </div>
                                    <button type="button" data-table-columns-reset class="mt-4 inline-flex items-center rounded-2xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-slate-900">
                                        Reset compact defaults
                                    </button>
                                </div>
                            </details>
                        </div>

                        <div data-table-columns-root>
                        <table class="min-w-full divide-y divide-stone-200">
                            <thead class="bg-stone-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Day</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Reading</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500" data-column="type">Type</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-200 bg-white">
                                @foreach($previewReadings as $reading)
                                    <tr>
                                        <td class="px-4 py-4 text-sm font-medium text-slate-900">
                                            <div>
                                                <p>Day {{ $reading->day_number }}</p>
                                                <p class="mt-1 text-xs font-normal text-slate-500 md:hidden">{{ $reading->is_break_day ? 'Refresh Break' : 'Reading Day' }}</p>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-slate-600">{{ $reading->reading_range }}</td>
                                        <td class="px-4 py-4 text-sm" data-column="type">
                                            @if($reading->is_break_day)
                                                <span class="inline-flex rounded-full bg-stone-200 px-3 py-1 text-xs font-semibold text-stone-700">Refresh Break</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">Reading Day</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

                <div class="mt-8 grid gap-6 lg:grid-cols-2">
                    <div class="rounded-[1.5rem] bg-stone-50 p-5">
                        <h4 class="text-lg font-semibold text-slate-900">About this plan</h4>
                        <p class="mt-3 text-sm leading-7 text-slate-600">{{ $readingPlan->description }}</p>
                        @if($readingPlan->additional_info)
                            <div class="mt-4 text-sm leading-7 text-slate-600">
                                {!! nl2br(e($readingPlan->additional_info)) !!}
                            </div>
                        @endif
                    </div>

                    <div class="rounded-[1.5rem] bg-stone-50 p-5">
                        <h4 class="text-lg font-semibold text-slate-900">Recent participants</h4>
                        <p class="mt-3 text-sm text-slate-600">
                            <span class="font-semibold text-slate-900">{{ $readingPlan->users()->count() }}</span> people are currently following this reading plan.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            @forelse($readingPlan->users()->latest('user_reading_plans.created_at')->take(6)->get() as $user)
                                <span class="rounded-full border border-stone-200 bg-white px-3 py-1.5 text-sm text-slate-700">{{ $user->name }}</span>
                            @empty
                                <span class="text-sm text-slate-500">No one has joined this cohort yet.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </article>

            <aside class="space-y-6">
                <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Your access</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">
                        @if($isActive)
                            You are actively following this cohort.
                        @elseif($isJoined)
                            You were previously enrolled here.
                        @elseif(!$canJoin)
                            This cohort is currently locked.
                        @else
                            You can join this cohort now.
                        @endif
                    </h3>

                    <div class="mt-6 flex flex-wrap gap-3">
                        @if($isActive)
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800">
                                Go to Dashboard
                            </a>
                            <form action="{{ route('reading-plans.leave', $readingPlan) }}" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-rose-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-rose-700">
                                    Leave Plan
                                </button>
                            </form>
                        @elseif($isJoined)
                            <form action="{{ route('reading-plans.join', $readingPlan) }}" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700">
                                    Resume Plan
                                </button>
                            </form>
                        @elseif(!$canJoin)
                            <button disabled class="inline-flex items-center justify-center rounded-2xl bg-stone-200 px-4 py-2.5 text-sm font-medium text-stone-600">
                                Locked
                            </button>
                        @else
                            <form action="{{ route('reading-plans.join', $readingPlan) }}" method="POST">
                                @csrf
                                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800">
                                    Join Plan
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Cadence reminder</p>
                    <div class="mt-4 space-y-3">
                        <div class="rounded-[1.35rem] bg-stone-50 px-4 py-3">
                            <p class="text-sm font-semibold text-slate-900">{{ $readingPlan->chapters_per_day }} chapters daily</p>
                            <p class="mt-1 text-sm text-slate-600">Stay steady on each reading day.</p>
                        </div>
                        <div class="rounded-[1.35rem] bg-stone-50 px-4 py-3">
                            <p class="text-sm font-semibold text-slate-900">{{ $readingPlan->streak_days }} days on, {{ $readingPlan->break_days }} day off</p>
                            <p class="mt-1 text-sm text-slate-600">Use break days for refresh and prayers.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between px-1 text-sm">
                    <a href="{{ route('reading-plans.index') }}" class="font-medium text-emerald-700 transition hover:text-emerald-800">
                        &larr; Back to plans
                    </a>
                    @if($isActive)
                        <a href="{{ route('dashboard') }}" class="font-medium text-emerald-700 transition hover:text-emerald-800">
                            Dashboard &rarr;
                        </a>
                    @endif
                </div>
            </aside>
        </section>
    </div>
</x-app-layout>
