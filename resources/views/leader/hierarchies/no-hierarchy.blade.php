<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-semibold text-slate-900">My Hierarchy</h1>
    </x-slot>

    <div class="flex flex-col items-center justify-center py-24 text-center">
        <span class="flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100">
            <i class="fas fa-sitemap text-2xl text-slate-400"></i>
        </span>
        <h2 class="mt-6 text-xl font-semibold text-slate-900">No hierarchy assigned</h2>
        <p class="mt-2 max-w-sm text-sm text-slate-500">
            You haven't been assigned as the leader of any hierarchy group yet. Contact an admin to get set up.
        </p>
    </div>
</x-app-layout>
