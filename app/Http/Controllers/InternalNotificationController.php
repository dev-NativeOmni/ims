<?php

namespace App\Http\Controllers;

use App\Models\InternalNotification;
use App\Services\InternalNotificationSyncService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InternalNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $notifications = $user->internalNotifications()
            ->latest()
            ->paginate(15);

        $unreadCount = $user->internalNotifications()
            ->unread()
            ->count();

        return view('internal-notifications.index', compact(
            'notifications',
            'unreadCount'
        ));
    }

    public function markAsRead(Request $request, InternalNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);

        if (! $notification->read_at) {
            $notification->update([
                'read_at' => now(),
            ]);
        }

        return redirect($notification->action_url ?: route('notifications.index'));
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()
            ->internalNotifications()
            ->unread()
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('notifications.index')
            ->with('success', 'Semua notifikasi berhasil ditandai sudah dibaca.');
    }

    public function destroy(Request $request, InternalNotification $notification): RedirectResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);

        $notification->delete();

        return redirect()
            ->route('notifications.index')
            ->with('success', 'Notifikasi berhasil dihapus.');
    }

    public function sync(Request $request, InternalNotificationSyncService $service): RedirectResponse
    {
        abort_unless(
            $request->user()?->hasAnyRole(['super_admin', 'admin', 'teacher']),
            403
        );

        $result = $service->syncAll();

        $total = array_sum($result);

        return redirect()
            ->route('notifications.index')
            ->with('success', "Sinkronisasi selesai. {$total} notifikasi diproses.");
    }
}