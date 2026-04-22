<div class="space-y-6">
    <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">User Guides</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Choose a task</h2>
                <p class="mt-2 text-sm text-slate-600">Open the guide that matches what you want to do.</p>
            </div>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach($guides as $guide)
            <article class="rounded-[1.75rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">{{ $guide['category'] }}</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">{{ $guide['title'] }}</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ $guide['summary'] }}</p>
                <a href="{{ route('manual.show', $guide['slug']) }}" class="mt-5 inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Open guide
                </a>
            </article>
        @endforeach
    </section>
</div>
