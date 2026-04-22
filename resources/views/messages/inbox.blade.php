<x-dynamic-component :component="$layoutComponent">
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Message Centre</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Inbox</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        @include('messages.partials.nav')

        <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Inbox filters</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">See unread and read conversations</h2>
                </div>

                <form method="GET" action="{{ route('messages.inbox') }}" class="grid gap-3 sm:grid-cols-3">
                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">State</span>
                        <select name="state" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            <option value="">All messages</option>
                            <option value="unread" {{ $filters['state'] === 'unread' ? 'selected' : '' }}>Unread</option>
                            <option value="read" {{ $filters['state'] === 'read' ? 'selected' : '' }}>Read</option>
                        </select>
                    </label>

                    <label class="block sm:col-span-2">
                        <span class="text-sm font-medium text-slate-700">Search</span>
                        <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Subject, sender, or text" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                    </label>
                </form>
            </div>
        </section>

        <section class="rounded-[2rem] bg-white shadow-xl shadow-slate-900/5">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-2xl font-semibold text-slate-900">Messages ({{ $inboxItems->total() }})</h2>
            </div>

            <div class="divide-y divide-slate-200">
                @forelse($inboxItems as $item)
                    @php
                        $threadId = $item->message->thread_root_id ?: $item->message->id;
                    @endphp
                    <a href="{{ route('messages.show', $threadId) }}" class="block px-6 py-5 transition hover:bg-slate-50/80">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if(!$item->read_at)
                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">Unread</span>
                                    @endif
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $item->rendered_subject }}</p>
                                </div>
                                <p class="mt-2 text-sm text-slate-500">From {{ $item->message->sender->name }}</p>
                                <p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-600">{{ $item->rendered_body }}</p>
                            </div>
                            <div class="shrink-0 text-sm text-slate-500">
                                {{ $item->created_at->format('M d, Y g:i A') }}
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-6 py-16 text-center text-sm text-slate-500">
                        Your inbox is empty.
                    </div>
                @endforelse
            </div>

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $inboxItems->links() }}
            </div>
        </section>
    </div>
</x-dynamic-component>
