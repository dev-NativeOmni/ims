<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SystemNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->systemNotifications()
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $unreadCount = $request->user()
            ->unreadSystemNotifications()
            ->count();

        return view('system-notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function markAsRead(Request $request, SystemNotification $systemNotification): RedirectResponse
    {
        $this->ensureOwner($request, $systemNotification);

        $systemNotification->markAsRead();

        if ($systemNotification->action_url) {
            return redirect()->to($systemNotification->action_url);
        }

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()
            ->unreadSystemNotifications()
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    public function destroy(Request $request, SystemNotification $systemNotification): RedirectResponse
    {
        $this->ensureOwner($request, $systemNotification);

        $systemNotification->delete();

        return back()->with('success', 'Notifikasi berhasil dihapus.');
    }

    private function ensureOwner(Request $request, SystemNotification $systemNotification): void
    {
        abort_unless(
            (int) $systemNotification->user_id === (int) $request->user()->id,
            403
        );
    }
}