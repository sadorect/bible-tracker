<x-admin-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Structure · {{ $totalCount }} groups</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">Hierarchy tree</h1>
            </div>
            <a href="{{ route('admin.hierarchies.index') }}"
               class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm shadow-slate-900/5 transition hover:bg-slate-50">
                <i class="fas fa-sliders text-slate-400"></i>
                Manage hierarchies
            </a>
        </div>
    </x-slot>

    {{-- Expand/collapse control toolbar --}}
    <div class="mb-5 overflow-x-auto rounded-[1.75rem] border border-slate-200 bg-white px-4 py-3 shadow-sm shadow-slate-900/5 sm:px-5 sm:py-4">
        <div class="flex min-w-max flex-wrap items-center gap-2 sm:gap-3">

            {{-- Global controls --}}
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 mr-1">All</span>
                <button
                    @click="$dispatch('hierarchy-tree-toggle', { type: 'all', expanded: true })"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-100"
                    title="Expand all levels"
                >
                    <i class="fas fa-plus text-[10px]"></i> Expand all
                </button>
                <button
                    @click="$dispatch('hierarchy-tree-toggle', { type: 'all', expanded: false })"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-100"
                    title="Collapse all levels"
                >
                    <i class="fas fa-minus text-[10px]"></i> Collapse all
                </button>
            </div>

            <div class="h-5 w-px bg-slate-200 hidden sm:block"></div>

            {{-- Per-level controls --}}
            @php
                $levelStyles = [
                    'clan'    => 'border-violet-200 bg-violet-100 text-violet-700',
                    'squad'   => 'border-sky-200 bg-sky-100 text-sky-700',
                    'platoon' => 'border-emerald-200 bg-emerald-100 text-emerald-700',
                    'batch'   => 'border-amber-200 bg-amber-100 text-amber-700',
                    'team'    => 'border-rose-200 bg-rose-100 text-rose-700',
                ];
            @endphp
            @foreach($typeLabels as $typeKey => $typeLabel)
                <div class="flex items-center gap-1">
                    <span class="rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $levelStyles[$typeKey] ?? 'border-slate-200 bg-slate-100 text-slate-600' }}">
                        {{ $typeLabel }}
                    </span>
                    <button
                        @click="$dispatch('hierarchy-tree-toggle', { type: '{{ $typeKey }}', expanded: true })"
                        class="flex h-6 w-6 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-500 transition hover:bg-slate-100"
                        title="Expand all {{ $typeLabel }} nodes"
                    ><i class="fas fa-plus text-[9px]"></i></button>
                    <button
                        @click="$dispatch('hierarchy-tree-toggle', { type: '{{ $typeKey }}', expanded: false })"
                        class="flex h-6 w-6 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-500 transition hover:bg-slate-100"
                        title="Collapse all {{ $typeLabel }} nodes"
                    ><i class="fas fa-minus text-[9px]"></i></button>
                </div>
            @endforeach

            <span class="ml-auto hidden text-xs text-slate-400 xl:block">
                <i class="fas fa-chevron-right text-[10px]"></i> click a node to expand/collapse it
            </span>
        </div>
    </div>

    <div class="space-y-2">
        @forelse($roots as $root)
            @include('admin.hierarchies._node', [
                'node'        => $root,
                'depth'       => 0,
                'typeLabels'  => $typeLabels,
                'routePrefix' => 'admin.hierarchies',
            ])
        @empty
            <div class="rounded-[2rem] border border-dashed border-slate-200 px-6 py-20 text-center">
                <i class="fas fa-sitemap text-3xl text-slate-300"></i>
                <p class="mt-4 text-sm font-medium text-slate-500">No hierarchy groups have been created yet.</p>
                <a href="{{ route('admin.hierarchies.index') }}"
                   class="mt-4 inline-flex items-center gap-2 rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                    <i class="fas fa-plus"></i> Create the first group
                </a>
            </div>
        @endforelse
    </div>
</x-admin-layout>
