<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Message Centre</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Messaging settings</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <form method="POST" action="{{ route('admin.messages.settings.update') }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Delivery defaults</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Global message delivery</h2>
                    </div>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Default delivery mode</span>
                        <select name="default_delivery" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            @foreach($deliveryOptions as $value => $label)
                                <option value="{{ $value }}" {{ $defaultDelivery === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="flex items-start gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4">
                        <input type="hidden" name="email_enabled" value="0">
                        <input type="checkbox" name="email_enabled" value="1" {{ $emailEnabled ? 'checked' : '' }} class="mt-1 rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Enable email delivery</p>
                            <p class="mt-1 text-sm text-slate-500">When disabled, every message falls back to inbox-only delivery.</p>
                        </div>
                    </label>

                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Save settings
                    </button>
                </form>
            </div>

            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Merge variables</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Available placeholders</h2>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    @foreach($availableVariables as $key => $label)
                        <div class="rounded-[1.35rem] bg-slate-50 px-4 py-4">
                            <p class="font-mono text-xs text-slate-900">&#123;&#123; {{ $key }} &#125;&#125;</p>
                            <p class="mt-2 text-sm text-slate-500">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <form method="POST" action="{{ route('admin.messages.templates.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Template library</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Create a shared template</h2>
                    </div>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Name</span>
                        <input type="text" name="name" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Subject template</span>
                        <input type="text" name="subject_template" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Body template</span>
                        <textarea name="body_template" rows="6" required class="mt-2 w-full rounded-[1.5rem] border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500"></textarea>
                    </label>

                    <label class="flex items-start gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" checked class="mt-1 rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Active</p>
                            <p class="mt-1 text-sm text-slate-500">Active templates are available in the compose screen.</p>
                        </div>
                    </label>

                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700">
                        Create template
                    </button>
                </form>
            </div>

            <div class="space-y-4">
                @forelse($templates as $template)
                    <article class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                        <form id="update-template-{{ $template->id }}" method="POST" action="{{ route('admin.messages.templates.update', $template) }}" class="space-y-4">
                            @csrf
                            @method('PUT')

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $template->name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Created by {{ $template->creator?->name ?? 'Unknown' }}</p>
                                </div>
                            </div>

                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">Name</span>
                                <input type="text" name="name" value="{{ $template->name }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            </label>

                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">Subject template</span>
                                <input type="text" name="subject_template" value="{{ $template->subject_template }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            </label>

                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">Body template</span>
                                <textarea name="body_template" rows="5" required class="mt-2 w-full rounded-[1.5rem] border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">{{ $template->body_template }}</textarea>
                            </label>

                            <label class="flex items-start gap-3 rounded-[1.5rem] border border-slate-200 bg-slate-50 px-4 py-4">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" value="1" {{ $template->is_active ? 'checked' : '' }} class="mt-1 rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Active</p>
                                    <p class="mt-1 text-sm text-slate-500">Inactive templates stay stored but disappear from compose.</p>
                                </div>
                            </label>

                        </form>

                        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:justify-between">
                            <form method="POST" action="{{ route('admin.messages.templates.destroy', $template) }}" onsubmit="return confirm('Delete this message template?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 transition hover:bg-rose-100">
                                    Delete
                                </button>
                            </form>
                            <button type="submit" form="update-template-{{ $template->id }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                                Save template
                            </button>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[2rem] border border-dashed border-slate-200 px-6 py-16 text-center text-sm text-slate-500">
                        No templates created yet.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-admin-layout>
