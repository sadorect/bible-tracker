<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">People Ops</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ $user->name }}</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-sky-700 text-white shadow-2xl shadow-slate-900/15">
            <div class="grid gap-6 px-6 py-8 sm:px-8 lg:grid-cols-[minmax(0,1.2fr)_minmax(18rem,0.8fr)] lg:px-10">
                <div class="flex items-start gap-4">
                    <x-user-avatar :name="$user->name" class="h-16 w-16 rounded-[1.5rem] bg-white text-slate-900 text-lg" />
                    <div>
                        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-sky-100">
                            {{ $user->roleLabel() }}
                        </span>
                        <h2 class="mt-4 text-3xl font-semibold">{{ $user->name }}</h2>
                        <p class="mt-2 text-sm text-slate-200">{{ $user->email }}</p>
                        @if($user->phone_number)
                            <p class="mt-1 text-sm text-slate-300">{{ $user->phone_number }}</p>
                        @endif
                        <p class="mt-3 text-sm text-slate-300">Joined {{ $user->created_at->format('M d, Y') }}</p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row lg:flex-col">
                    <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center justify-center rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-slate-950 transition hover:bg-slate-100">
                        Edit user
                    </a>
                    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-medium text-white transition hover:bg-white/15">
                        Back to users
                    </a>
                </div>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Plans</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $readingStats['total_plans'] }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Completions</p>
                <p class="mt-3 text-3xl font-semibold text-emerald-700">{{ $readingStats['completed_readings'] }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Completion rate</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ $readingStats['completion_rate'] }}%</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Current streak</p>
                <p class="mt-3 text-3xl font-semibold text-sky-700">{{ $readingStats['current_streak'] }}</p>
            </article>
            <article class="rounded-[1.75rem] bg-white p-5 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Longest streak</p>
                <p class="mt-3 text-3xl font-semibold text-amber-700">{{ $readingStats['longest_streak'] }}</p>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(0,0.95fr)]">
            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Enrollment</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Reading plans</h2>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse($user->readingPlans as $plan)
                        @php
                            $completedCount = $user->readingProgress()
                                ->whereHas('dailyReading', function ($query) use ($plan) {
                                    $query->where('reading_plan_id', $plan->id);
                                })
                                ->count();
                            $totalCount = $plan->dailyReadings->count();
                            $progressPercentage = $totalCount > 0 ? round(($completedCount / $totalCount) * 100, 1) : 0;
                        @endphp
                        <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-lg font-semibold text-slate-900">{{ $plan->name }}</h3>
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600">{{ $plan->type_label }}</span>
                                    </div>
                                    <p class="mt-2 text-sm text-slate-500">{{ $plan->description ?: 'No plan description yet.' }}</p>
                                    <p class="mt-3 text-xs text-slate-500">
                                        Joined {{ $plan->pivot->joined_date ? \Illuminate\Support\Carbon::parse($plan->pivot->joined_date)->format('M d, Y') : 'N/A' }}
                                        · Current day {{ $plan->pivot->current_day }}
                                    </p>
                                </div>
                                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                    {{ $plan->cadence_description }}
                                </span>
                            </div>

                            <div class="mt-5">
                                <div class="flex items-center justify-between text-sm text-slate-500">
                                    <span>Progress</span>
                                    <span>{{ $completedCount }}/{{ $totalCount }} · {{ $progressPercentage }}%</span>
                                </div>
                                <div class="mt-2 h-2.5 rounded-full bg-slate-200">
                                    <div class="h-2.5 rounded-full bg-emerald-500" style="width: {{ min(100, $progressPercentage) }}%"></div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-200 px-5 py-12 text-center text-sm text-slate-500">
                            No reading plans are assigned to this user yet.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="space-y-6">
                <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Account summary</p>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-[1.5rem] bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Status</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ $user->email_verified_at ? 'Active' : 'Inactive' }}</p>
                        </div>
                        <div class="rounded-[1.5rem] bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Hierarchy</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ $user->hierarchy?->name ?? 'Unassigned' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $user->hierarchy?->parent?->name ?? ($user->hierarchy ? ucfirst($user->hierarchy->type) : 'No group yet') }}</p>
                        </div>
                        <div class="rounded-[1.5rem] bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Last updated</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ $user->updated_at->format('M d, Y') }}</p>
                        </div>
                        <div class="rounded-[1.5rem] bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Message delivery</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ $user->messageDeliveryPreferenceLabel() ?? 'Admin default' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $user->message_delivery_preference_locked ? 'Locked by admin' : 'User can change this setting' }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">System access</p>
                    <div class="mt-5 space-y-4">
                        <div class="rounded-[1.5rem] bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Admin console access</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ $user->canAccessAdminPanel() ? 'Granted' : 'Not granted' }}</p>
                        </div>

                        <div class="rounded-[1.5rem] bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Assigned access roles</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse($user->systemRoles as $systemRole)
                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700">{{ $systemRole->name }}</span>
                                @empty
                                    <span class="text-sm text-slate-500">No system roles assigned.</span>
                                @endforelse
                            </div>
                        </div>

                        <div class="rounded-[1.5rem] bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Effective permissions</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse($user->permissionNames() as $permission)
                                    <span class="rounded-full bg-white px-3 py-1 font-mono text-xs text-slate-700">{{ $permission }}</span>
                                @empty
                                    <span class="text-sm text-slate-500">No system permissions granted.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Activity feed</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Recent completions</h2>
                    </div>

                    <div class="mt-6 space-y-3">
                        @forelse($recentActivity as $activity)
                            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4">
                                <p class="text-sm font-semibold text-slate-900">{{ $activity->dailyReading->reading_range }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ $activity->dailyReading->readingPlan->name }} · Day {{ $activity->dailyReading->day_number }}</p>
                                <p class="mt-3 text-xs text-slate-400">{{ $activity->completed_date->format('M d, Y') }}</p>
                            </div>
                        @empty
                            <div class="rounded-[1.5rem] border border-dashed border-slate-200 px-5 py-12 text-center text-sm text-slate-500">
                                No recent activity recorded.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </section>
    </div>
</x-admin-layout>
