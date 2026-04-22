<x-dynamic-component :component="$layoutComponent">
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Message Centre</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Thread detail</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        @include('messages.partials.nav')

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <section class="space-y-6">
            <article class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Conversation</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">{{ $threadRoot->subject }}</h2>
                <p class="mt-3 text-sm text-slate-500">Started by {{ $threadRoot->sender->name }} on {{ $threadRoot->created_at->format('M d, Y g:i A') }}</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ ucfirst($threadMailbox) }}</span>
                    @if($threadFolder === 'archive')
                        <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">Archived</span>
                    @elseif($threadFolder === 'trash')
                        <span class="inline-flex rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-700">Trash</span>
                    @endif
                </div>
            </article>

            @foreach($threadMessages as $message)
                @php($recipientRow = $userRecipientRows->get($message->id))
                <article class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-lg font-semibold text-slate-900">{{ $message->sender->name }}</p>
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ ucfirst($message->direction) }}</span>
                            </div>
                            <p class="mt-1 text-sm text-slate-500">{{ $message->created_at->format('M d, Y g:i A') }}</p>
                        </div>
                    </div>

                    <div class="mt-5 whitespace-pre-line text-sm leading-7 text-slate-700">
                        {{ $message->sender_id === auth()->id() ? $message->body : ($recipientRow?->rendered_body ?? $message->body) }}
                    </div>

                    @if($message->sender_id === auth()->id())
                        <div class="mt-6 border-t border-slate-200 pt-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Recipients</p>
                            <div class="mt-3 grid gap-3">
                                @foreach($message->recipients as $recipient)
                                    <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3 text-sm text-slate-600">
                                        <div class="flex flex-wrap items-center justify-between gap-3">
                                            <div>
                                                <p class="font-semibold text-slate-900">{{ $recipient->recipient->name }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ $recipient->recipient->email }}</p>
                                            </div>
                                            <div class="flex flex-wrap gap-2 text-xs">
                                                <span class="rounded-full bg-white px-3 py-1">{{ ucfirst($recipient->delivery_preference_snapshot) }}</span>
                                                <span class="rounded-full px-3 py-1 {{ $recipient->read_at ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                                    {{ $recipient->read_at ? 'Read' : 'Unread' }}
                                                </span>
                                                <span class="rounded-full px-3 py-1 {{ $recipient->email_status === 'sent' ? 'bg-sky-100 text-sky-700' : ($recipient->email_status === 'failed' ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600') }}">
                                                    Email {{ ucfirst($recipient->email_status) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </article>
            @endforeach
        </section>

        <aside class="space-y-6">
            @if($canReply && $threadFolder !== 'trash')
                <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Reply</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Continue this thread</h2>

                    <form method="POST" action="{{ route('messages.store') }}" class="mt-6 space-y-5">
                        @csrf
                        <input type="hidden" name="reply_to" value="{{ $threadRoot->id }}">
                        <input type="hidden" name="direction" value="{{ auth()->user()->isAdmin() || auth()->user()->isLeader() ? 'downward' : 'upward' }}">

                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Subject</span>
                            <input type="text" name="subject" value="{{ $replySubject }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Message</span>
                            <textarea name="body" rows="6" required class="mt-2 w-full rounded-[1.5rem] border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500"></textarea>
                        </label>

                        @if(auth()->user()->isAdmin() || auth()->user()->isLeader())
                            <div>
                                <p class="text-sm font-medium text-slate-700">Recipients</p>
                                <div class="mt-3 space-y-3">
                                    @foreach($replyRecipients as $recipient)
                                        <label class="flex items-start gap-3 rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                            <input type="checkbox" name="recipient_ids[]" value="{{ $recipient->id }}" checked class="mt-1 rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                            <div>
                                                <p class="font-semibold text-slate-900">{{ $recipient->name }}</p>
                                                <p class="mt-1 text-xs text-slate-500">{{ $recipient->email }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            @foreach($replyRecipients as $recipient)
                                <input type="hidden" name="recipient_ids[]" value="{{ $recipient->id }}">
                            @endforeach
                        @endif

                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Send reply
                        </button>
                    </form>
                </section>
            @endif

            <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Conversation actions</p>
                <div class="mt-4 flex flex-col gap-3">
                    @if($threadFolder === 'trash')
                        <form method="POST" action="{{ route('messages.restore', $threadRoot) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                Restore conversation
                            </button>
                        </form>
                    @else
                        @if($threadFolder !== 'archive')
                            <form method="POST" action="{{ route('messages.archive', $threadRoot) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                    Archive conversation
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('messages.restore', $threadRoot) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                                    Restore from archive
                                </button>
                            </form>
                        @endif

                        <form method="POST" action="{{ route('messages.trash', $threadRoot) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 transition hover:bg-rose-100">
                                Move to trash
                            </button>
                        </form>
                    @endif

                    <a href="{{ route('messages.inbox', ['folder' => $threadMailbox === 'inbox' ? $threadFolder : 'inbox']) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">Back to inbox</a>
                    <a href="{{ route('messages.sent', ['folder' => $threadMailbox === 'sent' ? $threadFolder : 'sent']) }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50">View sent messages</a>
                    @if($threadFolder !== 'trash' && !$canReply)
                        <p class="text-sm text-slate-500">Reply is unavailable for this thread.</p>
                    @elseif($threadFolder === 'trash')
                        <p class="text-sm text-slate-500">Restore this conversation before replying.</p>
                    @endif
                </div>
            </section>
        </aside>
        </div>
    </div>
</x-dynamic-component>
