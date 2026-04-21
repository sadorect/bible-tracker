@php
    $typeStyle = match($node->type) {
        'clan'    => ['badge' => 'bg-violet-100 text-violet-700 border border-violet-200', 'bar' => 'bg-violet-400', 'connector' => 'bg-violet-200', 'cardBorder' => 'border-violet-200'],
        'squad'   => ['badge' => 'bg-sky-100 text-sky-700 border border-sky-200',          'bar' => 'bg-sky-400',    'connector' => 'bg-sky-200',    'cardBorder' => 'border-sky-200'],
        'platoon' => ['badge' => 'bg-emerald-100 text-emerald-700 border border-emerald-200', 'bar' => 'bg-emerald-400', 'connector' => 'bg-emerald-200', 'cardBorder' => 'border-emerald-200'],
        'batch'   => ['badge' => 'bg-amber-100 text-amber-700 border border-amber-200',    'bar' => 'bg-amber-400',  'connector' => 'bg-amber-200',  'cardBorder' => 'border-amber-200'],
        'team'    => ['badge' => 'bg-rose-100 text-rose-700 border border-rose-200',       'bar' => 'bg-rose-400',   'connector' => 'bg-rose-200',   'cardBorder' => 'border-rose-200'],
        default   => ['badge' => 'bg-slate-100 text-slate-600 border border-slate-200',   'bar' => 'bg-slate-400',  'connector' => 'bg-slate-200',  'cardBorder' => 'border-slate-200'],
    };

    // Depth-based card coloration: gets progressively more tinted as you go deeper
    $depthCard = [
        0 => ['bg' => 'bg-white',     'shadow' => 'shadow-lg shadow-slate-900/5'],
        1 => ['bg' => 'bg-slate-50',  'shadow' => 'shadow-md shadow-slate-900/5'],
        2 => ['bg' => 'bg-slate-100', 'shadow' => 'shadow-sm shadow-slate-900/5'],
        3 => ['bg' => 'bg-slate-100', 'shadow' => 'shadow-sm shadow-slate-900/3'],
        4 => ['bg' => 'bg-gray-100',  'shadow' => 'shadow-sm'],
    ];
    $card        = $depthCard[min($depth, 4)];
    $hasChildren = $node->children->isNotEmpty();
    $autoOpen    = $depth < 2;
    $routePrefix = $routePrefix ?? 'admin.hierarchies';
    $nodePath    = $nodePath ?? $node->type;
    $showUrl     = $routePrefix === 'leader.hierarchies'
        ? url('/my-hierarchy/' . $nodePath . '/' . $node->id)
        : route($routePrefix . '.show', $node);
@endphp

<div
    x-data="{ open: {{ $autoOpen ? 'true' : 'false' }} }"
    @hierarchy-tree-toggle.window="if ($event.detail.type === '{{ $node->type }}' || $event.detail.type === 'all') open = $event.detail.expanded"
    class="{{ $depth > 0 ? 'mt-2' : '' }}"
>
    {{-- Node card --}}
    <div class="flex items-stretch gap-0">

        {{-- Indent connector lines for nested nodes (colored by this node's type) --}}
        @if($depth > 0)
            <div class="flex w-6 flex-shrink-0 items-stretch">
                <div class="mx-auto w-0.5 {{ $typeStyle['connector'] }}"></div>
            </div>
            <div class="flex w-5 flex-shrink-0 items-center">
                <div class="h-0.5 w-full {{ $typeStyle['connector'] }}"></div>
            </div>
        @endif

        {{-- Card: background and border tint varies with depth --}}
        <div class="flex flex-1 items-center gap-3 rounded-2xl border {{ $typeStyle['cardBorder'] }} {{ $card['bg'] }} px-4 py-3 {{ $card['shadow'] }} transition hover:brightness-95">

            {{-- Expand / collapse toggle --}}
            @if($hasChildren)
                <button
                    @click="open = !open"
                    class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                    :title="open ? 'Collapse' : 'Expand'"
                >
                    <i class="fas fa-chevron-right text-xs transition-transform duration-200" :class="open ? 'rotate-90' : ''"></i>
                </button>
            @else
                {{-- Leaf indicator --}}
                <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center">
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-300"></span>
                </span>
            @endif

            {{-- Type bar accent — full-height stripe --}}
            <span class="self-stretch w-1 flex-shrink-0 rounded-full {{ $typeStyle['bar'] }}"></span>

            {{-- Info --}}
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $typeStyle['badge'] }}">
                        {{ $typeLabels[$node->type] ?? ucfirst($node->type) }}
                    </span>
                    <span class="font-semibold text-slate-900">{{ $node->name }}</span>
                </div>
                <p class="mt-0.5 truncate text-xs text-slate-500">
                    <i class="fas fa-user-tie mr-1 text-[10px]"></i>{{ $node->leader?->name ?? 'No leader assigned' }}
                    <span class="mx-1.5 text-slate-300">·</span>
                    <i class="fas fa-users mr-1 text-[10px]"></i>{{ $node->members_count }} member{{ $node->members_count !== 1 ? 's' : '' }}
                    @if($hasChildren)
                        <span class="mx-1.5 text-slate-300">·</span>
                        <i class="fas fa-diagram-project mr-1 text-[10px]"></i>{{ $node->children->count() }} sub-group{{ $node->children->count() !== 1 ? 's' : '' }}
                    @endif
                </p>
            </div>

            {{-- Detail link --}}
            <a
                href="{{ $showUrl }}"
                class="flex-shrink-0 rounded-xl border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                title="View details for {{ $node->name }}"
            >
                <i class="fas fa-arrow-up-right-from-square text-[10px] sm:mr-1"></i><span class="hidden sm:inline">Details</span>
            </a>
        </div>
    </div>

    {{-- Children — fixed 28 px indentation per level for consistent spacing --}}
    @if($hasChildren)
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="sm:pl-7"
        >
            @foreach($node->children as $child)
                @include('admin.hierarchies._node', [
                    'node'        => $child,
                    'depth'       => $depth + 1,
                    'nodePath'    => $nodePath . '/' . $child->type,
                    'typeLabels'  => $typeLabels,
                    'routePrefix' => $routePrefix,
                ])
            @endforeach
        </div>
    @endif
</div>
