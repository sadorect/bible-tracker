<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600">Alerts</p>
            <h1 class="mt-1 text-2xl font-semibold text-slate-900">Keep up with reminders and updates.</h1>
        </div>
    </x-slot>

    @include('notifications.partials.list', ['notifications' => $notifications, 'unreadCount' => $unreadCount])
</x-app-layout>
