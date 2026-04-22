<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Workflow Review</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Guided horizontal migration</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] lg:items-center">
                <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Current location</p>
                    <h2 class="mt-3 text-xl font-semibold text-slate-900">{{ $preview['source']->displayPath() }}</h2>
                    <p class="mt-2 text-sm text-slate-500">Currently nested under {{ $preview['current_parent']->name }}.</p>
                </div>
                <div class="flex justify-center text-2xl text-emerald-600">
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="rounded-[1.5rem] border border-emerald-200 bg-emerald-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">Destination</p>
                    <h2 class="mt-3 text-xl font-semibold text-slate-900">{{ $preview['destination_parent']->displayPath() }} / {{ $preview['source']->name }}</h2>
                    <p class="mt-2 text-sm text-emerald-800">The branch will stay at the same level and carry its descendants with it.</p>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Direct members</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $preview['summary']['direct_members'] }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Branch members</p>
                <p class="mt-3 text-3xl font-semibold text-emerald-700">{{ $preview['summary']['branch_members'] }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Descendant groups</p>
                <p class="mt-3 text-3xl font-semibold text-sky-700">{{ $preview['summary']['descendant_groups'] }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Leaders impacted</p>
                <p class="mt-3 text-3xl font-semibold text-amber-700">{{ $preview['summary']['branch_leaders'] }}</p>
            </article>
        </section>

        <section class="rounded-[2rem] bg-white shadow-xl shadow-slate-900/5">
            <div class="border-b border-slate-200 px-6 py-5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Path review</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">What changes for each group</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                            <th class="px-6 py-4">Group</th>
                            <th class="px-6 py-4">Current path</th>
                            <th class="px-6 py-4">Future path</th>
                            <th class="px-6 py-4">Direct members</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($preview['branch_hierarchies'] as $branch)
                            <tr>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-slate-900">{{ $branch['hierarchy']->name }}</p>
                                    <p class="text-xs text-slate-500">{{ $branch['type_label'] }}</p>
                                </td>
                                <td class="px-6 py-4 text-slate-600">{{ $branch['current_path'] }}</td>
                                <td class="px-6 py-4 text-emerald-700">{{ $branch['future_path'] }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $branch['direct_members_count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Leaders</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Leadership impact</h2>
                <div class="mt-6 space-y-3">
                    @forelse($preview['impacted_leaders'] as $leaderImpact)
                        <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">{{ $leaderImpact['leader']->name }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $leaderImpact['hierarchy']->name }}</p>
                            <p class="mt-3 text-xs uppercase tracking-[0.2em] text-slate-400">Path change</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $leaderImpact['current_path'] }}</p>
                            <p class="mt-1 text-sm font-medium text-emerald-700">{{ $leaderImpact['future_path'] }}</p>
                        </div>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-200 px-5 py-10 text-center text-sm text-slate-500">
                            No branch leaders will be repositioned by this move.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Confirmation</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Confirm branch move</h2>
                <p class="mt-3 text-sm leading-6 text-slate-500">This updates the parent for the selected branch and keeps the existing vertical structure intact.</p>

                <form method="POST" action="{{ route('admin.hierarchies.migration.execute') }}" class="mt-6 space-y-4">
                    @csrf
                    <input type="hidden" name="source_hierarchy_id" value="{{ $preview['source']->id }}">
                    <input type="hidden" name="destination_parent_id" value="{{ $preview['destination_parent']->id }}">

                    <div class="rounded-[1.5rem] border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900">
                        {{ $preview['summary']['branch_members'] }} people and {{ $preview['summary']['descendant_groups'] }} descendant group(s) will move with {{ $preview['source']->name }}.
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            Confirm migration
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
