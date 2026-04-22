<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Structure</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Build and manage the leadership hierarchy.</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Total groups</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $stats['total'] }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Squads</p>
                <p class="mt-3 text-3xl font-semibold text-sky-700">{{ $stats['squads'] }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Platoons</p>
                <p class="mt-3 text-3xl font-semibold text-emerald-700">{{ $stats['platoons'] }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Batches</p>
                <p class="mt-3 text-3xl font-semibold text-amber-700">{{ $stats['batches'] }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Teams</p>
                <p class="mt-3 text-3xl font-semibold text-violet-700">{{ $stats['teams'] }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5 sm:col-span-2 xl:col-span-1">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Vacant groups</p>
                <p class="mt-3 text-3xl font-semibold text-amber-700">{{ $stats['vacant'] }}</p>
                <p class="mt-2 text-sm text-slate-500">Hierarchy slots currently waiting for a leader.</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.05fr)]">
            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Create hierarchy</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Add a new structure level</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Create squads, platoons, batches, and teams here, then assign the matching leader directly.</p>
                </div>

                <form method="POST" action="{{ route('admin.hierarchies.store') }}" class="mt-6 space-y-5">
                    @csrf

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Name</span>
                        <input type="text" name="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        @error('name')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Type</span>
                            <select name="type" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                                <option value="">Select type</option>
                                @foreach($typeLabels as $value => $label)
                                    <option value="{{ $value }}" {{ old('type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Leader</span>
                            <select name="leader_id" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                                <option value="">Select leader</option>
                                @foreach($leaders as $leader)
                                    <option value="{{ $leader->id }}" {{ (string) old('leader_id') === (string) $leader->id ? 'selected' : '' }}>
                                        {{ $leader->name }} · {{ $leader->roleLabel() }}
                                    </option>
                                @endforeach
                            </select>
                            @error('leader_id')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>
                    </div>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Parent group</span>
                        <select name="parent_id" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            <option value="">No parent</option>
                            @foreach($hierarchies as $hierarchy)
                                <option value="{{ $hierarchy->id }}" {{ (string) old('parent_id') === (string) $hierarchy->id ? 'selected' : '' }}>
                                    {{ $typeLabels[$hierarchy->type] ?? ucfirst($hierarchy->type) }} · {{ $displayPaths[$hierarchy->id] }}
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Create hierarchy
                    </button>
                </form>
            </div>

            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Recommended chain</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Leadership flow</h2>
                </div>

                <div class="mt-6 space-y-3">
                    @foreach($typeLabels as $value => $label)
                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $label }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $recommendedParents[$value] }}</p>
                                </div>
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600">
                                    {{ $expectedLeaderLabels[$value] ?? 'Leader' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        @if($teamBalanceInsights->isNotEmpty())
            <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Balancing watch</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Sibling teams that may need a rebalance</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">These batch branches have a noticeable spread in member counts across teams. Use the bulk distribution tool if you want to level them out.</p>
                    </div>
                    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-white">
                        Open user balancing tools
                    </a>
                </div>

                <div class="mt-6 grid gap-4 xl:grid-cols-2">
                    @foreach($teamBalanceInsights as $insight)
                        <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-5 py-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-slate-900">{{ $insight['batch']->name }}</h3>
                                    <p class="mt-1 text-sm text-slate-500">{{ $insight['batch']->displayPath() }}</p>
                                </div>
                                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                    Spread {{ $insight['spread'] }}
                                </span>
                            </div>

                            <div class="mt-5 space-y-3">
                                @foreach($insight['child_teams'] as $teamSummary)
                                    <div class="flex items-center justify-between rounded-[1.1rem] bg-white px-4 py-3 text-sm">
                                        <span class="font-medium text-slate-900">{{ $teamSummary['team']->name }}</span>
                                        <span class="text-slate-500">{{ $teamSummary['member_count'] }} member(s)</span>
                                    </div>
                                @endforeach
                            </div>

                            <p class="mt-4 text-sm text-slate-600">Suggested move target: about {{ $insight['suggested_moves'] }} member(s) from the heaviest team toward the lightest team.</p>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="space-y-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Existing structure</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Edit groups and leader assignments</h2>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                @forelse($hierarchies as $hierarchy)
                    <article class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-xl font-semibold text-slate-900">{{ $hierarchy->name }}</h3>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                                        {{ $typeLabels[$hierarchy->type] ?? ucfirst($hierarchy->type) }}
                                    </span>
                                </div>
                                <p class="mt-2 text-sm text-slate-500">{{ $displayPaths[$hierarchy->id] }}</p>
                            </div>
                            <div class="text-sm text-slate-500">
                                <p>{{ $hierarchy->children_count }} child group(s)</p>
                                <p>{{ $hierarchy->members_count }} assigned user(s)</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.hierarchies.update', $hierarchy) }}" class="mt-6 space-y-4">
                            @csrf
                            @method('PUT')

                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">Name</span>
                                <input type="text" name="name" value="{{ old('name', $hierarchy->name) }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            </label>

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="block">
                                    <span class="text-sm font-medium text-slate-700">Type</span>
                                    <input type="text" value="{{ $typeLabels[$hierarchy->type] ?? ucfirst($hierarchy->type) }}" disabled class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-100 px-4 py-3 text-sm text-slate-500 shadow-sm">
                                    <input type="hidden" name="type" value="{{ $hierarchy->type }}">
                                </label>

                                <label class="block">
                                    <span class="text-sm font-medium text-slate-700">Leader</span>
                                    <select name="leader_id" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                                        @foreach($leaders as $leader)
                                            <option value="{{ $leader->id }}" {{ (int) old('leader_id', $hierarchy->leader_id) === $leader->id ? 'selected' : '' }}>
                                                {{ $leader->name }} · {{ $leader->roleLabel() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>

                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">Parent group</span>
                                <select name="parent_id" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                                    <option value="">No parent</option>
                                    @foreach($hierarchies as $parentOption)
                                        @if($parentOption->id !== $hierarchy->id)
                                            <option value="{{ $parentOption->id }}" {{ (string) old('parent_id', $hierarchy->parent_id) === (string) $parentOption->id ? 'selected' : '' }}>
                                                {{ $typeLabels[$parentOption->type] ?? ucfirst($parentOption->type) }} · {{ $displayPaths[$parentOption->id] }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </label>

                            <div class="flex items-center justify-between gap-3">
                                <div class="text-xs text-slate-500">
                                    Current leader: {{ $hierarchy->leader?->name ?? 'None assigned' }}
                                </div>
                                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                    Save changes
                                </button>
                            </div>
                        </form>

                        <div class="mt-6 grid gap-4 border-t border-slate-200 pt-5 lg:grid-cols-2">
                            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Promotion helper</p>
                                <p class="mt-2 text-sm text-slate-600">Use this when the hierarchy is vacant and you want to promote someone straight into leadership.</p>
                                <form method="POST" action="{{ route('admin.hierarchies.promote-leader', $hierarchy) }}" class="mt-4 space-y-3">
                                    @csrf
                                    <label class="block">
                                        <span class="text-sm font-medium text-slate-700">Promote user</span>
                                        <select name="promote_user_id" class="mt-2 w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                            <option value="">Select user</option>
                                            @foreach(($promotableUsers[$hierarchy->id] ?? collect()) as $candidate)
                                                <option value="{{ $candidate->id }}">{{ $candidate->name }} · {{ $candidate->roleLabel() }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <button type="submit" class="inline-flex rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800" {{ $hierarchy->leader_id ? 'disabled' : '' }}>
                                        Promote into leadership
                                    </button>
                                </form>
                            </div>

                            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Demotion helper</p>
                                <p class="mt-2 text-sm text-slate-600">Release the current leader safely. Non-team leaders must land in a descendant team.</p>
                                <form method="POST" action="{{ route('admin.hierarchies.demote-leader', $hierarchy) }}" class="mt-4 space-y-3">
                                    @csrf
                                    @if($hierarchy->type !== 'team')
                                        <label class="block">
                                            <span class="text-sm font-medium text-slate-700">Destination team</span>
                                            <select name="demote_target_team_id" class="mt-2 w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                                <option value="">Select team</option>
                                                @foreach(($descendantTeams[$hierarchy->id] ?? collect()) as $team)
                                                    <option value="{{ $team->id }}">{{ $team->displayPath() }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                    @endif
                                    <button type="submit" class="inline-flex rounded-2xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-100" {{ $hierarchy->leader_id ? '' : 'disabled' }}>
                                        Demote current leader
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[2rem] border border-dashed border-slate-200 px-6 py-16 text-center text-sm text-slate-500 xl:col-span-2">
                        No hierarchy groups have been created yet.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-admin-layout>
