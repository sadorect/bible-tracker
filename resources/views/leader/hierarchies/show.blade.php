@php
    $typeStyle = match($hierarchy->type) {
        'clan'    => ['badge' => 'bg-violet-100 text-violet-700 border border-violet-200', 'bar' => 'bg-violet-400', 'icon' => 'text-violet-600'],
        'squad'   => ['badge' => 'bg-sky-100 text-sky-700 border border-sky-200',          'bar' => 'bg-sky-400',    'icon' => 'text-sky-600'],
        'platoon' => ['badge' => 'bg-emerald-100 text-emerald-700 border border-emerald-200', 'bar' => 'bg-emerald-400', 'icon' => 'text-emerald-600'],
        'batch'   => ['badge' => 'bg-amber-100 text-amber-700 border border-amber-200',    'bar' => 'bg-amber-400',  'icon' => 'text-amber-600'],
        'team'    => ['badge' => 'bg-rose-100 text-rose-700 border border-rose-200',       'bar' => 'bg-rose-400',   'icon' => 'text-rose-600'],
        default   => ['badge' => 'bg-slate-100 text-slate-600 border border-slate-200',   'bar' => 'bg-slate-400',  'icon' => 'text-slate-600'],
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0">
                {{-- Breadcrumb --}}
                <nav class="flex flex-wrap items-center gap-1 text-xs text-slate-500">
                    <a href="{{ route('leader.hierarchies.tree') }}" class="transition hover:text-slate-800">My branch</a>
                    @foreach($breadcrumb as $ancestor)
                        <i class="fas fa-chevron-right text-[9px] text-slate-300"></i>
                        <a href="{{ $urlPathMap[$ancestor->id] ?? route('leader.hierarchies.tree') }}" class="transition hover:text-slate-800 truncate max-w-[100px] sm:max-w-none">{{ $ancestor->name }}</a>
                    @endforeach
                    <i class="fas fa-chevron-right text-[9px] text-slate-300"></i>
                    <span class="font-semibold text-slate-800 truncate max-w-[120px] sm:max-w-none">{{ $hierarchy->name }}</span>
                </nav>
                {{-- Title --}}
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <span class="rounded-full px-3 py-1 text-xs font-medium {{ $typeStyle['badge'] }}">
                        {{ $typeLabels[$hierarchy->type] ?? ucfirst($hierarchy->type) }}
                    </span>
                    <h1 class="text-xl font-semibold text-slate-900">{{ $hierarchy->name }}</h1>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('leader.hierarchies.tree') }}"
                   class="inline-flex items-center gap-2 rounded-2xl border border-stone-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-stone-50">
                    <i class="fas fa-sitemap text-slate-400"></i>
                    <span class="hidden sm:inline">Tree</span>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-5">

        {{-- Stats row --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            {{-- Leader card --}}
            <div class="col-span-2 rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Leader</p>
                @if($hierarchy->leader)
                    <div class="mt-3 flex items-center gap-3">
                        <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl {{ $typeStyle['bar'] }} text-white shadow-lg">
                            <i class="fas fa-user-tie text-sm"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-slate-900">{{ $hierarchy->leader->name }}</p>
                            <p class="truncate text-sm text-slate-500">{{ $hierarchy->leader->email }}</p>
                            <p class="text-xs text-slate-400">{{ $hierarchy->leader->roleLabel() }}</p>
                        </div>
                    </div>
                @else
                    <p class="mt-3 text-sm italic text-slate-400">No leader assigned</p>
                @endif
            </div>

            <div class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Direct</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $hierarchy->members->count() }}</p>
                <p class="mt-1 text-xs text-slate-400">Members</p>
            </div>

            <div class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Total</p>
                <p class="mt-3 text-3xl font-semibold {{ $typeStyle['icon'] }}">{{ $totalDescendantMembers }}</p>
                <p class="mt-1 text-xs text-slate-400">All sub-groups</p>
            </div>
        </div>

        {{-- Direct children --}}
        @if($children->isNotEmpty())
        <section>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">
                {{ $children->count() }} direct sub-group{{ $children->count() !== 1 ? 's' : '' }}
            </p>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($children as $child)
                    @php
                        $cs = match($child->type) {
                            'clan'    => ['badge' => 'bg-violet-100 text-violet-700 border border-violet-200', 'bar' => 'bg-violet-400'],
                            'squad'   => ['badge' => 'bg-sky-100 text-sky-700 border border-sky-200',          'bar' => 'bg-sky-400'],
                            'platoon' => ['badge' => 'bg-emerald-100 text-emerald-700 border border-emerald-200', 'bar' => 'bg-emerald-400'],
                            'batch'   => ['badge' => 'bg-amber-100 text-amber-700 border border-amber-200',    'bar' => 'bg-amber-400'],
                            'team'    => ['badge' => 'bg-rose-100 text-rose-700 border border-rose-200',       'bar' => 'bg-rose-400'],
                            default   => ['badge' => 'bg-slate-100 text-slate-600 border border-slate-200',   'bar' => 'bg-slate-400'],
                        };
                    @endphp
                    <a href="{{ $urlPathMap[$child->id] ?? route('leader.hierarchies.tree') }}"
                       class="group flex items-center gap-4 rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5 transition hover:shadow-2xl hover:-translate-y-0.5">
                        <span class="flex h-10 w-2 flex-shrink-0 rounded-full {{ $cs['bar'] }}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $cs['badge'] }}">
                                    {{ $typeLabels[$child->type] ?? ucfirst($child->type) }}
                                </span>
                            </div>
                            <p class="mt-1.5 truncate font-semibold text-slate-900 group-hover:text-slate-700">{{ $child->name }}</p>
                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                {{ $child->leader?->name ?? 'No leader' }}
                                <span class="mx-1 text-slate-300">·</span>
                                {{ $child->members_count }} member{{ $child->members_count !== 1 ? 's' : '' }}
                                @if($child->children_count)
                                    <span class="mx-1 text-slate-300">·</span>
                                    {{ $child->children_count }} sub-group{{ $child->children_count !== 1 ? 's' : '' }}
                                @endif
                            </p>
                        </div>
                        <i class="fas fa-chevron-right text-xs text-slate-300 transition group-hover:text-slate-500 group-hover:translate-x-0.5 flex-shrink-0"></i>
                    </a>
                @endforeach
            </div>
        </section>
        @endif

        {{-- Members table --}}
        @if($hierarchy->members->isNotEmpty())
        <section>
            <p class="mb-3 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">
                {{ $hierarchy->members->count() }} directly assigned member{{ $hierarchy->members->count() !== 1 ? 's' : '' }}
            </p>
            <div class="overflow-x-auto rounded-[2rem] bg-white shadow-xl shadow-slate-900/5">
                <table class="w-full min-w-[480px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Name</th>
                            <th class="hidden px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-400 sm:table-cell">Email</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Role</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($hierarchy->members as $member)
                            <tr class="transition hover:bg-slate-50/60">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xs font-semibold text-slate-600">
                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                        </span>
                                        <span class="font-medium text-slate-900">{{ $member->name }}</span>
                                    </div>
                                </td>
                                <td class="hidden px-5 py-3 text-slate-500 sm:table-cell">{{ $member->email }}</td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">
                                        {{ $member->roleLabel() }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        @endif

        @if($children->isEmpty() && $hierarchy->members->isEmpty())
            <div class="rounded-[2rem] border border-dashed border-stone-200 px-6 py-16 text-center text-sm text-slate-400">
                <i class="fas fa-inbox text-2xl text-slate-300"></i>
                <p class="mt-3">No sub-groups or members assigned to this group yet.</p>
            </div>
        @endif

    </div>
</x-app-layout>
