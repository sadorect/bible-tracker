<div class="grid gap-6 xl:grid-cols-[minmax(0,0.8fr)_minmax(18rem,0.35fr)]">
    <section class="space-y-6">
        <article class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">{{ $guide['category'] }}</p>
            <h2 class="mt-2 text-3xl font-semibold text-slate-900">{{ $guide['title'] }}</h2>
            <p class="mt-3 text-sm leading-6 text-slate-600">{{ $guide['summary'] }}</p>
        </article>

        <article class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Steps</p>
            <ol class="mt-4 space-y-3">
                @foreach($guide['steps'] as $index => $step)
                    <li class="flex gap-4 rounded-[1.25rem] bg-slate-50 px-4 py-4">
                        <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-emerald-600 text-sm font-semibold text-white">{{ $index + 1 }}</span>
                        <p class="text-sm leading-6 text-slate-700">{{ $step }}</p>
                    </li>
                @endforeach
            </ol>
        </article>

        @if($guide['links'] !== [])
            <article class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Quick Links</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    @foreach($guide['links'] as $link)
                        <a href="{{ $link['url'] }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </div>
            </article>
        @endif
    </section>

    <aside class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">More Guides</p>
        <div class="mt-4 space-y-3">
            @foreach($guides as $otherGuide)
                <a href="{{ route('manual.show', $otherGuide['slug']) }}" class="block rounded-[1.25rem] px-4 py-4 transition {{ $otherGuide['slug'] === $guide['slug'] ? 'bg-slate-900 text-white' : 'bg-slate-50 text-slate-700 hover:bg-slate-100' }}">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] {{ $otherGuide['slug'] === $guide['slug'] ? 'text-slate-300' : 'text-slate-400' }}">{{ $otherGuide['category'] }}</p>
                    <p class="mt-2 text-sm font-semibold">{{ $otherGuide['title'] }}</p>
                </a>
            @endforeach
        </div>
    </aside>
</div>
