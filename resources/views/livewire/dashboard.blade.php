<div>
    @if($userPlan && $userPlan->pivot)
        @php
            $activeDay = (int) $userPlan->pivot->current_day;
            $completionRate = number_format($userPlan->pivot->completion_rate ?? 0, 0);
            $completedHistoryCount = collect($readingHistory)->where('completed', true)->count();
            $assignedDate = $viewingReading
                ? (($readingPlan->reading_start_date ?? $readingPlan->start_date)?->copy()->addDays($viewingReading->day_number - 1))
                : null;
        @endphp

        <div class="space-y-6">
            @if($viewingDay && $viewingDay !== $activeDay)
                <div class="rounded-[1.75rem] border border-sky-200 bg-sky-50 px-5 py-4 text-sm text-sky-900 shadow-sm shadow-sky-900/5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-100 text-sky-700">
                                <i class="fas fa-compass"></i>
                            </span>
                            <div>
                                <p class="font-semibold">You are viewing Day {{ $viewingDay }}.</p>
                                <p class="mt-1 text-sky-800/80">
                                    {{ $viewingDay < $activeDay ? 'This helps with catch-up on a missed assignment.' : 'This is a read-ahead view for faster progress.' }}
                                </p>
                            </div>
                        </div>
                        <button wire:click="resetToToday" class="inline-flex items-center justify-center rounded-2xl bg-sky-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-sky-700">
                            Return to Scheduled Day {{ $activeDay }}
                        </button>
                    </div>
                </div>
            @endif

            @if($isTrainingStage)
                <section class="overflow-hidden rounded-[2rem] border border-amber-200 bg-white shadow-xl shadow-slate-900/5">
                    <div class="grid gap-6 px-6 py-6 lg:grid-cols-[minmax(0,1.3fr)_minmax(16rem,0.7fr)] lg:px-8">
                        <div class="space-y-5">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-amber-800">Training Stage</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                    Day {{ max($trainingDayNumber, 1) }} of {{ max(count($trainingResources), 1) }}
                                </span>
                            </div>
                            <div>
                                <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Build the rhythm before the readings open.</h2>
                                <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-600">
                                    Every uploaded training resource extends the onboarding window. Complete each one, then your reading schedule opens on
                                    <span class="font-semibold text-slate-900">{{ $readingPlan->reading_start_date?->format('M d, Y') ?? 'the scheduled reading date' }}</span>.
                                </p>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-3">
                                <div class="rounded-[1.5rem] bg-stone-50 p-4">
                                    <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Training starts</p>
                                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ $readingPlan->start_date?->format('M d, Y') ?? 'TBD' }}</p>
                                </div>
                                <div class="rounded-[1.5rem] bg-stone-50 p-4">
                                    <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Reading opens</p>
                                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ $readingPlan->reading_start_date?->format('M d, Y') ?? 'After training' }}</p>
                                </div>
                                <div class="rounded-[1.5rem] bg-stone-50 p-4">
                                    <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Resources</p>
                                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ count($trainingResources) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[1.75rem] bg-gradient-to-br from-amber-500 to-orange-500 p-6 text-white shadow-lg shadow-amber-600/20">
                            <p class="text-xs uppercase tracking-[0.24em] text-amber-100">Progress</p>
                            <p class="mt-3 text-4xl font-semibold">{{ collect($trainingResources)->where('completed', true)->count() }}/{{ max(count($trainingResources), 1) }}</p>
                            <p class="mt-2 text-sm text-amber-50">Completed training resources</p>
                            @if($trainingComplete && ! $readingUnlocked)
                                <p class="mt-6 rounded-2xl bg-white/15 px-4 py-3 text-sm text-white/90">Training is complete. Your reading journey unlocks on the scheduled start date.</p>
                            @endif
                        </div>
                    </div>

                    <div class="border-t border-amber-100 bg-amber-50/50 px-6 py-6 lg:px-8">
                        <div class="grid gap-4 lg:grid-cols-2">
                            @forelse($trainingResources as $resource)
                                <article class="rounded-[1.5rem] border border-amber-100 bg-white p-5 shadow-sm shadow-amber-900/5">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="space-y-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-slate-700">Training Day {{ $resource['day_number'] }}</span>
                                                <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-medium text-sky-700">{{ $resource['type_label'] }}</span>
                                            </div>
                                            <h3 class="text-lg font-semibold text-slate-900">{{ $resource['title'] }}</h3>
                                            @if($resource['description'])
                                                <p class="text-sm leading-6 text-slate-600">{{ $resource['description'] }}</p>
                                            @endif
                                        </div>

                                        <div class="flex flex-col gap-2 sm:items-end">
                                            <div class="flex flex-wrap gap-2 sm:justify-end">
                                                @if($resource['video_link'])
                                                    <a href="{{ $resource['video_link'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-slate-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-slate-800">
                                                        Open Video
                                                    </a>
                                                @endif
                                                @if($resource['document_link'])
                                                    <a href="{{ $resource['document_link'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-stone-50">
                                                        Open PDF
                                                    </a>
                                                @endif
                                            </div>

                                            @if($resource['completed'])
                                                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                                    Completed{{ $resource['completed_at'] ? ' '.$resource['completed_at']->format('M d') : '' }}
                                                </span>
                                            @else
                                                <button wire:click="markTrainingResourceComplete({{ $resource['id'] }})" class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-amber-700">
                                                    Mark Complete
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-[1.5rem] border border-dashed border-amber-200 bg-white px-6 py-10 text-center text-sm text-slate-500 lg:col-span-2">
                                    No training resources have been added to this cohort yet.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </section>
            @else
                <section class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(19rem,0.8fr)]">
                    <article class="overflow-hidden rounded-[2rem] bg-white shadow-xl shadow-slate-900/5">
                        <div class="border-b border-stone-200 px-6 py-5 lg:px-8">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <div class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-sky-700">
                                        {{ $viewingDay && $viewingDay !== $activeDay ? 'Viewing Day '.$viewingDay : 'Today’s Assignment' }}
                                    </div>
                                    <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-900">
                                        @if($viewingReading && $viewingReading->is_break_day)
                                            Refresh & Prayer Break
                                        @elseif($viewingReading)
                                            {{ $viewingReading->reading_range }}
                                        @else
                                            No reading assigned
                                        @endif
                                    </h2>
                                    <p class="mt-2 text-sm leading-7 text-slate-600">
                                        @if($viewingReading && $viewingReading->is_break_day)
                                            This is your scheduled pause after the current reading streak. Reflect, pray, and prepare for the next stretch.
                                        @elseif($viewingReading)
                                            {{ $readingPlan->type_label }} plan. {{ $readingPlan->cadence_description }}.
                                        @else
                                            Please check back later for the next assignment.
                                        @endif
                                    </p>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-[1.5rem] bg-stone-50 px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Scheduled day</p>
                                        <p class="mt-2 text-2xl font-semibold text-slate-900">Day {{ $viewingDay ?? $activeDay }}</p>
                                    </div>
                                    <div class="rounded-[1.5rem] bg-stone-50 px-4 py-3">
                                        <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Assigned date</p>
                                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ $assignedDate?->format('M d, Y') ?? 'TBD' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6 px-6 py-6 lg:px-8">
                            @if($viewingReading && ! $viewingReading->is_break_day && $viewingChapters && count($viewingChapters) > 0)
                                <div>
                                    <div class="mb-3 flex items-center justify-between">
                                        <h3 class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">Chapters</h3>
                                        <span class="text-sm text-slate-500">{{ count($viewingChapters) }} assigned</span>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                        @foreach($viewingChapters as $chapter)
                                            @if(Route::has('bible.chapter'))
                                                <a href="{{ route('bible.chapter', [$chapter->book_name, $chapter->chapter_number]) }}" class="rounded-[1.35rem] border border-stone-200 bg-stone-50 px-4 py-3 text-sm font-medium text-slate-800 transition hover:border-sky-200 hover:bg-sky-50 hover:text-sky-800">
                                                    {{ $chapter->book_name }} {{ $chapter->chapter_number }}
                                                </a>
                                            @else
                                                <div class="rounded-[1.35rem] border border-stone-200 bg-stone-50 px-4 py-3 text-sm font-medium text-slate-800">
                                                    {{ $chapter->book_name }} {{ $chapter->chapter_number }}
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="flex flex-wrap gap-3">
                                @if($viewingDay && $viewingDay === $activeDay)
                                    @if($completedToday)
                                        <span class="inline-flex items-center rounded-2xl bg-emerald-100 px-5 py-3 text-sm font-semibold text-emerald-700">
                                            <i class="fas fa-check mr-2"></i>
                                            Today completed
                                        </span>
                                    @elseif($todayReading && ! $todayReading->is_break_day)
                                        <button wire:click="markAsComplete" class="inline-flex items-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                            Mark Today Complete
                                        </button>
                                    @endif
                                @elseif($viewingDay && $viewingDay < $activeDay)
                                    @if($viewingCompleted)
                                        <span class="inline-flex items-center rounded-2xl bg-emerald-100 px-5 py-3 text-sm font-semibold text-emerald-700">
                                            <i class="fas fa-check mr-2"></i>
                                            Catch-up complete
                                        </span>
                                    @elseif($viewingReading && ! $viewingReading->is_break_day)
                                        <button wire:click="markViewingDayAsComplete" class="inline-flex items-center rounded-2xl bg-amber-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-amber-700">
                                            Mark Catch-up Complete
                                        </button>
                                    @endif
                                @elseif($viewingDay && $viewingDay > $activeDay)
                                    @if($viewingCompleted)
                                        <span class="inline-flex items-center rounded-2xl bg-emerald-100 px-5 py-3 text-sm font-semibold text-emerald-700">
                                            <i class="fas fa-check mr-2"></i>
                                            Read-ahead complete
                                        </span>
                                    @elseif($viewingReading && ! $viewingReading->is_break_day)
                                        <button wire:click="markViewingDayAsComplete" class="inline-flex items-center rounded-2xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                            Mark Read-ahead Complete
                                        </button>
                                    @endif
                                @endif

                                @if($viewingDay && $viewingDay === $activeDay && $readingUnlocked)
                                    <button wire:click="showCatchUpOptions" class="inline-flex items-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-stone-50">
                                        <i class="fas fa-rotate-left mr-2 text-slate-500"></i>
                                        Catch up on missed days
                                    </button>
                                @endif
                            </div>
                        </div>
                    </article>

                    <aside class="space-y-6">
                        <div class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-emerald-600 to-teal-700 p-6 text-white shadow-xl shadow-emerald-900/10">
                            <p class="text-xs uppercase tracking-[0.24em] text-emerald-100">Journey Snapshot</p>
                            <div class="mt-4 grid gap-4 sm:grid-cols-3 xl:grid-cols-1">
                                <div>
                                    <p class="text-sm text-emerald-100">Current streak</p>
                                    <p class="mt-1 text-3xl font-semibold">{{ $userPlan->pivot->current_streak ?? 0 }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-emerald-100">Completion rate</p>
                                    <p class="mt-1 text-3xl font-semibold">{{ $completionRate }}%</p>
                                </div>
                                <div>
                                    <p class="text-sm text-emerald-100">Next break</p>
                                    <p class="mt-1 text-3xl font-semibold">{{ $nextBreakDays ?? 0 }}</p>
                                    <p class="text-xs text-emerald-50">days away</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Cycle</p>
                                    <h3 class="mt-2 text-xl font-semibold text-slate-900">Reading cadence</h3>
                                </div>
                                <span class="rounded-full bg-stone-100 px-3 py-1 text-xs font-medium text-slate-700">
                                    {{ $readingPlan->streak_days }} on / {{ $readingPlan->break_days }} off
                                </span>
                            </div>
                            <div class="mt-5 grid gap-3">
                                <div class="rounded-[1.35rem] bg-stone-50 px-4 py-3">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Reading start</p>
                                    <p class="mt-2 text-base font-semibold text-slate-900">{{ ($readingPlan->reading_start_date ?? $readingPlan->start_date)?->format('M d, Y') ?? 'TBD' }}</p>
                                </div>
                                <div class="rounded-[1.35rem] bg-stone-50 px-4 py-3">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Plan end</p>
                                    <p class="mt-2 text-base font-semibold text-slate-900">{{ $readingPlan->end_date?->format('M d, Y') ?? 'TBD' }}</p>
                                </div>
                                <div class="rounded-[1.35rem] bg-stone-50 px-4 py-3">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Recently completed</p>
                                    <p class="mt-2 text-base font-semibold text-slate-900">{{ $completedHistoryCount }} of last {{ count($readingHistory) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                            <h3 class="text-lg font-semibold text-slate-900">Quick mark completion</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Enter a single day or a range. Catch-up respects the current schedule, while day/range entry can include read-ahead progress.
                            </p>

                            <form action="{{ route('reading.quick-mark') }}" method="POST" class="mt-5 space-y-4">
                                @csrf
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="day_number" class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Day number</label>
                                        <input
                                            type="number"
                                            name="day_number"
                                            id="day_number"
                                            min="1"
                                            max="{{ $readingPlan->duration_days }}"
                                            class="mt-2 w-full rounded-2xl border-stone-200 bg-stone-50 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            placeholder="e.g. 18"
                                        >
                                    </div>
                                    <div>
                                        <label for="day_range" class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Day range</label>
                                        <input
                                            type="text"
                                            name="day_range"
                                            id="day_range"
                                            pattern="^\s*\d+\s*-\s*\d+\s*$"
                                            class="mt-2 w-full rounded-2xl border-stone-200 bg-stone-50 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                            placeholder="e.g. 18-22"
                                        >
                                    </div>
                                </div>

                                <label class="flex items-center gap-3 rounded-[1.35rem] border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-slate-700">
                                    <input type="checkbox" name="apply_catch_up" value="1" class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500">
                                    Mark all missed reading days up to the chosen day
                                </label>

                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                                    Update Reading Progress
                                </button>
                            </form>

                            <p class="mt-4 text-xs leading-6 text-slate-500">
                                Break days are skipped automatically and already completed days are ignored.
                            </p>
                        </div>
                    </aside>
                </section>

                <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 lg:p-8">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Navigate</p>
                            <h3 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Move across recent, current, and upcoming schedule days.</h3>
                        </div>
                        @if($viewingDay !== $activeDay)
                            <button wire:click="resetToToday" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-stone-50">
                                Back to today
                            </button>
                        @endif
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <div class="flex min-w-max gap-3 pb-2">
                            @foreach($nearbyDays as $day)
                                <button wire:click="viewDay({{ $day['day'] }})" class="w-28 flex-shrink-0 rounded-[1.5rem] border px-4 py-4 text-left transition {{ $day['is_current'] ? 'border-slate-900 bg-slate-900 text-white shadow-lg shadow-slate-900/10' : 'border-stone-200 bg-stone-50 text-slate-700 hover:border-stone-300 hover:bg-white' }}">
                                    <p class="text-[11px] uppercase tracking-[0.2em] {{ $day['is_current'] ? 'text-slate-300' : 'text-slate-500' }}">Day</p>
                                    <p class="mt-2 text-2xl font-semibold">{{ $day['day'] }}</p>
                                    <p class="mt-3 text-xs {{ $day['is_current'] ? 'text-slate-200' : 'text-slate-500' }}">
                                        @if($day['is_break_day'])
                                            Break day
                                        @elseif($day['completed'])
                                            Completed
                                        @elseif($day['is_today'])
                                            Scheduled today
                                        @else
                                            Pending
                                        @endif
                                    </p>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
                    <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Calendar</p>
                                <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ \Carbon\Carbon::create($calendarYear, $calendarMonth, 1)->format('F Y') }}</h3>
                            </div>
                            <div class="flex gap-2">
                                <button wire:click="previousMonth" class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-white">Prev</button>
                                <button wire:click="nextMonth" class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-white">Next</button>
                            </div>
                        </div>

                        <div class="mt-6 grid grid-cols-7 gap-2 text-center">
                            @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $label)
                                <div class="pb-2 text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">{{ $label }}</div>
                            @endforeach

                            @foreach($calendarDays as $day)
                                <div class="flex h-12 items-center justify-center rounded-2xl text-sm font-medium
                                    {{ !$day['is_current_month'] ? 'text-slate-300' : 'text-slate-700' }}
                                    {{ $day['is_break_day'] && !$day['is_future'] ? 'bg-stone-200' : '' }}
                                    {{ $day['is_completed'] ? 'bg-emerald-500 text-white' : '' }}
                                    {{ $day['is_missed'] ? 'bg-rose-100 text-rose-700' : '' }}
                                    {{ $day['is_today'] ? 'ring-2 ring-sky-500 ring-offset-2 ring-offset-white' : '' }}">
                                    {{ $day['day'] }}
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 flex flex-wrap gap-4 text-xs text-slate-500">
                            <div class="flex items-center gap-2">
                                <span class="h-3.5 w-3.5 rounded-full bg-emerald-500"></span>
                                Completed
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="h-3.5 w-3.5 rounded-full bg-rose-100"></span>
                                Missed
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="h-3.5 w-3.5 rounded-full bg-stone-200"></span>
                                Break day
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="h-3.5 w-3.5 rounded-full border-2 border-sky-500"></span>
                                Today
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-6">
                        <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Reading History</p>
                                    <h3 class="mt-2 text-xl font-semibold text-slate-900">Recent days</h3>
                                </div>
                                <a href="{{ route('reading-history') }}" class="text-sm font-medium text-emerald-700 transition hover:text-emerald-800">View full history</a>
                            </div>

                            <div class="mt-5 space-y-3">
                                @forelse($readingHistory as $history)
                                    <div class="rounded-[1.35rem] border border-stone-200 bg-stone-50 px-4 py-4">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900">Day {{ $history['day'] }} · {{ $history['date'] }}</p>
                                                <p class="mt-1 text-sm text-slate-600">{{ $history['reading'] }}</p>
                                            </div>
                                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $history['completed'] ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                                {{ $history['completed'] ? 'Completed' : 'Missed' }}
                                            </span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="rounded-[1.35rem] border border-dashed border-stone-200 px-4 py-8 text-center text-sm text-slate-500">
                                        No reading history yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Group Updates</p>
                                    <h3 class="mt-2 text-xl font-semibold text-slate-900">Community encouragement</h3>
                                </div>
                            </div>

                            <div class="mt-5 space-y-3">
                                @forelse($groupMessages as $message)
                                    <div class="rounded-[1.35rem] border border-stone-200 bg-stone-50 px-4 py-4">
                                        <p class="text-sm font-semibold text-slate-900">
                                            {{ $message->is_admin_message ? 'Admin Message' : $message->title }}
                                        </p>
                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $message->message }}</p>
                                        <p class="mt-3 text-xs text-slate-400">{{ $message->created_at->diffForHumans() }}</p>
                                    </div>
                                @empty
                                    <div class="rounded-[1.35rem] border border-dashed border-stone-200 px-4 py-8 text-center text-sm text-slate-500">
                                        No group updates yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>
            @endif
        </div>

        @if($showCatchUpModal)
            <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/50 px-4 py-10 backdrop-blur-sm" wire:click="closeCatchUpModal">
                <div class="mx-auto w-full max-w-3xl rounded-[2rem] bg-white shadow-2xl shadow-slate-900/20" wire:click.stop>
                    <div class="flex items-center justify-between border-b border-stone-200 px-6 py-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Catch Up</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">Missed readings waiting for completion</h3>
                        </div>
                        <button wire:click="closeCatchUpModal" class="rounded-2xl p-3 text-slate-400 transition hover:bg-stone-100 hover:text-slate-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="px-6 py-6">
                        @if($missedReadings && count($missedReadings) > 0)
                            <div class="max-h-[26rem] space-y-3 overflow-y-auto pr-1">
                                @foreach($missedReadings as $reading)
                                    <div class="rounded-[1.5rem] border px-4 py-4 {{ $selectedReading && $selectedReading->id == $reading->id ? 'border-sky-300 bg-sky-50' : 'border-stone-200 bg-stone-50' }}">
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900">Day {{ $reading->day_number }}</p>
                                                <p class="mt-1 text-sm text-slate-600">{{ $reading->reading_range }}</p>
                                                <p class="mt-2 text-xs text-slate-400">
                                                    Assigned {{ ($readingPlan->reading_start_date ?? $readingPlan->start_date)?->copy()->addDays($reading->day_number - 1)->format('M d, Y') }}
                                                </p>
                                            </div>
                                            <button wire:click="selectReading({{ $reading->id }})" class="inline-flex items-center justify-center rounded-2xl px-4 py-2 text-sm font-medium transition {{ $selectedReading && $selectedReading->id == $reading->id ? 'bg-sky-600 text-white hover:bg-sky-700' : 'border border-stone-200 bg-white text-slate-700 hover:bg-stone-100' }}">
                                                {{ $selectedReading && $selectedReading->id == $reading->id ? 'Selected' : 'Select' }}
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                                <button wire:click="closeCatchUpModal" class="inline-flex items-center justify-center rounded-2xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-stone-50">
                                    Cancel
                                </button>
                                <button wire:click="markPreviousAsComplete" {{ !$selectedReading ? 'disabled' : '' }} class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-stone-300">
                                    Mark Selected Day Complete
                                </button>
                            </div>
                        @else
                            <div class="rounded-[1.5rem] border border-dashed border-stone-200 px-4 py-10 text-center text-sm text-slate-500">
                                You have no missed readings to catch up on.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if (session()->has('message'))
            <div class="fixed right-4 top-4 z-50 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-lg shadow-emerald-900/10" role="alert">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('success'))
            <div class="fixed right-4 top-4 z-50 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-lg shadow-emerald-900/10" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="fixed right-4 top-4 z-50 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 shadow-lg shadow-rose-900/10" role="alert">
                {{ session('error') }}
            </div>
        @endif
    @else
        <div class="rounded-[2rem] border border-amber-200 bg-amber-50 px-6 py-8 text-amber-900 shadow-sm shadow-amber-900/5">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-700">
                        <i class="fas fa-circle-exclamation"></i>
                    </span>
                    <div>
                        <h3 class="text-lg font-semibold">Reading plan not found</h3>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-amber-800/85">
                            It looks like you do not have an active reading plan or the current plan data needs to be refreshed.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('reading-plans.index') }}" class="inline-flex items-center justify-center rounded-2xl bg-amber-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-amber-700">
                        Browse Reading Plans
                    </a>
                    <button wire:click="$refresh" class="inline-flex items-center justify-center rounded-2xl border border-amber-200 bg-white px-4 py-2.5 text-sm font-medium text-amber-800 transition hover:bg-amber-100">
                        Refresh
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
