<x-dynamic-component :component="$layoutComponent">
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Message Centre</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Sent</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        @include('messages.partials.nav')

        <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
            <form method="GET" action="{{ route('messages.sent') }}" class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Search</span>
                    <input type="text" name="search" value="{{ $filters['search'] }}" placeholder="Subject or body" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                </label>
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                    Apply
                </button>
            </form>
        </section>

        <section class="rounded-[2rem] bg-white shadow-xl shadow-slate-900/5">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-2xl font-semibold text-slate-900">Sent messages ({{ $sentItems->total() }})</h2>
            </div>

            <div class="divide-y divide-slate-200">
                @forelse($sentItems as $message)
                    <a href="{{ route('messages.show', $message->thread_root_id ?: $message->id) }}" class="block px-6 py-5 transition hover:bg-slate-50/80">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-xs font-semibold text-sky-700">{{ ucfirst($message->direction) }}</span>
                                    @if($message->template)
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $message->template->name }}</span>
                                    @endif
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $message->subject }}</p>
                                </div>
                                <p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-600">{{ $message->body }}</p>
                                <div class="mt-4 flex flex-wrap gap-2 text-xs text-slate-500">
                                    <span class="rounded-full bg-slate-100 px-3 py-1">{{ $message->recipients_count }} recipients</span>
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">{{ $message->read_count }} read</span>
                                    <span class="rounded-full bg-sky-100 px-3 py-1 text-sky-700">{{ $message->emailed_count }} emailed</span>
                                    @if($message->failed_email_count > 0)
                                        <span class="rounded-full bg-rose-100 px-3 py-1 text-rose-700">{{ $message->failed_email_count }} failed email</span>
                                    @endif
                                </div>
                            </div>
                            <div class="shrink-0 text-sm text-slate-500">
                                {{ $message->created_at->format('M d, Y g:i A') }}
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="px-6 py-16 text-center text-sm text-slate-500">
                        You have not sent any messages yet.
                    </div>
                @endforelse
            </div>

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $sentItems->links() }}
            </div>
        </section>
    </div>
</x-dynamic-component>
