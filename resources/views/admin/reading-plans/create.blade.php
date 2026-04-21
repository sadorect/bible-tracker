<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Cohort Ops</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Create a reading plan.</h1>
        </div>
    </x-slot>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 sm:p-8">
            <form
                method="POST"
                action="{{ route('admin.reading-plans.store') }}"
                class="space-y-8"
                x-data="{
                    defaults: @js($typeDefaults),
                    selectedType: @js(old('type', \App\Models\ReadingPlan::TYPE_NEW_TESTAMENT)),
                    chapters: @js(old('chapters_per_day', $typeDefaults[\App\Models\ReadingPlan::TYPE_NEW_TESTAMENT]['chapters_per_day'])),
                    streak: @js(old('streak_days', $typeDefaults[\App\Models\ReadingPlan::TYPE_NEW_TESTAMENT]['streak_days'])),
                    breaks: @js(old('break_days', $typeDefaults[\App\Models\ReadingPlan::TYPE_NEW_TESTAMENT]['break_days'])),
                    applyRecommendedCadence() {
                        const preset = this.defaults[this.selectedType];

                        if (!preset) {
                            return;
                        }

                        this.chapters = preset.chapters_per_day;
                        this.streak = preset.streak_days;
                        this.breaks = preset.break_days;
                    },
                }"
            >
                @csrf

                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Plan setup</p>
                    <h2 class="mt-2 text-2xl font-semibold text-slate-900">Core details</h2>
                </div>

                <div class="grid gap-5">
                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Plan name</span>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        @error('name')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Plan type</span>
                        <select name="type" id="type" x-model="selectedType" @change="applyRecommendedCadence()" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                            <option value="new_testament">New Testament</option>
                            <option value="old_testament">Old Testament</option>
                        </select>
                        @error('type')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Description</span>
                        <textarea name="description" id="description" rows="4" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>
                </div>

                <div class="rounded-[1.75rem] border border-emerald-200 bg-emerald-50 p-5 text-sm text-emerald-900">
                    <p class="font-semibold">Cadence is fully configurable.</p>
                    <p class="mt-2 leading-6">Recommended values load with the selected stage, but you can change the chapter count, reading streak, and break days for any cohort.</p>
                </div>

                <div class="grid gap-5 md:grid-cols-3">
                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Chapters per day</span>
                        <input type="number" name="chapters_per_day" id="chapters_per_day" x-model="chapters" min="1" max="100" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        @error('chapters_per_day')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Reading streak</span>
                        <input type="number" name="streak_days" id="streak_days" x-model="streak" min="1" max="365" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        @error('streak_days')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Break days</span>
                        <input type="number" name="break_days" id="break_days" x-model="breaks" min="0" max="60" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        @error('break_days')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <label class="block">
                        <span class="text-sm font-medium text-slate-700">Commencement date</span>
                        <input type="date" name="start_date" id="start_date" value="{{ old('start_date', date('Y-m-d')) }}" required class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">
                        @error('start_date')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </label>

                    <div class="rounded-[1.5rem] bg-slate-50 p-5">
                        <label class="flex items-center gap-3">
                            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active') ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <span class="text-sm font-medium text-slate-700">Mark this plan as active immediately</span>
                        </label>
                        @error('is_active')
                            <p class="mt-3 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <label class="block border-t border-slate-200 pt-8">
                    <span class="text-sm font-medium text-slate-700">Additional information</span>
                    <textarea name="additional_info" id="additional_info" rows="5" class="mt-2 w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-900 shadow-sm shadow-slate-900/5 focus:border-emerald-500 focus:bg-white focus:ring-emerald-500">{{ old('additional_info') }}</textarea>
                    @error('additional_info')
                        <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </label>

                <div class="flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:justify-end">
                    <a href="{{ route('admin.reading-plans.index') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                        Create reading plan
                    </button>
                </div>
            </form>
        </section>

        <aside class="space-y-6">
            <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Before you publish</p>
                <div class="mt-4 space-y-4 text-sm leading-6 text-slate-600">
                    <p>Choose the stage carefully. The generator still uses the correct testament, but the pace and break rhythm are now yours to set.</p>
                    <p>Training resources can be added after the plan is created. Each item adds one day to the training window and can include both a YouTube video and a PDF.</p>
                    <p>The plan’s reading start date will automatically shift to begin after training is complete.</p>
                </div>
            </section>

            <section class="rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-700 p-6 text-white shadow-2xl shadow-slate-900/15">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-100">Cadence reminder</p>
                <h2 class="mt-3 text-2xl font-semibold">Shape the rhythm for each cohort.</h2>
                <p class="mt-3 text-sm leading-6 text-slate-200">The generator respects your chapter load, reading streak, break length, and the extra days introduced by training resources.</p>
            </section>
        </aside>
    </div>
</x-admin-layout>
