<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Vacancy Resolution</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ $hierarchy->name }}</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Affected hierarchy</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">{{ $hierarchy->displayPath() }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Use this one-step workflow to assign an existing matching leader or promote someone already inside the group.</p>
                </div>
                <a href="{{ route('admin.hierarchies.show', $hierarchy) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Open hierarchy detail
                </a>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Assign existing leader</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Fill the vacancy with a matching leader</h2>
                <form method="POST" action="{{ route('admin.hierarchies.resolve-vacancy.submit', $hierarchy) }}" class="mt-6 space-y-4">
                    @csrf
                    <input type="hidden" name="resolution" value="assign">

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Available {{ $typeLabels[$hierarchy->type] ?? ucfirst($hierarchy->type) }} leader</span>
                        <select name="leader_id" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            <option value="">Select leader</option>
                            @foreach($assignableLeaders as $leader)
                                <option value="{{ $leader->id }}">{{ $leader->name }} · {{ $leader->email }}</option>
                            @endforeach
                        </select>
                        @error('leader_id')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Assign leader
                    </button>
                </form>
            </div>

            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Promote from within</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Promote someone already in the group</h2>
                <form method="POST" action="{{ route('admin.hierarchies.resolve-vacancy.submit', $hierarchy) }}" class="mt-6 space-y-4">
                    @csrf
                    <input type="hidden" name="resolution" value="promote">

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Promote user</span>
                        <select name="promote_user_id" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            <option value="">Select person</option>
                            @foreach($promotableMembers as $candidate)
                                <option value="{{ $candidate->id }}">{{ $candidate->name }} · {{ $candidate->roleLabel() }}</option>
                            @endforeach
                        </select>
                        @error('promote_user_id')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Promote into leadership
                    </button>
                </form>
            </div>
        </section>
    </div>
</x-admin-layout>
