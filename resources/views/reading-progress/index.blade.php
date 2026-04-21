<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Progress Center</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Reading Progress</h2>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 lg:p-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Current assignment</p>
                    @if(!$readingUnlocked && $trainingResources->isNotEmpty())
                        <h3 class="mt-2 text-3xl font-semibold text-slate-900">Training is still in progress.</h3>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">
                            Complete all training resources before you begin reporting reading progress. Reading opens on
                            <span class="font-semibold text-slate-900">{{ $readingPlan->reading_start_date?->format('M d, Y') ?? 'the scheduled reading start date' }}</span>.
                        </p>
                    @elseif($todayReading->is_break_day)
                        <h3 class="mt-2 text-3xl font-semibold text-slate-900">Refresh & Prayer Break</h3>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-600">This day is reserved for reflection, prayer, and recovery before the next reading stretch begins.</p>
                    @else
                        <h3 class="mt-2 text-3xl font-semibold text-slate-900">{{ $todayReading->reading_range }}</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">{{ $readingPlan->type_label }} · {{ $readingPlan->cadence_description }}</p>
                    @endif
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:w-[22rem] lg:grid-cols-1">
                    <div class="rounded-[1.35rem] bg-stone-50 px-4 py-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Active cohort</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ $readingPlan->name }}</p>
                    </div>
                    <div class="rounded-[1.35rem] bg-stone-50 px-4 py-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Reading opens</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ $readingPlan->reading_start_date?->format('M d, Y') ?? 'TBD' }}</p>
                    </div>
                </div>
            </div>

            @if(!$readingUnlocked && $trainingResources->isNotEmpty())
                <div class="mt-6 grid gap-4 lg:grid-cols-2">
                    @foreach($trainingResources as $resource)
                        <article class="rounded-[1.5rem] border border-amber-200 bg-amber-50 p-5">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h4 class="text-lg font-semibold text-slate-900">{{ $resource->title }}</h4>
                                    @if($resource->description)
                                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $resource->description }}</p>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    @if($resource->video_link)
                                        <a href="{{ $resource->video_link }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800">
                                            Open Video
                                        </a>
                                    @endif
                                    @if($resource->document_link)
                                        <a href="{{ $resource->document_link }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-stone-50">
                                            Open PDF
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            @forelse($readingPlans as $plan)
                <article class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">{{ $plan->type_label }}</p>
                            <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $plan->name }}</h3>
                        </div>
                        @if($plan->pivot->is_active)
                            <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">Active</span>
                        @endif
                    </div>

                    <div class="mt-6">
                        <div class="mb-2 flex items-center justify-between text-sm text-slate-500">
                            <span>Completion</span>
                            <span>{{ number_format($plan->pivot->completion_rate, 0) }}%</span>
                        </div>
                        <div class="h-3 rounded-full bg-stone-200">
                            <div class="h-3 rounded-full bg-emerald-600" style="width: {{ $plan->pivot->completion_rate }}%"></div>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-[1.35rem] bg-stone-50 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Current day</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ $plan->pivot->current_day }} / {{ $plan->duration_days }}</p>
                        </div>
                        <div class="rounded-[1.35rem] bg-stone-50 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Current streak</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ $plan->pivot->current_streak }} days</p>
                        </div>
                        <div class="rounded-[1.35rem] bg-stone-50 px-4 py-3 sm:col-span-2">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Joined</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ \Carbon\Carbon::parse($plan->pivot->joined_date)->format('M d, Y') }}</p>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('progress.view', ['plan_id' => $plan->id]) }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800">
                            View Details
                        </a>
                        <a href="{{ route('reading-plans.show', $plan->id) }}" class="inline-flex items-center justify-center rounded-2xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-stone-50">
                            Open Plan
                        </a>
                    </div>
                </article>
            @empty
                <div class="rounded-[2rem] border border-dashed border-stone-300 bg-white px-6 py-14 text-center lg:col-span-2">
                    <p class="text-sm text-slate-500">You have not joined any reading plans yet.</p>
                    <a href="{{ route('reading-plans.index') }}" class="mt-4 inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800">
                        Browse Reading Plans
                    </a>
                </div>
            @endforelse
        </section>
    </div>
</x-app-layout>
