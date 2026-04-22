<div class="space-y-6">
    <section class="grid gap-4 sm:grid-cols-2">
        <article class="rounded-[1.75rem] border border-slate-200 bg-white px-5 py-5 shadow-sm shadow-slate-900/5">
            <p class="text-sm text-slate-500">Unread alerts</p>
            <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($unreadCount) }}</p>
        </article>
        <article class="rounded-[1.75rem] border border-slate-200 bg-white px-5 py-5 shadow-sm shadow-slate-900/5">
            <p class="text-sm text-slate-500">Total alerts</p>
            <p class="mt-3 text-3xl font-semibold text-slate-900">{{ number_format($notifications->total()) }}</p>
        </article>
    </section>

    <section class="rounded-[2rem] border border-slate-200 bg-white shadow-sm shadow-slate-900/5">
        <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Recent alerts</h2>
                <p class="mt-1 text-sm text-slate-500">Training reminders, reading nudges, and daily leadership digests appear here.</p>
            </div>
            @if($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="inline-flex rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900">
                        Mark all as read
                    </button>
                </form>
            @endif
        </div>

        <div class="divide-y divide-slate-200">
            @forelse($notifications as $notification)
                @php($data = $notification->data)
                <article class="px-6 py-5 {{ $notification->read_at ? 'bg-white' : 'bg-emerald-50/40' }}">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $notification->read_at ? 'bg-slate-100 text-slate-600' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ $notification->read_at ? 'Read' : 'Unread' }}
                                </span>
                                <span class="text-xs uppercase tracking-[0.18em] text-slate-400">{{ str($data['category'] ?? 'alert')->replace('_', ' ')->headline() }}</span>
                            </div>
                            <h3 class="mt-3 text-lg font-semibold text-slate-900">{{ $data['title'] ?? 'Notification' }}</h3>
                            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $data['body'] ?? '' }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                <span>{{ $notification->created_at->format('M j, Y g:i A') }}</span>
                                @if(!empty($data['action_url']) && !empty($data['action_label']))
                                    <a href="{{ $data['action_url'] }}" class="font-semibold text-emerald-700 hover:text-emerald-800">{{ $data['action_label'] }}</a>
                                @endif
                            </div>
                        </div>

                        @if(!$notification->read_at)
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:text-slate-900">
                                    Mark as read
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="px-6 py-16 text-center text-sm text-slate-500">
                    No alerts have been delivered yet.
                </div>
            @endforelse
        </div>

        <div class="border-t border-slate-200 px-6 py-4">
            {{ $notifications->links() }}
        </div>
    </section>
</div>
