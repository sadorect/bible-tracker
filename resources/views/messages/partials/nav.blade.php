<div class="flex flex-col gap-3 rounded-[2rem] bg-white p-4 shadow-xl shadow-slate-900/5 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('messages.inbox') }}" class="inline-flex items-center justify-center rounded-2xl px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('messages.inbox') || request()->routeIs('messages.index') ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
            Inbox
        </a>
        <a href="{{ route('messages.sent') }}" class="inline-flex items-center justify-center rounded-2xl px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('messages.sent') ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
            Sent
        </a>
        <a href="{{ route('messages.compose') }}" class="inline-flex items-center justify-center rounded-2xl px-4 py-2.5 text-sm font-medium transition {{ request()->routeIs('messages.compose') ? 'bg-slate-900 text-white' : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50' }}">
            Compose
        </a>
    </div>

    @unless(request()->routeIs('messages.compose'))
        <a href="{{ route('messages.compose') }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
            New message
        </a>
    @endunless
</div>
