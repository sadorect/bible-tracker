<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Leader Console</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Manage Team</h2>
        </div>
    </x-slot>

    <div class="space-y-6">
            @if(!$leadHierarchy)
                <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <h3 class="text-lg font-semibold text-slate-900">No Leadership Scope Found</h3>
                    <p class="mt-2 text-sm text-slate-600">Your account has leader permissions, but it is not attached to a hierarchy yet.</p>
                </div>
            @else
                <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-medium text-emerald-600">{{ strtoupper($leadHierarchy->type) }} LEADER VIEW</p>
                            <h3 class="mt-1 text-2xl font-semibold text-slate-900">{{ $leadHierarchy->name }}</h3>
                            <p class="mt-2 text-sm text-slate-600">
                                Monitoring {{ $summary['total_monitored'] }} people across
                                {{ $scopeHierarchies->count() }} hierarchy level(s).
                            </p>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                            <div class="rounded-lg bg-amber-50 px-4 py-3">
                                <p class="text-amber-700 font-medium">In Training</p>
                                <p class="mt-1 text-2xl font-semibold text-amber-900">{{ $summary['in_training'] }}</p>
                            </div>
                            <div class="rounded-lg bg-rose-50 px-4 py-3">
                                <p class="text-rose-700 font-medium">Catching Up</p>
                                <p class="mt-1 text-2xl font-semibold text-rose-900">{{ $summary['catching_up'] }}</p>
                            </div>
                            <div class="rounded-lg bg-indigo-50 px-4 py-3">
                                <p class="text-indigo-700 font-medium">Reading Ahead</p>
                                <p class="mt-1 text-2xl font-semibold text-indigo-900">{{ $summary['reading_ahead'] }}</p>
                            </div>
                            <div class="rounded-lg bg-green-50 px-4 py-3">
                                <p class="text-green-700 font-medium">On Track</p>
                                <p class="mt-1 text-2xl font-semibold text-green-900">{{ $summary['on_track'] }}</p>
                            </div>
                            <div class="rounded-lg bg-sky-50 px-4 py-3">
                                <p class="text-sky-700 font-medium">Awaiting Start</p>
                                <p class="mt-1 text-2xl font-semibold text-sky-900">{{ $summary['awaiting_start'] }}</p>
                            </div>
                            <div class="rounded-lg bg-slate-50 px-4 py-3">
                                <p class="text-slate-700 font-medium">No Active Plan</p>
                                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ $summary['no_active_plan'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <h3 class="text-lg font-semibold text-slate-900">Scope</h3>
                    <div class="mt-4 flex flex-wrap gap-3">
                        @foreach($scopeHierarchies->sortBy('type') as $hierarchy)
                            <div class="rounded-full border border-slate-200 px-4 py-2 text-sm text-slate-700">
                                <span class="font-medium">{{ ucfirst($hierarchy->type) }}</span>
                                <span class="mx-1 text-slate-400">•</span>
                                {{ $hierarchy->name }}
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Filters</h3>
                            <p class="mt-1 text-sm text-slate-600">Narrow the list by status, subgroup, or member name.</p>
                        </div>

                        <form method="GET" action="{{ route('hierarchy.manage') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3 w-full lg:w-auto">
                            <div>
                                <label for="search" class="block text-xs font-medium uppercase tracking-wider text-slate-500">Search</label>
                                <input type="text" name="search" id="search" value="{{ $filters['search'] }}" placeholder="Name or email" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </div>

                            <div>
                                <label for="status" class="block text-xs font-medium uppercase tracking-wider text-slate-500">Status</label>
                                <select name="status" id="status" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    <option value="">All statuses</option>
                                    @foreach($statusOptions as $value => $label)
                                        <option value="{{ $value }}" {{ $filters['status'] === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="hierarchy_id" class="block text-xs font-medium uppercase tracking-wider text-slate-500">Group</label>
                                <select name="hierarchy_id" id="hierarchy_id" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    <option value="">All groups</option>
                                    @foreach($scopeHierarchies->sortBy('name') as $hierarchy)
                                        <option value="{{ $hierarchy->id }}" {{ $filters['hierarchy_id'] === $hierarchy->id ? 'selected' : '' }}>
                                            {{ $hierarchy->name }} ({{ ucfirst($hierarchy->type) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="flex gap-2">
                                <button type="submit" class="mt-5 inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                    Apply
                                </button>
                                <a href="{{ route('hierarchy.manage') }}" class="mt-5 inline-flex items-center justify-center rounded-md bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">
                                    Reset
                                </a>
                            </div>
                        </form>
                    </div>

                    <p class="mt-4 text-sm text-slate-600">
                        Showing {{ $memberSnapshots->count() }} of {{ $allMemberSnapshots->count() }} monitored people.
                    </p>
                </div>

                <div
                    class="rounded-[2rem] bg-white shadow-xl shadow-slate-900/5 overflow-hidden"
                    data-table-columns="leader-member-monitoring"
                    data-default-columns='{"group":false,"active-plan":false,"training":false,"expected-day":false,"completed":false,"last-completion":false}'
                    data-default-columns-md='{"group":true,"active-plan":true,"training":true}'
                    data-default-columns-xl='{"expected-day":true,"completed":true,"last-completion":true}'
                >
                    <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Member Monitoring</h3>
                            <p class="mt-1 text-sm text-slate-600">Training progress, current pace, recent reading activity, and team assignment for everyone in your span of care.</p>
                        </div>
                        <details class="relative">
                            <summary class="flex cursor-pointer list-none items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm shadow-slate-900/5 transition hover:border-slate-300 hover:text-slate-900">
                                <i class="fas fa-table-columns text-slate-400"></i>
                                Display columns
                                <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                            </summary>
                            <div class="absolute right-0 z-10 mt-3 w-80 rounded-3xl border border-slate-200 bg-white p-4 shadow-2xl shadow-slate-900/10">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Team monitor</p>
                                <p class="mt-2 text-sm text-slate-500">Choose which supporting details stay visible while you review your members.</p>
                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    <label class="flex items-center gap-3 text-sm text-slate-700">
                                        <input type="checkbox" data-column-toggle="group" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        Group
                                    </label>
                                    <label class="flex items-center gap-3 text-sm text-slate-700">
                                        <input type="checkbox" data-column-toggle="active-plan" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        Active plan
                                    </label>
                                    <label class="flex items-center gap-3 text-sm text-slate-700">
                                        <input type="checkbox" data-column-toggle="training" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        Training
                                    </label>
                                    <label class="flex items-center gap-3 text-sm text-slate-700">
                                        <input type="checkbox" data-column-toggle="expected-day" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        Expected day
                                    </label>
                                    <label class="flex items-center gap-3 text-sm text-slate-700">
                                        <input type="checkbox" data-column-toggle="completed" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        Completed
                                    </label>
                                    <label class="flex items-center gap-3 text-sm text-slate-700">
                                        <input type="checkbox" data-column-toggle="last-completion" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                        Last completion
                                    </label>
                                </div>
                                <button type="button" data-table-columns-reset class="mt-4 inline-flex items-center rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-slate-900">
                                    Reset compact defaults
                                </button>
                            </div>
                        </details>
                    </div>

                    <div class="overflow-x-auto" data-table-columns-root>
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Member</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500" data-column="group">Group</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500" data-column="active-plan">Active Plan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500" data-column="training">Training</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500" data-column="expected-day">Expected Day</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500" data-column="completed">Completed</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Pace</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Assignment</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500" data-column="last-completion">Last Completion</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                @forelse($memberSnapshots as $snapshot)
                                    @php
                                        $toneClasses = match ($snapshot['status_tone']) {
                                            'amber' => 'bg-amber-100 text-amber-800',
                                            'rose' => 'bg-rose-100 text-rose-800',
                                            'indigo' => 'bg-indigo-100 text-indigo-800',
                                            'green' => 'bg-green-100 text-green-800',
                                            'sky' => 'bg-sky-100 text-sky-800',
                                            default => 'bg-slate-100 text-slate-800',
                                        };
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4 align-top">
                                            <div>
                                                <p class="font-medium text-slate-900">{{ $snapshot['user']->name }}</p>
                                                <p class="text-sm text-slate-500">{{ $snapshot['user']->email }}</p>
                                                <p class="mt-1 text-xs uppercase tracking-wide text-slate-400">{{ $snapshot['user']->roleLabel() }}</p>
                                                <div class="mt-3 flex flex-wrap gap-2 text-xs md:hidden">
                                                    @if($snapshot['hierarchy'])
                                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-700">
                                                            {{ $snapshot['hierarchy']->name }}
                                                        </span>
                                                    @endif
                                                    @if($snapshot['active_plan'])
                                                        <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 font-medium text-sky-700">
                                                            {{ $snapshot['active_plan']->name }}
                                                        </span>
                                                    @endif
                                                    <span class="inline-flex rounded-full px-2.5 py-1 font-medium {{ $toneClasses }}">
                                                        {{ $snapshot['status_label'] }}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 align-top text-sm text-slate-700" data-column="group">
                                            <p>{{ $snapshot['hierarchy']?->name ?? 'Unassigned' }}</p>
                                            <p class="text-xs text-slate-500">{{ ucfirst($snapshot['hierarchy']?->type ?? 'none') }}</p>
                                        </td>
                                        <td class="px-6 py-4 align-top text-sm text-slate-700" data-column="active-plan">
                                            @if($snapshot['active_plan'])
                                                <p class="font-medium text-slate-900">{{ $snapshot['active_plan']->name }}</p>
                                                <p class="text-xs text-slate-500">{{ $snapshot['active_plan']->type_label }}</p>
                                            @else
                                                <span class="text-slate-400">No active plan</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 align-top text-sm text-slate-700" data-column="training">
                                            {{ $snapshot['training_progress'] }}
                                        </td>
                                        <td class="px-6 py-4 align-top text-sm text-slate-700" data-column="expected-day">
                                            {{ $snapshot['expected_day'] ?? '—' }}
                                        </td>
                                        <td class="px-6 py-4 align-top text-sm text-slate-700" data-column="completed">
                                            {{ $snapshot['completed_days'] }}
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            <div class="space-y-2">
                                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $toneClasses }}">
                                                    {{ $snapshot['status_label'] }}
                                                </span>
                                                @if($snapshot['behind_days'] > 0)
                                                    <p class="text-xs text-rose-700">{{ $snapshot['behind_days'] }} day(s) behind</p>
                                                @endif
                                                @if($snapshot['ahead_days'] > 0)
                                                    <p class="text-xs text-indigo-700">{{ $snapshot['ahead_days'] }} day(s) ahead</p>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 align-top text-sm text-slate-700">
                                            @if($snapshot['user']->role === \App\Models\User::ROLE_MEMBER && $manageableTeams->isNotEmpty())
                                                <form method="POST" action="{{ route('hierarchy.members.update', $snapshot['user']) }}" class="space-y-2">
                                                    @csrf
                                                    <select name="hierarchy_id" class="block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                                        @foreach($manageableTeams as $team)
                                                            <option value="{{ $team->id }}" {{ $snapshot['user']->hierarchy_id === $team->id ? 'selected' : '' }}>
                                                                {{ $team->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-3 py-2 text-xs font-medium text-white hover:bg-emerald-700">
                                                        Save Team
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-xs text-slate-500">{{ $snapshot['user']->role === \App\Models\User::ROLE_MEMBER ? 'No teams in scope' : 'Leader assignment managed by admin' }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 align-top text-sm text-slate-700" data-column="last-completion">
                                            {{ $snapshot['last_completion_date'] ? $snapshot['last_completion_date']->format('M d, Y') : 'No reading reported yet' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-6 py-8 text-center text-sm text-slate-500">
                                            No members are currently assigned within your hierarchy.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
    </div>
</x-app-layout>
