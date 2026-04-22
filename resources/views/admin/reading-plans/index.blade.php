<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Cohort Ops</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Manage reading cohorts and schedule windows.</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-700 text-white shadow-2xl shadow-slate-900/15">
            <div class="grid gap-6 px-6 py-8 sm:px-8 lg:grid-cols-[minmax(0,1.2fr)_minmax(18rem,0.8fr)] lg:px-10">
                <div>
                    <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-emerald-100">
                        Reading plan management
                    </span>
                    <h2 class="mt-4 text-3xl font-semibold">Coordinate New Testament and Old Testament cohorts with flexible cadence settings.</h2>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-200">Each plan can carry training resources, a scheduled commencement date, and the refresh break cadence readers will follow throughout the journey.</p>
                </div>
                <div class="flex items-end">
                    <a href="{{ route('admin.reading-plans.create') }}" class="inline-flex w-full items-center justify-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-100">
                        Create new plan
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Published plans</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ count($readingPlans) }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Live</p>
                <p class="mt-3 text-3xl font-semibold text-emerald-700">{{ collect($readingPlans)->whereIn('lifecycle_status', \App\Models\ReadingPlan::liveStatuses())->count() }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">New Testament</p>
                <p class="mt-3 text-3xl font-semibold text-sky-700">{{ collect($readingPlans)->where('type', \App\Models\ReadingPlan::TYPE_NEW_TESTAMENT)->count() }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Old Testament</p>
                <p class="mt-3 text-3xl font-semibold text-amber-700">{{ collect($readingPlans)->where('type', \App\Models\ReadingPlan::TYPE_OLD_TESTAMENT)->count() }}</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
            <article class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Live plan limits</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Control concurrent cohorts</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">Leave any field blank if you want that limit to be unlimited.</p>

                <form method="POST" action="{{ route('admin.reading-plans.settings.update') }}" class="mt-6 grid gap-5 md:grid-cols-3">
                    @csrf
                    @method('PUT')

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Live NT plans</span>
                        <input type="number" name="max_live_new_testament" min="0" max="100" value="{{ old('max_live_new_testament', $lifecycleSettings['max_live_new_testament']) }}" placeholder="Unlimited" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Live OT plans</span>
                        <input type="number" name="max_live_old_testament" min="0" max="100" value="{{ old('max_live_old_testament', $lifecycleSettings['max_live_old_testament']) }}" placeholder="Unlimited" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Total live plans</span>
                        <input type="number" name="max_live_total" min="0" max="100" value="{{ old('max_live_total', $lifecycleSettings['max_live_total']) }}" placeholder="Unlimited" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                    </label>

                    <div class="md:col-span-3 flex justify-end">
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Save lifecycle settings
                        </button>
                    </div>
                </form>
            </article>

            <article class="rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-sky-700 p-6 text-white shadow-2xl shadow-slate-900/15">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-100">Lifecycle guide</p>
                <div class="mt-4 space-y-3 text-sm leading-6 text-slate-200">
                    <p><strong class="text-white">Draft</strong> keeps a plan hidden while you prepare cadence, training, and schedule details.</p>
                    <p><strong class="text-white">Recruiting</strong> makes a future cohort visible and joinable while enrollment is open.</p>
                    <p><strong class="text-white">Active</strong> keeps an underway cohort visible, and it can still accept joiners if its enrollment window is open.</p>
                    <p><strong class="text-white">Closed</strong> and <strong class="text-white">Archived</strong> remove the cohort from public discovery while preserving history.</p>
                </div>
            </article>
        </section>

        <section class="grid gap-4 lg:grid-cols-2">
            @forelse($readingPlans as $plan)
                @php
                    $statusClasses = match($plan->lifecycle_status) {
                        \App\Models\ReadingPlan::STATUS_ACTIVE => 'bg-emerald-100 text-emerald-700',
                        \App\Models\ReadingPlan::STATUS_RECRUITING => 'bg-sky-100 text-sky-700',
                        \App\Models\ReadingPlan::STATUS_CLOSED => 'bg-amber-100 text-amber-700',
                        \App\Models\ReadingPlan::STATUS_ARCHIVED => 'bg-slate-200 text-slate-700',
                        default => 'bg-slate-100 text-slate-700',
                    };
                    $statusLabel = $plan->lifecycle_status_label;
                @endphp
                <article class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-2xl font-semibold text-slate-900">{{ $plan->name }}</h2>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-slate-500">{{ $plan->description ?: 'No description yet.' }}</p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">{{ $plan->type_label }}</span>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-[1.35rem] bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Cadence</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ $plan->cadence_description }}</p>
                        </div>
                        <div class="rounded-[1.35rem] bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Start</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ optional($plan->start_date)->format('M d, Y') ?? 'TBD' }}</p>
                        </div>
                        <div class="rounded-[1.35rem] bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Reading opens</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ $plan->reading_start_date?->format('M d, Y') ?? 'TBD' }}</p>
                        </div>
                        <div class="rounded-[1.35rem] bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Enrollment</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">
                                {{ $plan->acceptsEnrollment() ? 'Open now' : 'Closed now' }}
                            </p>
                        </div>
                        <div class="rounded-[1.35rem] bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Participants</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ $plan->users()->count() }}</p>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('admin.reading-plans.edit', $plan) }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Edit plan
                        </a>
                        <form action="{{ route('admin.reading-plans.destroy', $plan) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this reading plan? This will remove all associated readings and progress.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-rose-100 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-200">
                                Delete
                            </button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-[2rem] border border-dashed border-slate-200 px-6 py-16 text-center text-sm text-slate-500 lg:col-span-2">
                    No reading plans have been created yet.
                </div>
            @endforelse
        </section>
    </div>
</x-admin-layout>
