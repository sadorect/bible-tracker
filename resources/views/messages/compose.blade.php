<x-dynamic-component :component="$layoutComponent">
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Message Centre</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ $replyThread ? 'Reply to thread' : 'Compose message' }}</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        @include('messages.partials.nav')

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
            @if($replyThread)
                <div class="mb-8 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Replying in thread</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ $replyThread->subject }}</p>
                    <p class="mt-2 text-sm text-slate-600">Started by {{ $replyThread->sender->name }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('messages.preview') }}" class="space-y-8">
                @csrf

                @if($replyThread)
                    <input type="hidden" name="reply_to" value="{{ $replyThread->id }}">
                @endif

                <div class="grid gap-5 md:grid-cols-2">
                    @if(!$replyThread && $canSendDownward && $canSendUpward)
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Direction</span>
                            <select name="direction" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                                <option value="downward" {{ $formData['direction'] === 'downward' ? 'selected' : '' }}>Downward</option>
                                <option value="upward" {{ $formData['direction'] === 'upward' ? 'selected' : '' }}>Upward</option>
                            </select>
                        </label>
                    @else
                        <input type="hidden" name="direction" value="{{ $formData['direction'] }}">
                    @endif

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Template</span>
                        <select name="template_id" id="template_id" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            <option value="">Ad-hoc message</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" data-subject="{{ e($template->subject_template) }}" data-body="{{ e($template->body_template) }}" {{ (string) $formData['template_id'] === (string) $template->id ? 'selected' : '' }}>
                                    {{ $template->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Subject</span>
                    <input type="text" name="subject" id="message_subject" value="{{ old('subject', $formData['subject']) }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                    @error('subject')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">Message</span>
                    <textarea name="body" id="message_body" rows="7" required class="mt-2 w-full rounded-[1.5rem] border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">{{ old('body', $formData['body']) }}</textarea>
                    @error('body')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                @if(!$replyThread && $formData['direction'] === 'downward')
                    <div class="border-t border-slate-200 pt-8">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Audience filters</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Choose who receives this</h2>

                        <div class="mt-6 grid gap-5 md:grid-cols-2">
                            <label class="block md:col-span-2">
                                <span class="text-sm font-medium text-slate-700">Hierarchy branches</span>
                                <select name="hierarchy_ids[]" multiple class="mt-2 w-full rounded-[1.5rem] border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                                    @foreach($hierarchies as $hierarchy)
                                        <option value="{{ $hierarchy->id }}" {{ in_array($hierarchy->id, old('hierarchy_ids', $formData['hierarchy_ids'] ?? [])) ? 'selected' : '' }}>
                                            {{ ucfirst($hierarchy->type) }} · {{ $hierarchy->displayPath() }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">Roles</span>
                                <select name="roles[]" multiple class="mt-2 w-full rounded-[1.5rem] border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                                    @foreach($roles as $value => $label)
                                        <option value="{{ $value }}" {{ in_array($value, old('roles', $formData['roles'] ?? [])) ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">Active state</span>
                                <select name="active_state" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                                    <option value="">All users</option>
                                    <option value="active" {{ ($formData['active_state'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ ($formData['active_state'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </label>

                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">Active plan</span>
                                <select name="active_plan_id" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                                    <option value="">Any active plan</option>
                                    @foreach($readingPlans as $readingPlan)
                                        <option value="{{ $readingPlan->id }}" {{ (string) ($formData['active_plan_id'] ?? '') === (string) $readingPlan->id ? 'selected' : '' }}>{{ $readingPlan->name }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">Plan type</span>
                                <select name="plan_type" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                                    <option value="">Any type</option>
                                    @foreach($planTypes as $type => $configuration)
                                        <option value="{{ $type }}" {{ ($formData['plan_type'] ?? '') === $type ? 'selected' : '' }}>{{ $configuration['label'] }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">Training status</span>
                                <select name="training_status" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                                    <option value="">Any training state</option>
                                    @foreach($trainingStatuses as $value => $label)
                                        <option value="{{ $value }}" {{ ($formData['training_status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="block">
                                <span class="text-sm font-medium text-slate-700">Pace status</span>
                                <select name="pace_status" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                                    <option value="">Any pace</option>
                                    @foreach($paceStatuses as $value => $label)
                                        <option value="{{ $value }}" {{ ($formData['pace_status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </div>
                @endif

                <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-900">
                        Preview recipients
                    </button>
                    @if($previewRecipients->isNotEmpty())
                        <button type="submit" formaction="{{ route('messages.store') }}" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Send message
                        </button>
                    @endif
                </div>

                @if($previewRecipients->isNotEmpty())
                    <div class="border-t border-slate-200 pt-8">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Resolved audience</p>
                                <h2 class="mt-2 text-2xl font-semibold text-slate-900">{{ $previewRecipients->count() }} recipients</h2>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-3">
                            @foreach($previewRecipients as $recipient)
                                <label class="flex items-start gap-4 rounded-[1.5rem] border border-slate-200 bg-slate-50 p-4">
                                    <input type="checkbox" name="recipient_ids[]" value="{{ $recipient->id }}" {{ in_array($recipient->id, $selectedRecipientIds, true) ? 'checked' : '' }} class="mt-1 rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ $recipient->name }}</p>
                                        <p class="mt-1 text-sm text-slate-500">{{ $recipient->email }}</p>
                                        <div class="mt-2 flex flex-wrap gap-2 text-xs text-slate-500">
                                            <span class="rounded-full bg-white px-3 py-1">{{ $recipient->roleLabel() }}</span>
                                            @if($recipient->hierarchy)
                                                <span class="rounded-full bg-white px-3 py-1">{{ $recipient->hierarchy->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            </form>
        </section>

        <aside class="space-y-6">
            <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Variables</p>
                <h2 class="mt-2 text-2xl font-semibold text-slate-900">Available merge fields</h2>
                <div class="mt-4 space-y-3 text-sm text-slate-600">
                    @foreach($availableVariables as $key => $label)
                        <div class="rounded-[1.25rem] bg-slate-50 px-4 py-3">
                            <p class="font-mono text-xs text-slate-900">&#123;&#123; {{ $key }} &#125;&#125;</p>
                            <p class="mt-1">{{ $label }}</p>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-700 p-6 text-white shadow-2xl shadow-slate-900/15">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-100">Messaging note</p>
                <p class="mt-3 text-sm leading-6 text-slate-200">Recipients are resolved against live hierarchy rules every time you preview or send, so people outside your allowed branch are excluded automatically.</p>
            </section>
        </aside>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const templateSelect = document.getElementById('template_id');
                const subjectInput = document.getElementById('message_subject');
                const bodyInput = document.getElementById('message_body');

                if (!templateSelect || !subjectInput || !bodyInput) {
                    return;
                }

                templateSelect.addEventListener('change', () => {
                    const selected = templateSelect.options[templateSelect.selectedIndex];

                    if (!selected || !selected.dataset.subject) {
                        return;
                    }

                    if (subjectInput.value.trim() === '' || confirm('Replace the current subject and body with the selected template?')) {
                        subjectInput.value = selected.dataset.subject;
                        bodyInput.value = selected.dataset.body || '';
                    }
                });
            });
        </script>
    @endpush
</x-dynamic-component>
