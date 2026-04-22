<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">People Ops</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Manage members, leaders, and admin access.</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Total users</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($stats['total_users']) }}</p>
                <p class="mt-2 text-sm text-slate-500">Everyone currently registered in the movement.</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Active users</p>
                <p class="mt-3 text-3xl font-semibold text-emerald-700">{{ number_format($stats['active_users']) }}</p>
                <p class="mt-2 text-sm text-slate-500">Email-verified readers ready to participate.</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Leaders</p>
                <p class="mt-3 text-3xl font-semibold text-sky-700">{{ number_format($stats['leaders']) }}</p>
                <p class="mt-2 text-sm text-slate-500">Accounts currently assigned to a leadership role.</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Members</p>
                <p class="mt-3 text-3xl font-semibold text-amber-700">{{ number_format($stats['members']) }}</p>
                <p class="mt-2 text-sm text-slate-500">Readers who are being guided through cohorts.</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(20rem,0.8fr)]">
            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Directory filters</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Narrow the user list quickly</h2>
                    </div>
                    <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Add new user
                    </a>
                </div>

                <form method="GET" action="{{ route('admin.users.index') }}" class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Search</span>
                        <input
                            type="text"
                            name="search"
                            id="search"
                            value="{{ request('search') }}"
                            placeholder="Name or email"
                            class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500"
                        >
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Role</span>
                        <select name="role" id="role" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            <option value="">All roles</option>
                            @foreach($stats['roles'] as $value => $label)
                                <option value="{{ $value }}" {{ request('role') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Status</span>
                        <select name="status" id="status" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            <option value="">All statuses</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Reading plan</span>
                        <select name="reading_plan" id="reading_plan" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            <option value="">All plans</option>
                            @foreach($readingPlans as $plan)
                                <option value="{{ $plan->id }}" {{ request('reading_plan') == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Rows per page</span>
                        <select name="per_page" id="per_page" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            @foreach($allowedPerPage as $pageSize)
                                <option value="{{ $pageSize }}" {{ $perPage === $pageSize ? 'selected' : '' }}>{{ $pageSize }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div class="flex flex-col justify-end gap-3 sm:flex-row md:col-span-2 xl:col-span-1 xl:flex-col">
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                            Apply filters
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <form id="bulk-action-form" method="POST" action="{{ route('admin.users.bulk-action') }}" class="space-y-5">
                    @csrf

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Bulk actions</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Apply one action to many users</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Select users from the table below, then choose a shared action here.</p>
                    </div>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Action</span>
                        <select name="action" id="bulk-action" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            <option value="">Select action</option>
                            <option value="assign_plan">Assign reading plan</option>
                            <option value="remove_plan">Remove reading plan</option>
                            <option value="change_role">Change role</option>
                            <option value="assign_hierarchy">Assign group</option>
                            <option value="distribute_evenly">Distribute across teams evenly</option>
                            <option value="clear_hierarchy">Clear group</option>
                            <option value="delete">Delete users</option>
                        </select>
                    </label>

                    <div id="reading-plan-select" class="hidden">
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Reading plan</span>
                            <select name="reading_plan_id" id="bulk-reading-plan" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                                <option value="">Select plan</option>
                                @foreach($readingPlans as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div id="role-select" class="hidden">
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">New role</span>
                            <select name="role" id="bulk-role" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                                <option value="">Select role</option>
                                @foreach($stats['roles'] as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div id="hierarchy-select" class="hidden">
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Group</span>
                            <select name="hierarchy_id" id="bulk-hierarchy" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                                <option value="">Select group</option>
                                @foreach($hierarchies as $hierarchy)
                                    <option value="{{ $hierarchy->id }}">{{ $hierarchy->displayPath() }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div id="team-distribution-select" class="hidden">
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Target teams</span>
                            <select name="target_team_ids[]" id="bulk-target-teams" multiple class="mt-2 min-h-36 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                                @foreach($hierarchies->where('type', 'team') as $hierarchy)
                                    <option value="{{ $hierarchy->id }}">{{ $hierarchy->displayPath() }}</option>
                                @endforeach
                            </select>
                            <p class="mt-2 text-xs text-slate-500">Choose at least two teams from the same batch. Selected members will be placed into the lightest-loaded team first.</p>
                        </label>
                    </div>

                    <button type="submit" id="bulk-submit" disabled class="inline-flex w-full items-center justify-center rounded-2xl bg-amber-500 px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400">
                        Apply to selected users
                    </button>
                </form>
            </div>
        </section>

        <section
            class="rounded-[2rem] bg-white shadow-xl shadow-slate-900/5"
            data-table-columns="admin-user-directory"
            data-default-columns='{"role":true,"group":true,"status":false,"plans":false,"completions":false,"joined":false}'
            data-default-columns-md='{"status":true,"plans":true}'
            data-default-columns-xl='{"completions":true,"joined":true}'
        >
            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">User directory</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Users ({{ $users->total() }})</h2>
                </div>
                <div class="flex flex-col gap-3 sm:items-end">
                    <p class="text-sm text-slate-500">Select rows for bulk actions, or manage each user individually. Showing {{ $users->count() }} of {{ $users->total() }} users.</p>
                    <details class="relative">
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm shadow-slate-900/5 transition hover:border-slate-300 hover:text-slate-900">
                            <i class="fas fa-table-columns text-slate-400"></i>
                            Display columns
                            <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                        </summary>
                        <div class="absolute right-0 z-10 mt-3 w-72 rounded-3xl border border-slate-200 bg-white p-4 shadow-2xl shadow-slate-900/10">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Directory view</p>
                            <p class="mt-2 text-sm text-slate-500">Choose which optional columns stay visible on this device.</p>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <label class="flex items-center gap-3 text-sm text-slate-700">
                                    <input type="checkbox" data-column-toggle="role" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    Role
                                </label>
                                <label class="flex items-center gap-3 text-sm text-slate-700">
                                    <input type="checkbox" data-column-toggle="group" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    Group
                                </label>
                                <label class="flex items-center gap-3 text-sm text-slate-700">
                                    <input type="checkbox" data-column-toggle="status" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    Status
                                </label>
                                <label class="flex items-center gap-3 text-sm text-slate-700">
                                    <input type="checkbox" data-column-toggle="plans" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    Plans
                                </label>
                                <label class="flex items-center gap-3 text-sm text-slate-700">
                                    <input type="checkbox" data-column-toggle="completions" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    Completions
                                </label>
                                <label class="flex items-center gap-3 text-sm text-slate-700">
                                    <input type="checkbox" data-column-toggle="joined" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    Joined
                                </label>
                            </div>
                            <button type="button" data-table-columns-reset class="mt-4 inline-flex items-center rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-slate-900">
                                Reset compact defaults
                            </button>
                        </div>
                    </details>
                </div>
            </div>

            <div class="overflow-x-auto" data-table-columns-root>
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                            <th class="px-6 py-4">
                                <input type="checkbox" id="select-all" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            </th>
                            <th class="px-6 py-4">User</th>
                            <th class="px-6 py-4" data-column="role">Role</th>
                            <th class="px-6 py-4" data-column="group">Group</th>
                            <th class="px-6 py-4" data-column="status">Status</th>
                            <th class="px-6 py-4" data-column="plans">Plans</th>
                            <th class="px-6 py-4" data-column="completions">Completions</th>
                            <th class="px-6 py-4" data-column="joined">Joined</th>
                            <th class="px-6 py-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse($users as $user)
                            @php
                                $roleClasses = match ($user->role) {
                                    'admin' => 'bg-rose-100 text-rose-700',
                                    \App\Models\User::ROLE_CLAN_LEADER,
                                    \App\Models\User::ROLE_PLATOON_LEADER,
                                    \App\Models\User::ROLE_SQUAD_LEADER,
                                    \App\Models\User::ROLE_BATCH_LEADER,
                                    \App\Models\User::ROLE_TEAM_LEADER => 'bg-sky-100 text-sky-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                                $statusClasses = $user->email_verified_at
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-amber-100 text-amber-700';
                            @endphp
                            <tr class="align-top transition hover:bg-slate-50/80">
                                <td class="px-6 py-5">
                                    <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="user-checkbox rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex items-center gap-4">
                                        <img
                                            class="h-11 w-11 rounded-2xl object-cover"
                                            src="https://ui-avatars.com/api/?name={{ urlencode($user->name) }}&background=0f172a&color=fff"
                                            alt="{{ $user->name }}"
                                        >
                                        <div>
                                            <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                                            <p class="text-sm text-slate-500">{{ $user->email }}</p>
                                            @if($user->phone_number)
                                                <p class="mt-1 text-xs text-slate-400">{{ $user->phone_number }}</p>
                                            @endif
                                            <div class="mt-3 flex flex-wrap gap-2 text-xs md:hidden">
                                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-600">{{ $user->roleLabel() }}</span>
                                                @if($user->hierarchy)
                                                    <span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 font-medium text-sky-700">{{ $user->hierarchy->name }}</span>
                                                @endif
                                                <span class="inline-flex rounded-full px-2.5 py-1 font-medium {{ $statusClasses }}">{{ $user->email_verified_at ? 'Active' : 'Inactive' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5" data-column="role">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $roleClasses }}">
                                        {{ $user->roleLabel() }}
                                    </span>
                                </td>
                                <td class="px-6 py-5" data-column="group">
                                    @if($user->hierarchy)
                                        <div title="{{ $user->hierarchy->displayPath() }}">
                                            <p class="text-sm font-medium text-slate-900">{{ $user->hierarchy->name }}</p>
                                            <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-400">{{ $user->hierarchy->type }}</p>
                                        </div>
                                    @else
                                        <span class="text-sm text-slate-500">Unassigned</span>
                                    @endif
                                </td>
                                <td class="px-6 py-5" data-column="status">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusClasses }}">
                                        {{ $user->email_verified_at ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-5 text-sm text-slate-600" data-column="plans">
                                    {{ $user->reading_plans_count }}
                                </td>
                                <td class="px-6 py-5 text-sm text-slate-600" data-column="completions">
                                    {{ $user->reading_progress_count }}
                                </td>
                                <td class="px-6 py-5 text-sm text-slate-500" data-column="joined">
                                    {{ $user->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-6 py-5">
                                    <div class="flex flex-wrap gap-2 text-sm font-medium">
                                        <a href="{{ route('admin.users.show', $user) }}" class="inline-flex rounded-full bg-slate-100 px-3 py-1.5 text-slate-700 transition hover:bg-slate-200">
                                            View
                                        </a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex rounded-full bg-sky-100 px-3 py-1.5 text-sky-700 transition hover:bg-sky-200">
                                            Edit
                                        </a>
                                        @if($user->role !== 'admin' || \App\Models\User::where('role', 'admin')->count() > 1)
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex rounded-full bg-rose-100 px-3 py-1.5 text-rose-700 transition hover:bg-rose-200">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-16 text-center text-sm text-slate-500">
                                    No users match the current filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $users->links() }}
            </div>
        </section>
    </div>

    @push('scripts')
        <script>
            document.getElementById('select-all').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.user-checkbox');
                checkboxes.forEach((checkbox) => {
                    checkbox.checked = this.checked;
                });
                updateBulkActions();
            });

            document.querySelectorAll('.user-checkbox').forEach((checkbox) => {
                checkbox.addEventListener('change', updateBulkActions);
            });

            document.getElementById('bulk-reading-plan').addEventListener('change', updateBulkActions);
            document.getElementById('bulk-role').addEventListener('change', updateBulkActions);
            document.getElementById('bulk-hierarchy').addEventListener('change', updateBulkActions);
            document.getElementById('bulk-target-teams').addEventListener('change', updateBulkActions);

            document.getElementById('bulk-action').addEventListener('change', function() {
                const action = this.value;
                const readingPlanSelect = document.getElementById('reading-plan-select');
                const roleSelect = document.getElementById('role-select');
                const hierarchySelect = document.getElementById('hierarchy-select');
                const teamDistributionSelect = document.getElementById('team-distribution-select');

                readingPlanSelect.classList.add('hidden');
                roleSelect.classList.add('hidden');
                hierarchySelect.classList.add('hidden');
                teamDistributionSelect.classList.add('hidden');

                if (action === 'assign_plan' || action === 'remove_plan') {
                    readingPlanSelect.classList.remove('hidden');
                } else if (action === 'change_role') {
                    roleSelect.classList.remove('hidden');
                } else if (action === 'assign_hierarchy') {
                    hierarchySelect.classList.remove('hidden');
                } else if (action === 'distribute_evenly') {
                    teamDistributionSelect.classList.remove('hidden');
                }

                updateBulkActions();
            });

            function updateBulkActions() {
                const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
                const bulkSubmit = document.getElementById('bulk-submit');
                const bulkAction = document.getElementById('bulk-action');
                const action = bulkAction.value;
                const selectedCount = selectedCheckboxes.length;
                const readingPlanValue = document.getElementById('bulk-reading-plan').value;
                const roleValue = document.getElementById('bulk-role').value;
                const hierarchyValue = document.getElementById('bulk-hierarchy').value;
                const targetTeamsValue = Array.from(document.getElementById('bulk-target-teams').selectedOptions).map((option) => option.value);

                let actionReady = action.length > 0;

                if (action === 'assign_plan' || action === 'remove_plan') {
                    actionReady = readingPlanValue.length > 0;
                } else if (action === 'change_role') {
                    actionReady = roleValue.length > 0;
                } else if (action === 'assign_hierarchy') {
                    actionReady = hierarchyValue.length > 0;
                } else if (action === 'distribute_evenly') {
                    actionReady = targetTeamsValue.length >= 2;
                }

                bulkSubmit.disabled = !(selectedCount > 0 && actionReady);
            }

            document.getElementById('bulk-action-form').addEventListener('submit', function(e) {
                const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
                const action = document.getElementById('bulk-action').value;

                if (selectedCheckboxes.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one user.');
                    return;
                }

                if (action === 'delete' && ! confirm(`Are you sure you want to delete ${selectedCheckboxes.length} user(s)?`)) {
                    e.preventDefault();
                    return;
                }

                if (action === 'assign_hierarchy' && ! document.getElementById('bulk-hierarchy').value) {
                    e.preventDefault();
                    alert('Please choose a group for the selected users.');
                    return;
                }

                if (action === 'distribute_evenly' && document.getElementById('bulk-target-teams').selectedOptions.length < 2) {
                    e.preventDefault();
                    alert('Please choose at least two teams for balanced distribution.');
                    return;
                }

                this.querySelectorAll('input[name="user_ids[]"][type="hidden"]').forEach((input) => input.remove());

                selectedCheckboxes.forEach((checkbox) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'user_ids[]';
                    input.value = checkbox.value;
                    this.appendChild(input);
                });
            });

        </script>
    @endpush
</x-admin-layout>
