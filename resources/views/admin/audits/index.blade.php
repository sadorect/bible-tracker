<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Audit Trail</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Track high-value operational changes.</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-[1.75rem] border border-slate-200 bg-white px-5 py-5 shadow-sm shadow-slate-900/5">
                <p class="text-sm text-slate-500">Total events</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($stats['total']) }}</p>
            </article>
            <article class="rounded-[1.75rem] border border-slate-200 bg-white px-5 py-5 shadow-sm shadow-slate-900/5">
                <p class="text-sm text-slate-500">Today</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($stats['today']) }}</p>
            </article>
            <article class="rounded-[1.75rem] border border-slate-200 bg-white px-5 py-5 shadow-sm shadow-slate-900/5">
                <p class="text-sm text-slate-500">Last 7 days</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($stats['last_7_days']) }}</p>
            </article>
            <article class="rounded-[1.75rem] border border-slate-200 bg-white px-5 py-5 shadow-sm shadow-slate-900/5">
                <p class="text-sm text-slate-500">Active operators</p>
                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($stats['unique_actors']) }}</p>
            </article>
        </section>

        <section class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm shadow-slate-900/5">
            <form method="GET" action="{{ route('admin.audits.index') }}" class="grid gap-4 lg:grid-cols-5">
                <div>
                    <label for="event" class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Event</label>
                    <select id="event" name="event" class="mt-2 w-full rounded-2xl border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="">All events</option>
                        @foreach($eventOptions as $eventOption)
                            <option value="{{ $eventOption }}" @selected($filters['event'] === $eventOption)>{{ str($eventOption)->replace('.', ' ')->headline() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="actor_id" class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Actor</label>
                    <select id="actor_id" name="actor_id" class="mt-2 w-full rounded-2xl border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="0">All operators</option>
                        @foreach($actorOptions as $actorOption)
                            <option value="{{ $actorOption->id }}" @selected($filters['actor_id'] === $actorOption->id)>{{ $actorOption->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_range" class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Window</label>
                    <select id="date_range" name="date_range" class="mt-2 w-full rounded-2xl border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="24_hours" @selected($filters['date_range'] === '24_hours')>Last 24 hours</option>
                        <option value="7_days" @selected($filters['date_range'] === '7_days')>Last 7 days</option>
                        <option value="30_days" @selected($filters['date_range'] === '30_days')>Last 30 days</option>
                        <option value="all" @selected($filters['date_range'] === 'all')>All time</option>
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label for="search" class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Search</label>
                    <input id="search" type="text" name="search" value="{{ $filters['search'] }}" placeholder="Search event, description, subject, or actor" class="mt-2 w-full rounded-2xl border-slate-200 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                </div>
                <div class="flex items-end gap-3 lg:col-span-5">
                    <button type="submit" class="inline-flex rounded-2xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-950/10 transition hover:bg-slate-800">Apply filters</button>
                    <a href="{{ route('admin.audits.index') }}" class="inline-flex rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:text-slate-900">Reset</a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-sm shadow-slate-900/5">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-semibold text-slate-900">Recent audit events</h2>
                <p class="mt-1 text-sm text-slate-500">Changes to plans, hierarchy assignments, exports, and other operational actions land here.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80">
                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                            <th class="px-6 py-4">Event</th>
                            <th class="px-6 py-4">Actor</th>
                            <th class="px-6 py-4">Subject</th>
                            <th class="px-6 py-4">Details</th>
                            <th class="px-6 py-4">Route</th>
                            <th class="px-6 py-4">When</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white text-sm text-slate-700">
                        @forelse($auditLogs as $auditLog)
                            <tr class="align-top">
                                <td class="px-6 py-5">
                                    <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $auditLog->eventLabel() }}</span>
                                    <p class="mt-2 text-xs text-slate-400">{{ $auditLog->event }}</p>
                                </td>
                                <td class="px-6 py-5">
                                    <p class="font-medium text-slate-900">{{ $auditLog->actor?->name ?? 'System' }}</p>
                                    @if($auditLog->actor?->email)
                                        <p class="mt-1 text-xs text-slate-500">{{ $auditLog->actor->email }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-5">
                                    <p class="font-medium text-slate-900">{{ $auditLog->subject_label ?? 'Not attached' }}</p>
                                    @if($auditLog->subject_type)
                                        <p class="mt-1 text-xs text-slate-500">{{ class_basename($auditLog->subject_type) }} #{{ $auditLog->subject_id }}</p>
                                    @endif
                                </td>
                                <td class="px-6 py-5">
                                    <p class="leading-6 text-slate-700">{{ $auditLog->description ?: 'No extra description captured.' }}</p>
                                    @if(!empty($auditLog->metadata))
                                        <dl class="mt-3 space-y-1 text-xs text-slate-500">
                                            @foreach($auditLog->metadata as $key => $value)
                                                <div>
                                                    <dt class="inline font-semibold text-slate-600">{{ str($key)->replace('_', ' ')->headline() }}:</dt>
                                                    <dd class="inline">{{ is_array($value) ? json_encode($value) : $value }}</dd>
                                                </div>
                                            @endforeach
                                        </dl>
                                    @endif
                                </td>
                                <td class="px-6 py-5 text-xs text-slate-500">
                                    {{ $auditLog->route_name ?? 'Unknown route' }}
                                </td>
                                <td class="px-6 py-5">
                                    <p class="font-medium text-slate-900">{{ $auditLog->created_at?->format('M j, Y') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $auditLog->created_at?->format('g:i A') }}</p>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center text-sm text-slate-500">No audit events match the current filters yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $auditLogs->links() }}
            </div>
        </section>
    </div>
</x-admin-layout>
