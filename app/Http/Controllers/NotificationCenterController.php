<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationCenterController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->orderByRaw('case when read_at is null then 0 else 1 end')
            ->latest()
            ->paginate(20);

        $view = $user->canAccessAdminPanel()
            ? 'admin.notifications.index'
            : 'notifications.index';

        return view($view, [
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->whereKey($notification)->firstOrFail();

        if (! $record->read_at) {
            $record->markAsRead();
        }

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }
}
