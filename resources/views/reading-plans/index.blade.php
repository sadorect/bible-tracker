<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Member Journey</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Reading Plans</h2>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-sky-700 px-6 py-8 text-white shadow-2xl shadow-slate-900/15 sm:px-8">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(16rem,0.65fr)]">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-sky-200">Choose Your Cohort</p>
                    <h3 class="mt-3 text-3xl font-semibold tracking-tight">Join the next faithful stretch of reading, training, and refresh.</h3>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-200">
                        Each plan follows the same ministry rhythm: training first, ten focused reading days, then a refresh-and-prayer break before the next cycle begins.
                    </p>
                </div>
                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/10 px-4 py-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-sky-100">Available</p>
                        <p class="mt-2 text-3xl font-semibold">{{ count($readingPlans) }}</p>
                    </div>
                    <div class="rounded-[1.5rem] border border-white/10 bg-white/10 px-4 py-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-sky-100">Stages</p>
                        <p class="mt-2 text-lg font-semibold">New then Old Testament</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-6 lg:grid-cols-2 2xl:grid-cols-3">
            @forelse($readingPlans as $plan)
                <article class="overflow-hidden rounded-[2rem] bg-white shadow-xl shadow-slate-900/5">
                    <div class="border-b border-stone-200 px-6 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">{{ $plan->type_label }}</p>
                                <h3 class="mt-2 text-2xl font-semibold text-slate-900">{{ $plan->name }}</h3>
                            </div>
                            @if($plan->user_has_joined)
                                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                    Enrolled
                                </span>
                            @elseif(!$plan->user_can_join)
                                <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                    Locked
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">
                                    Open
                                </span>
                            @endif
                        </div>
                        <p class="mt-3 text-sm leading-7 text-slate-600">{{ $plan->description }}</p>
                    </div>

                    <div class="space-y-5 px-6 py-6">
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div class="rounded-[1.35rem] bg-stone-50 px-4 py-3">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Cadence</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $plan->cadence_description }}</p>
                            </div>
                            <div class="rounded-[1.35rem] bg-stone-50 px-4 py-3">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Start date</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $plan->start_date?->format('M d, Y') ?? 'TBD' }}</p>
                            </div>
                            <div class="rounded-[1.35rem] bg-stone-50 px-4 py-3">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Scheduled days</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $plan->duration_days }}</p>
                            </div>
                            <div class="rounded-[1.35rem] bg-stone-50 px-4 py-3">
                                <p class="text-xs uppercase tracking-[0.2em] text-slate-500">Reading opens</p>
                                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $plan->reading_start_date?->format('M d, Y') ?? 'TBD' }}</p>
                            </div>
                        </div>

                        @if(!$plan->user_can_join && !$plan->user_has_joined)
                            <div class="rounded-[1.35rem] border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                                {{ $plan->locked_reason }}
                            </div>
                        @endif

                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('reading-plans.show', $plan->id) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:border-slate-300 hover:bg-stone-50">
                                View Plan
                            </a>

                            @if(!$plan->user_has_joined && $plan->user_can_join)
                                <form action="{{ route('reading-plans.join', $plan->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800">
                                        Join Plan
                                    </button>
                                </form>
                            @elseif($plan->user_has_joined)
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-emerald-700">
                                    Go to Dashboard
                                </a>
                            @else
                                <button disabled class="inline-flex items-center justify-center rounded-2xl bg-stone-200 px-4 py-2.5 text-sm font-medium text-stone-600">
                                    Locked
                                </button>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-[2rem] border border-dashed border-stone-300 bg-white px-6 py-16 text-center text-sm text-slate-500 lg:col-span-2 2xl:col-span-3">
                    No reading plans are available right now.
                </div>
            @endforelse
        </section>
    </div>
</x-app-layout>
