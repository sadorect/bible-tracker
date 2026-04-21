<div class="space-y-6">
    <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 lg:p-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">{{ $readingPlan->type_label }}</p>
                <h3 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{{ $readingPlan->name }} Progress</h3>
                <p class="mt-3 text-sm leading-7 text-slate-600">A complete day-by-day record of your journey, including break days, completed readings, missed assignments, and upcoming schedule days.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-2xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-stone-50">
                Back to Dashboard
            </a>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Completion Rate</p>
            <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $statistics['completion_rate'] }}%</p>
        </article>
        <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Current Streak</p>
            <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $statistics['current_streak'] }} days</p>
        </article>
        <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Completed</p>
            <p class="mt-3 text-3xl font-semibold text-emerald-700">{{ $statistics['completed_days'] }} days</p>
        </article>
        <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Missed</p>
            <p class="mt-3 text-3xl font-semibold text-rose-700">{{ $statistics['missed_days'] }} days</p>
        </article>
    </section>

    <section
        class="overflow-hidden rounded-[2rem] bg-white shadow-xl shadow-slate-900/5"
        data-table-columns="member-plan-progress"
        data-default-columns='{"date":false,"completed-on":false}'
        data-default-columns-md='{"date":true}'
        data-default-columns-xl='{"completed-on":true}'
    >
        <div class="flex flex-col gap-3 border-b border-stone-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <h4 class="text-2xl font-semibold text-slate-900">Day-by-day schedule</h4>
            <details class="relative">
                <summary class="flex cursor-pointer list-none items-center gap-2 rounded-2xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm shadow-slate-900/5 transition hover:border-stone-300 hover:text-slate-900">
                    <i class="fas fa-table-columns text-slate-400"></i>
                    Display columns
                    <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                </summary>
                <div class="absolute right-0 z-10 mt-3 w-72 rounded-3xl border border-stone-200 bg-white p-4 shadow-2xl shadow-slate-900/10">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Schedule view</p>
                    <p class="mt-2 text-sm text-slate-500">Choose how much date detail stays visible while you review your journey.</p>
                    <div class="mt-4 grid gap-3">
                        <label class="flex items-center gap-3 text-sm text-slate-700">
                            <input type="checkbox" data-column-toggle="date" class="rounded border-stone-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            Date
                        </label>
                        <label class="flex items-center gap-3 text-sm text-slate-700">
                            <input type="checkbox" data-column-toggle="completed-on" class="rounded border-stone-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            Completed on
                        </label>
                    </div>
                    <button type="button" data-table-columns-reset class="mt-4 inline-flex items-center rounded-2xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-slate-900">
                        Reset compact defaults
                    </button>
                </div>
            </details>
        </div>

        <div class="overflow-x-auto" data-table-columns-root>
            <table class="min-w-full divide-y divide-stone-200">
                <thead class="bg-stone-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Day</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500" data-column="date">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Reading</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500" data-column="completed-on">Completed On</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-200 bg-white">
                    @foreach($readingProgress as $progress)
                        <tr>
                            <td class="px-4 py-4 text-sm font-medium text-slate-900">
                                <div>
                                    <p>Day {{ $progress['day'] }}</p>
                                    <p class="mt-1 text-xs font-normal text-slate-500 md:hidden">{{ $progress['date'] }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-600" data-column="date">{{ $progress['date'] }}</td>
                            <td class="px-4 py-4 text-sm text-slate-600">
                                @if($progress['is_break_day'])
                                    <span class="italic text-stone-500">Refresh & Prayer Break</span>
                                @else
                                    {{ $progress['reading'] }}
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm">
                                @if($progress['is_future'])
                                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">Upcoming</span>
                                @elseif($progress['is_break_day'])
                                    <span class="inline-flex rounded-full bg-stone-200 px-3 py-1 text-xs font-semibold text-stone-700">Break Day</span>
                                @elseif($progress['completed'])
                                    <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Completed</span>
                                @else
                                    <span class="inline-flex rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">Missed</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm text-slate-600" data-column="completed-on">
                                @if($progress['completed'])
                                    {{ Carbon\Carbon::parse($progress['completed_date'])->format('M d, Y') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
