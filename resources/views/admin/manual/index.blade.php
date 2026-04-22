<x-admin-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-500">User Guide</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Guides</h1>
        </div>
    </x-slot>

    @include('manual.partials.index-content')
</x-admin-layout>
