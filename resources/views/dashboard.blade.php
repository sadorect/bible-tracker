<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Daily Reading Journey</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Dashboard</h2>
        </div>
    </x-slot>

    @php
        $user = auth()->user();
        $activePlan = $user?->readingPlans()->where('user_reading_plans.is_active', true)->first();
        $currentStreak = $activePlan?->pivot->current_streak ?? 0;
        $completionRate = number_format($activePlan?->pivot->completion_rate ?? 0, 0);
        $cadenceLabel = $activePlan
            ? "{$activePlan->streak_days} reading days / {$activePlan->break_days} refresh day"
            : 'Join a reading plan to begin';
    @endphp

    <div class="space-y-6">
        <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-slate-950 via-slate-900 to-emerald-700 text-white shadow-2xl shadow-slate-900/15">
            <div class="grid gap-8 px-6 py-8 sm:px-8 lg:grid-cols-[minmax(0,1.5fr)_minmax(18rem,0.8fr)] lg:px-10 lg:py-10">
                <div class="space-y-5">
                    <div class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.28em] text-emerald-100">
                        Daily Reading Journey
                    </div>
                    <div class="space-y-3">
                        <h1 class="max-w-3xl text-3xl font-semibold tracking-tight sm:text-4xl">Read with rhythm, recover with purpose, and keep the whole group moving.</h1>
                        <p class="max-w-2xl text-sm leading-7 text-slate-200 sm:text-base">
                            Your schedule now follows the full ministry cadence: training first, ten focused reading days, then a refresh-and-prayer break before the next stretch begins.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3 text-sm text-slate-100">
                        <span class="rounded-full bg-white/10 px-4 py-2">Cadence: {{ $cadenceLabel }}</span>
                        <span class="rounded-full bg-white/10 px-4 py-2">
                            {{ $activePlan?->type_label ?? 'No active cohort yet' }}
                        </span>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
                    <div class="rounded-[1.75rem] border border-white/10 bg-white/10 p-5 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.24em] text-emerald-100">Current streak</p>
                        <p class="mt-3 text-3xl font-semibold">{{ $currentStreak }}</p>
                        <p class="mt-1 text-sm text-slate-200">Consistent reading days in a row.</p>
                    </div>
                    <div class="rounded-[1.75rem] border border-white/10 bg-white/10 p-5 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.24em] text-emerald-100">Completion</p>
                        <p class="mt-3 text-3xl font-semibold">{{ $completionRate }}%</p>
                        <p class="mt-1 text-sm text-slate-200">Measured against scheduled reading days.</p>
                    </div>
                    <div class="rounded-[1.75rem] border border-white/10 bg-white/10 p-5 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.24em] text-emerald-100">Focus</p>
                        <p class="mt-3 text-xl font-semibold">{{ $activePlan?->name ?? 'Choose a cohort' }}</p>
                        <p class="mt-1 text-sm text-slate-200">{{ $activePlan?->cadence_description ?? 'Training and daily reading details will appear here.' }}</p>
                    </div>
                </div>
            </div>
        </section>

        @livewire('dashboard')
    </div>
</x-app-layout>
