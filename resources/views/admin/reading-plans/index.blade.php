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
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Active</p>
                <p class="mt-3 text-3xl font-semibold text-emerald-700">{{ collect($readingPlans)->where('is_active', true)->count() }}</p>
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

        <section class="grid gap-4 lg:grid-cols-2">
            @forelse($readingPlans as $plan)
                @php
                    $statusClasses = $plan->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700';
                    $statusLabel = $plan->is_active ? 'Active' : 'Inactive';
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
