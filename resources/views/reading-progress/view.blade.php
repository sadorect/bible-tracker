<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Progress Center</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Reading Progress Details</h2>
        </div>
    </x-slot>

    <div class="space-y-6">
        <section class="rounded-[2rem] bg-white p-6 shadow-xl shadow-slate-900/5 lg:p-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">Detailed breakdown</p>
                    <h3 class="mt-2 text-2xl font-semibold text-slate-900">Track every reading day, break day, and completion signal.</h3>
                </div>
                <a href="{{ route('reading.progress') }}" class="inline-flex items-center justify-center rounded-2xl border border-stone-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-stone-50">
                    &larr; Back to all reading plans
                </a>
            </div>
        </section>

        @livewire('reading-plan-progress', ['planId' => $planId])
    </div>
</x-app-layout>
