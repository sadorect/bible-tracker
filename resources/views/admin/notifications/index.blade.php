<x-admin-layout title="Alerts" eyebrow="Alerts">

    @include('notifications.partials.list', ['notifications' => $notifications, 'unreadCount' => $unreadCount])
</x-admin-layout>
