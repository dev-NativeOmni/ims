<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use App\Services\InternalNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SystemNotificationController extends Controller
{
    public function __construct(
        private readonly InternalNotificationService $notificationService
    ) {
        //
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $notifications = $user->systemNotifications()
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('system-notifications.index', [
            'notifications' => $notifications,
            'summary' => [
                'total' => $user->systemNotifications()->count(),
                'unread' => $user->unreadSystemNotifications()->count(),
                'target_overdue' => $user->systemNotifications()->where('type', 'target_overdue')->count(),
                'hafalan_attention' => $user->systemNotifications()->where('type', 'hafalan_attention')->count(),
                'murajaah_attention' => $user->systemNotifications()->where('type', 'murajaah_attention')->count(),
            ],
        ]);
    }

    public function refresh(Request $request): RedirectResponse
    {
        $created = $this->notificationService->generateForUser($request->user());

        return redirect()
            ->route('system-notifications.index')
            ->with('success', $created . ' notifikasi baru berhasil dibuat.');
    }

    public function markAsRead(
        Request $request,
        SystemNotification $systemNotification
    ): RedirectResponse {
        abort_if(
            (int) $systemNotification->user_id !== (int) $request->user()->id,
            403
        );

        $systemNotification->markAsRead();

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()
            ->unreadSystemNotifications()
            ->update([
                'read_at' => now(),
            ]);

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    public function destroy(
        Request $request,
        SystemNotification $systemNotification
    ): RedirectResponse {
        abort_if(
            (int) $systemNotification->user_id !== (int) $request->user()->id,
            403
        );

        $systemNotification->delete();

        return back()->with('success', 'Notifikasi berhasil dihapus.');
    }
}