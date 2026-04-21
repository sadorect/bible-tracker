<div class="space-y-6">
    <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 lg:p-8">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Reading History</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Review what you completed, what you missed, and where you need to return.</h1>
                <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">Use search and filters to focus on completed days, missed readings, or specific passages from your current active plan.</p>
            </div>

            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-2xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-stone-50">
                &larr; Back to dashboard
            </a>
        </div>
    </section>

    <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label for="search" class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Search readings</label>
                <input
                    type="text"
                    id="search"
                    wire:model.live="searchTerm"
                    placeholder="Search by book name or reading range..."
                    class="mt-2 w-full rounded-2xl border-stone-200 bg-stone-50 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                >
            </div>

            <div>
                <label for="filter" class="block text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Status filter</label>
                <select
                    id="filter"
                    wire:model.live="filterCompleted"
                    class="mt-2 w-full rounded-2xl border-stone-200 bg-stone-50 px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                >
                    <option value="all">All readings</option>
                    <option value="completed">Completed only</option>
                    <option value="missed">Missed only</option>
                </select>
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(0,0.75fr)]">
        <div class="rounded-[2rem] bg-white shadow-xl shadow-slate-900/5">
            <div class="border-b border-stone-200 px-6 py-5">
                <h2 class="text-2xl font-semibold text-slate-900">
                    Your reading record
                    @if(count($readingHistory) > 0)
                        <span class="text-base font-normal text-slate-500">({{ count($readingHistory) }} entries)</span>
                    @endif
                </h2>
            </div>

            <div class="divide-y divide-stone-200">
                @forelse($readingHistory as $history)
                    <article class="px-6 py-5 transition hover:bg-stone-50">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex items-start gap-4">
                                <span class="flex h-11 w-11 items-center justify-center rounded-2xl {{ $history['completed'] ? 'bg-emerald-100 text-emerald-700' : ($history['is_break_day'] ? 'bg-stone-200 text-stone-700' : 'bg-rose-100 text-rose-700') }}">
                                    @if($history['completed'])
                                        <i class="fas fa-check"></i>
                                    @elseif($history['is_break_day'])
                                        <i class="fas fa-pause"></i>
                                    @else
                                        <i class="fas fa-xmark"></i>
                                    @endif
                                </span>
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-semibold text-slate-900">Day {{ $history['day'] }}</span>
                                        <span class="text-slate-300">•</span>
                                        <span class="text-sm text-slate-500">{{ $history['date'] }}</span>
                                        @if($history['is_break_day'])
                                            <span class="inline-flex rounded-full bg-stone-200 px-3 py-1 text-xs font-semibold text-stone-700">Break Day</span>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-lg font-semibold text-slate-900">
                                        {{ $history['is_break_day'] ? 'Rest and Reflection Day' : $history['reading'] }}
                                    </p>
                                    @if($history['completed'] && $history['completed_date'])
                                        <p class="mt-2 text-sm text-emerald-700">Completed on {{ $history['completed_date'] }}</p>
                                    @elseif(!$history['completed'] && !$history['is_break_day'])
                                        <p class="mt-2 text-sm text-rose-700">Not completed. It was due on {{ $history['date'] }}.</p>
                                    @endif
                                </div>
                            </div>

                            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $history['completed'] ? 'bg-emerald-100 text-emerald-700' : ($history['is_break_day'] ? 'bg-stone-200 text-stone-700' : 'bg-rose-100 text-rose-700') }}">
                                {{ $history['completed'] ? 'Completed' : ($history['is_break_day'] ? 'Break Day' : 'Missed') }}
                            </span>
                        </div>
                    </article>
                @empty
                    <div class="px-6 py-16 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-stone-100 text-slate-400">
                            <i class="fas fa-book-open"></i>
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-slate-900">No reading history found</h3>
                        <p class="mt-2 text-sm text-slate-500">
                            @if($searchTerm || $filterCompleted !== 'all')
                                No readings match your current filters.
                            @else
                                You have not started any readings yet.
                            @endif
                        </p>
                        @if($searchTerm || $filterCompleted !== 'all')
                            <div class="mt-6 flex flex-wrap justify-center gap-3">
                                <button wire:click="$set('searchTerm', '')" class="inline-flex items-center justify-center rounded-2xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-stone-50">
                                    Clear Search
                                </button>
                                <button wire:click="$set('filterCompleted', 'all')" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800">
                                    Show All
                                </button>
                            </div>
                        @endif
                    </div>
                @endforelse
            </div>
        </div>

        @if(count($readingHistory) > 0)
            @php
                $completedCount = collect($readingHistory)->where('completed', true)->count();
                $missedCount = collect($readingHistory)->where('completed', false)->where('is_break_day', false)->count();
                $totalReadingDays = collect($readingHistory)->where('is_break_day', false)->count();
                $rate = $totalReadingDays > 0 ? round(($completedCount / $totalReadingDays) * 100) : 0;
            @endphp
            <div class="grid gap-6">
                <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Summary</p>
                    <div class="mt-5 grid gap-3">
                        <div class="rounded-[1.35rem] bg-emerald-50 px-4 py-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-emerald-700">Completed</p>
                            <p class="mt-2 text-3xl font-semibold text-emerald-800">{{ $completedCount }}</p>
                        </div>
                        <div class="rounded-[1.35rem] bg-rose-50 px-4 py-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-rose-700">Missed</p>
                            <p class="mt-2 text-3xl font-semibold text-rose-800">{{ $missedCount }}</p>
                        </div>
                        <div class="rounded-[1.35rem] bg-sky-50 px-4 py-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-sky-700">Completion rate</p>
                            <p class="mt-2 text-3xl font-semibold text-sky-800">{{ $rate }}%</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </section>
</div>
