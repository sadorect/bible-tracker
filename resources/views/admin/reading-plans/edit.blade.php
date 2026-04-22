<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Cohort Ops</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Edit {{ $readingPlan->name }}.</h1>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
                <form method="POST" action="{{ route('admin.reading-plans.update', $readingPlan) }}" class="space-y-8">
                    @csrf
                    @method('PUT')

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Plan setup</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Core details</h2>
                    </div>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Plan name</span>
                        <input type="text" name="name" id="name" value="{{ old('name', $readingPlan->name) }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        @error('name')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Plan type</span>
                            <select name="type" id="type" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500" required>
                                <option value="new_testament" {{ old('type', $readingPlan->type) === 'new_testament' ? 'selected' : '' }}>New Testament</option>
                                <option value="old_testament" {{ old('type', $readingPlan->type) === 'old_testament' ? 'selected' : '' }}>Old Testament</option>
                            </select>
                            @error('type')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Commencement date</span>
                            <input type="date" name="start_date" id="start_date" value="{{ old('start_date', $readingPlan->start_date?->format('Y-m-d')) }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            @error('start_date')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Lifecycle status</span>
                            <select name="lifecycle_status" id="lifecycle_status" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500" required>
                                @foreach($lifecycleStatuses as $value => $label)
                                    <option value="{{ $value }}" {{ old('lifecycle_status', $readingPlan->lifecycle_status) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('lifecycle_status')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>
                    </div>

                    <div class="grid gap-5 md:grid-cols-3">
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Chapters per day</span>
                            <input type="number" name="chapters_per_day" id="chapters_per_day" value="{{ old('chapters_per_day', $readingPlan->chapters_per_day) }}" min="1" max="100" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            @error('chapters_per_day')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Reading streak</span>
                            <input type="number" name="streak_days" id="streak_days" value="{{ old('streak_days', $readingPlan->streak_days) }}" min="1" max="365" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            @error('streak_days')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Break days</span>
                            <input type="number" name="break_days" id="break_days" value="{{ old('break_days', $readingPlan->break_days) }}" min="0" max="60" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            @error('break_days')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>
                    </div>

                    <div class="rounded-[1.5rem] bg-slate-50 p-5">
                        <p class="text-sm font-medium text-slate-700">Current cadence summary</p>
                        <p class="mt-2 text-lg font-semibold text-slate-900">{{ $readingPlan->cadence_description }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-500">This cohort is currently scheduled from {{ $readingPlan->start_date?->format('M d, Y') }} to {{ $readingPlan->end_date?->format('M d, Y') ?? 'TBD' }}.</p>
                        @if($readingPlan->readingProgress()->exists())
                            <p class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Cadence and stage changes are blocked once readers have started reporting progress. Create a fresh plan if you need a new structure.</p>
                        @endif
                    </div>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Description</span>
                        <textarea name="description" id="description" rows="4" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">{{ old('description', $readingPlan->description) }}</textarea>
                        @error('description')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Enrollment opens</span>
                            <input type="datetime-local" name="enrollment_starts_at" value="{{ old('enrollment_starts_at', optional($readingPlan->enrollment_starts_at)->format('Y-m-d\TH:i')) }}" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            <p class="mt-2 text-xs text-slate-500">Leave blank to allow enrollments as soon as this plan becomes visible.</p>
                            @error('enrollment_starts_at')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>

                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Enrollment closes</span>
                            <input type="datetime-local" name="enrollment_ends_at" value="{{ old('enrollment_ends_at', optional($readingPlan->enrollment_ends_at)->format('Y-m-d\TH:i')) }}" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            <p class="mt-2 text-xs text-slate-500">Leave blank to keep recruitment open while the plan is live.</p>
                            @error('enrollment_ends_at')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>
                    </div>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Additional information</span>
                        <textarea name="additional_info" id="additional_info" rows="5" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">{{ old('additional_info', $readingPlan->additional_info) }}</textarea>
                        @error('additional_info')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
                        <a href="{{ route('admin.reading-plans.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                            Back
                        </a>
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Update reading plan
                        </button>
                    </div>
                </form>
            </div>

            <aside class="space-y-6">
                <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Plan statistics</p>
                    <div class="mt-5 space-y-4">
                        <div class="rounded-[1.5rem] bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Users</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $readingPlan->users()->count() }}</p>
                        </div>
                        <div class="rounded-[1.5rem] bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Daily readings</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ $readingPlan->dailyReadings()->count() }}</p>
                        </div>
                        <div class="rounded-[1.5rem] bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Commencement</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ \Carbon\Carbon::parse($readingPlan->start_date)->format('M d, Y') }}</p>
                        </div>
                        <div class="rounded-[1.5rem] bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Lifecycle</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">{{ $readingPlan->lifecycle_status_label }}</p>
                        </div>
                        <div class="rounded-[1.5rem] bg-slate-50 p-4">
                            <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Enrollment window</p>
                            <p class="mt-2 text-sm font-semibold text-slate-900">
                                {{ $readingPlan->enrollment_starts_at?->format('M d, Y g:i A') ?? 'Open with status' }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Until {{ $readingPlan->enrollment_ends_at?->format('M d, Y g:i A') ?? 'status changes or admin closes it' }}
                            </p>
                        </div>
                    </div>
                </section>

                <section class="rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-sky-700 p-6 text-white shadow-2xl shadow-slate-900/15">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-sky-100">Training effect</p>
                    <h2 class="mt-3 text-2xl font-semibold">{{ $readingPlan->training_days }} training day(s)</h2>
                    <p class="mt-3 text-sm leading-6 text-slate-200">Reading starts on {{ $readingPlan->reading_start_date?->format('M d, Y') ?? 'the commencement date' }} once the assigned training journey is complete.</p>
                </section>
            </aside>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Guest enrollment</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Generate an enrollment link</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Links can be shared publicly, do not have a usage cap, and can be revoked at any time. Returning participants can use them to start fresh cycles on the same profile.</p>
                </div>

                @unless($readingPlan->acceptsEnrollment())
                    <div class="mt-4 rounded-[1.5rem] border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">
                        Enrollment links can only be created while the plan is visible and currently accepting enrollments.
                    </div>
                @endunless

                <form method="POST" action="{{ route('admin.reading-plans.invites.store', $readingPlan) }}" class="mt-6 grid gap-5 md:grid-cols-2">
                    @csrf

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Label</span>
                        <input type="text" name="label" value="{{ old('label') }}" placeholder="April outreach link" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        @error('label')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Expiry date and time</span>
                        <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        @error('expires_at')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <div class="md:col-span-2 flex justify-end">
                        <button type="submit" {{ $readingPlan->acceptsEnrollment() ? '' : 'disabled' }} class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300">
                            Generate enrollment link
                        </button>
                    </div>
                </form>
            </div>

            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Live links</p>
                        <h2 class="mt-2 text-2xl font-semibold text-slate-900">Enrollment links</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $readingPlan->invites->count() }} total</span>
                </div>

                <div class="mt-6 space-y-4">
                    @forelse($readingPlan->invites as $invite)
                        <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                            <div class="flex flex-col gap-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $invite->isUsable() ? 'bg-emerald-100 text-emerald-700' : ($invite->isRevoked() ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">
                                        {{ $invite->isUsable() ? 'Usable' : ($invite->isRevoked() ? 'Revoked' : 'Unavailable') }}
                                    </span>
                                    @if($invite->label)
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600">{{ $invite->label }}</span>
                                    @endif
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm text-slate-500">Created by {{ $invite->creator?->name ?? 'Unknown' }} on {{ $invite->created_at->format('M d, Y g:i A') }}</p>
                                    <p class="text-sm text-slate-500">Expires {{ $invite->expires_at?->format('M d, Y g:i A') ?? 'Never' }}</p>
                                    <input type="text" readonly value="{{ $invite->enrollmentUrl() }}" class="w-full rounded-2xl border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm shadow-slate-900/5">
                                </div>

                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <p class="text-sm text-slate-600">{{ $invite->participations()->count() }} participation cycle(s) started from this link.</p>
                                    @if(!$invite->isRevoked())
                                        <form method="POST" action="{{ route('admin.reading-plans.invites.revoke', [$readingPlan, $invite]) }}" onsubmit="return confirm('Revoke this enrollment link?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex rounded-2xl bg-rose-100 px-4 py-2.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-200">
                                                Revoke link
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-200 px-5 py-12 text-center text-sm text-slate-500">
                            No enrollment links generated yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Training library</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Add a training resource</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Each training item adds one day to the onboarding window, and each item can include a YouTube link, a PDF upload, or both.</p>
                </div>

                <form method="POST" action="{{ route('admin.reading-plans.training-resources.store', $readingPlan) }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                    @csrf

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Title</span>
                        <input type="text" name="title" id="title" value="{{ old('title') }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        @error('title')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-medium text-slate-700">Training day order</span>
                            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', $readingPlan->training_days + 1) }}" min="1" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            @error('sort_order')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </label>
                    </div>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">YouTube URL</span>
                        <input type="url" name="resource_url" id="resource_url" value="{{ old('resource_url') }}" placeholder="https://youtube.com/..." class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        @error('resource_url')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">PDF file</span>
                        <input type="file" name="resource_file" id="resource_file" accept="application/pdf" class="mt-2 block w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 file:mr-4 file:rounded-xl file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800">
                        @error('resource_file')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Description</span>
                        <textarea name="description" id="training_description" rows="4" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Add training resource
                        </button>
                    </div>
                </form>
            </div>

            <div class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Training schedule</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Current resources</h2>
                </div>

                <div class="mt-6 space-y-3">
                    @forelse($readingPlan->trainingResources as $resource)
                        <article class="rounded-[1.5rem] border border-slate-200 bg-slate-50 p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600">Day {{ $loop->iteration }}</span>
                                        <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-semibold text-sky-700">{{ $resource->type_label }}</span>
                                    </div>
                                    <p class="mt-3 text-sm font-semibold text-slate-900">{{ $resource->title }}</p>
                                    @if($resource->description)
                                        <p class="mt-2 text-sm text-slate-500">{{ $resource->description }}</p>
                                    @endif
                                    <div class="mt-3 flex flex-wrap gap-3">
                                        @if($resource->video_link)
                                            <a href="{{ $resource->video_link }}" target="_blank" rel="noopener noreferrer" class="inline-flex text-sm font-medium text-emerald-700 transition hover:text-emerald-800">
                                                Open video
                                            </a>
                                        @endif
                                        @if($resource->document_link)
                                            <a href="{{ $resource->document_link }}" target="_blank" rel="noopener noreferrer" class="inline-flex text-sm font-medium text-emerald-700 transition hover:text-emerald-800">
                                                Open PDF
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('admin.reading-plans.training-resources.destroy', [$readingPlan, $resource]) }}" onsubmit="return confirm('Remove this training resource?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex rounded-full bg-rose-100 px-3 py-1.5 text-sm font-semibold text-rose-700 transition hover:bg-rose-200">
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[1.5rem] border border-dashed border-slate-200 px-5 py-12 text-center text-sm text-slate-500">
                            No training resources have been added yet.
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section
            class="overflow-hidden rounded-[2rem] bg-white shadow-xl shadow-slate-900/5"
            data-table-columns="admin-reading-plan-participants"
            data-default-columns='{"current-day":false,"streak":false,"completion-rate":false,"status":true}'
            data-default-columns-md='{"current-day":true,"completion-rate":true}'
            data-default-columns-xl='{"streak":true}'
        >
            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Participants</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Users following this plan</h2>
                </div>
                <details class="relative">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm shadow-slate-900/5 transition hover:border-slate-300 hover:text-slate-900">
                        <i class="fas fa-table-columns text-slate-400"></i>
                        Display columns
                        <i class="fas fa-chevron-down text-xs text-slate-400"></i>
                    </summary>
                    <div class="absolute right-0 z-10 mt-3 w-72 rounded-3xl border border-slate-200 bg-white p-4 shadow-2xl shadow-slate-900/10">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Participant list</p>
                        <p class="mt-2 text-sm text-slate-500">Choose which progress details remain visible while you manage this plan.</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <label class="flex items-center gap-3 text-sm text-slate-700">
                                <input type="checkbox" data-column-toggle="current-day" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                Current day
                            </label>
                            <label class="flex items-center gap-3 text-sm text-slate-700">
                                <input type="checkbox" data-column-toggle="streak" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                Streak
                            </label>
                            <label class="flex items-center gap-3 text-sm text-slate-700">
                                <input type="checkbox" data-column-toggle="completion-rate" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                Completion rate
                            </label>
                            <label class="flex items-center gap-3 text-sm text-slate-700">
                                <input type="checkbox" data-column-toggle="status" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                Status
                            </label>
                        </div>
                        <button type="button" data-table-columns-reset class="mt-4 inline-flex items-center rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-white hover:text-slate-900">
                            Reset compact defaults
                        </button>
                    </div>
                </details>
            </div>

            <div class="overflow-x-auto" data-table-columns-root>
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">
                            <th class="px-6 py-4">Name</th>
                            <th class="px-6 py-4" data-column="current-day">Current day</th>
                            <th class="px-6 py-4" data-column="streak">Streak</th>
                            <th class="px-6 py-4" data-column="completion-rate">Completion rate</th>
                            <th class="px-6 py-4" data-column="status">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @forelse($readingPlan->users as $user)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-6 py-5 text-sm font-semibold text-slate-900">
                                    <div>
                                        <p>{{ $user->name }}</p>
                                        <div class="mt-2 flex flex-wrap gap-2 text-xs md:hidden">
                                            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 font-medium text-slate-700">Day {{ $user->pivot->current_day }}</span>
                                            <span class="inline-flex rounded-full bg-sky-100 px-2.5 py-1 font-medium text-sky-700">{{ number_format($user->pivot->completion_rate, 0) }}%</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 text-sm text-slate-600" data-column="current-day">Day {{ $user->pivot->current_day }}</td>
                                <td class="px-6 py-5 text-sm text-slate-600" data-column="streak">{{ $user->pivot->current_streak }} days</td>
                                <td class="px-6 py-5 text-sm text-slate-600" data-column="completion-rate">{{ number_format($user->pivot->completion_rate, 0) }}%</td>
                                <td class="px-6 py-5" data-column="status">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $user->pivot->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $user->pivot->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-14 text-center text-sm text-slate-500">
                                    No users are following this plan yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-admin-layout>
