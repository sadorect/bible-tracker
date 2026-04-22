<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Workflow Review</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">True sibling merge</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] lg:items-center">
                <div class="rounded-[1.5rem] border border-rose-200 bg-rose-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-700">Source group</p>
                    <h2 class="mt-3 text-xl font-semibold text-slate-900">{{ $preview['source']->displayPath() }}</h2>
                    <p class="mt-2 text-sm text-rose-900">This branch will be emptied and deleted after the merge is confirmed.</p>
                </div>
                <div class="flex justify-center text-2xl text-emerald-600">
                    <i class="fas fa-code-merge"></i>
                </div>
                <div class="rounded-[1.5rem] border border-emerald-200 bg-emerald-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Target group</p>
                    <h2 class="mt-3 text-xl font-semibold text-slate-900">{{ $preview['target']->displayPath() }}</h2>
                    <p class="mt-2 text-sm text-emerald-800">Members, child branches, and the retained leadership assignment will land here.</p>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Source direct members</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $preview['summary']['source_direct_members'] }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Source branch members</p>
                <p class="mt-3 text-3xl font-semibold text-emerald-700">{{ $preview['summary']['source_branch_members'] }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Source descendants</p>
                <p class="mt-3 text-3xl font-semibold text-sky-700">{{ $preview['summary']['source_descendant_groups'] }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Source leaders</p>
                <p class="mt-3 text-3xl font-semibold text-amber-700">{{ $preview['summary']['source_branch_leaders'] }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Merged branch members</p>
                <p class="mt-3 text-3xl font-semibold text-violet-700">{{ $preview['summary']['target_branch_members_after_merge'] }}</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-[2rem] bg-white shadow-xl shadow-slate-900/5">
                <div class="border-b border-slate-200 px-6 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Source branch</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">People moving with the merge</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($preview['source_users'] as $user)
                        <div class="px-6 py-4">
                            <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $user->roleLabel() }} · {{ $user->hierarchy?->displayPath() ?? 'Unassigned' }}</p>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center text-sm text-slate-500">
                            No direct users are currently attached to the source branch.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Confirmation</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Choose the merged leadership plan</h2>
                <p class="mt-3 text-sm leading-6 text-slate-500">Members and child branches move automatically. The final step is deciding who leads the surviving group and where any outgoing leaders should land.</p>

                <form method="POST" action="{{ route('admin.hierarchies.merge.execute') }}" class="mt-6 space-y-5">
                    @csrf
                    <input type="hidden" name="source_hierarchy_id" value="{{ $preview['source']->id }}">
                    <input type="hidden" name="target_hierarchy_id" value="{{ $preview['target']->id }}">

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Merged leader</span>
                        <select name="merged_leader_id" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            @foreach($preview['leader_options'] as $option)
                                <option value="{{ $option['id'] ?? '' }}" {{ (string) old('merged_leader_id', $preview['default_merged_leader_id']) === (string) ($option['id'] ?? '') ? 'selected' : '' }}>
                                    {{ $option['label'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('merged_leader_id')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    @if($preview['source']->leader)
                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">If {{ $preview['source']->leader->name }} is not retained</p>
                            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                <label class="block">
                                    <span class="text-sm font-medium text-slate-700">Disposition</span>
                                    <select name="source_leader_disposition" class="mt-2 w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        <option value="unassign" {{ old('source_leader_disposition') === 'unassign' ? 'selected' : '' }}>Unassign from the branch</option>
                                        @if($preview['destination_teams']->isNotEmpty())
                                            <option value="descendant_team" {{ old('source_leader_disposition') === 'descendant_team' ? 'selected' : '' }}>Demote into a team in the merged branch</option>
                                        @endif
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="text-sm font-medium text-slate-700">Destination team</span>
                                    <select name="source_leader_team_id" class="mt-2 w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        <option value="">No team selected</option>
                                        @foreach($preview['destination_teams'] as $team)
                                            <option value="{{ $team->id }}" {{ (string) old('source_leader_team_id') === (string) $team->id ? 'selected' : '' }}>
                                                {{ $team->displayPath() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                        </div>
                    @endif

                    @if($preview['target']->leader)
                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">If {{ $preview['target']->leader->name }} is not retained</p>
                            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                <label class="block">
                                    <span class="text-sm font-medium text-slate-700">Disposition</span>
                                    <select name="target_leader_disposition" class="mt-2 w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        <option value="unassign" {{ old('target_leader_disposition') === 'unassign' ? 'selected' : '' }}>Unassign from the branch</option>
                                        @if($preview['destination_teams']->isNotEmpty())
                                            <option value="descendant_team" {{ old('target_leader_disposition') === 'descendant_team' ? 'selected' : '' }}>Demote into a team in the merged branch</option>
                                        @endif
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="text-sm font-medium text-slate-700">Destination team</span>
                                    <select name="target_leader_team_id" class="mt-2 w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        <option value="">No team selected</option>
                                        @foreach($preview['destination_teams'] as $team)
                                            <option value="{{ $team->id }}" {{ (string) old('target_leader_team_id') === (string) $team->id ? 'selected' : '' }}>
                                                {{ $team->displayPath() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                        </div>
                    @endif

                    <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
                        {{ $preview['source']->name }} will be deleted after the members and descendants move into {{ $preview['target']->name }}.
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            Confirm merge
                        </button>
                        <a href="{{ route('admin.hierarchies.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Back to hierarchy manager
                        </a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</x-admin-layout>
