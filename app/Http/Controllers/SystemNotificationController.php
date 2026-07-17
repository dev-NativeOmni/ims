<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class SystemNotificationController extends Controller
{
    private array $availableTypes = [
        'info' => 'Informasi',
        'success' => 'Sukses',
        'warning' => 'Peringatan',
        'danger' => 'Bahaya',
    ];

    private array $availableRoles = [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'teacher' => 'Guru',
        'parent' => 'Orangtua',
        'student' => 'Santri',
    ];

    public function index(Request $request): View
    {
        $user = $request->user();
        $canManage = $this->canManage($user);

        $scope = $request->query('scope', 'inbox');

        $notificationsQuery = SystemNotification::query()
            ->with([
                'user.role',
                'creator',
            ])
            ->latest('created_at')
            ->latest('id');

        if (! $canManage || $scope !== 'all') {
            $notificationsQuery->where('user_id', $user->id);
        }

        if ($request->query('status') === 'unread') {
            $notificationsQuery->unread();
        }

        if ($request->query('status') === 'read') {
            $notificationsQuery->read();
        }

        if ($request->filled('type')) {
            $notificationsQuery->where('type', $request->query('type'));
        }

        if ($request->filled('target_role')) {
            $notificationsQuery->where('target_role', $request->query('target_role'));
        }

        $notifications = $notificationsQuery
            ->paginate(15)
            ->withQueryString();

        $summaryBaseQuery = SystemNotification::query();

        if (! $canManage || $scope !== 'all') {
            $summaryBaseQuery->where('user_id', $user->id);
        }

        $summary = [
            'total' => (clone $summaryBaseQuery)->count(),
            'unread' => (clone $summaryBaseQuery)->unread()->count(),
            'read' => (clone $summaryBaseQuery)->read()->count(),
        ];

        return view('system-notifications.index', [
            'notifications' => $notifications,
            'summary' => $summary,
            'availableTypes' => $this->availableTypes,
            'availableRoles' => $this->availableRoles,
            'canManage' => $canManage,
            'scope' => $scope,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($this->canManage($request->user()), 403);

        return view('system-notifications.create', [
            'availableTypes' => $this->availableTypes,
            'availableRoles' => $this->availableRoles,
            'users' => $this->usersForSelect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);

        $validated = $request->validate([
            'target_mode' => ['required', Rule::in(['all', 'role', 'user'])],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'target_role' => ['nullable', Rule::in(array_keys($this->availableRoles))],
            'type' => ['required', Rule::in(array_keys($this->availableTypes))],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'action_url' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:published_at'],
        ]);

        $recipients = $this->resolveRecipients($validated);

        if ($recipients->isEmpty()) {
            return back()
                ->withInput()
                ->with('error', 'Tidak ada penerima yang cocok.');
        }

        foreach ($recipients as $recipient) {
            SystemNotification::query()->create([
                'user_id' => $recipient->id,
                'created_by' => $request->user()->id,
                'title' => $validated['title'],
                'message' => $validated['message'],
                'type' => $validated['type'],
                'target_role' => $validated['target_mode'] === 'role'
                    ? $validated['target_role']
                    : $recipient->role?->name,
                'action_url' => $validated['action_url'] ?? null,
                'is_read' => false,
                'read_at' => null,
                'published_at' => $validated['published_at'] ?? null,
                'expires_at' => $validated['expires_at'] ?? null,
            ]);
        }

        return redirect()
            ->route('system-notifications.index', ['scope' => 'all'])
            ->with('success', 'Notifikasi berhasil dikirim ke '.$recipients->count().' pengguna.');
    }

    public function show(Request $request, SystemNotification $systemNotification): View
    {
        $this->authorizeAccess($request, $systemNotification);

        $systemNotification->load([
            'user.role',
            'creator',
        ]);

        if ($systemNotification->user_id === $request->user()->id && $systemNotification->isUnread()) {
            $systemNotification->markAsRead();
        }

        return view('system-notifications.show', [
            'systemNotification' => $systemNotification,
            'canManage' => $this->canManage($request->user()),
        ]);
    }

    public function edit(Request $request, SystemNotification $systemNotification): View
    {
        abort_unless($this->canManage($request->user()), 403);

        $systemNotification->load([
            'user.role',
            'creator',
        ]);

        return view('system-notifications.edit', [
            'systemNotification' => $systemNotification,
            'availableTypes' => $this->availableTypes,
            'availableRoles' => $this->availableRoles,
            'users' => $this->usersForSelect(),
        ]);
    }

    public function update(Request $request, SystemNotification $systemNotification): RedirectResponse
    {
        abort_unless($this->canManage($request->user()), 403);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'target_role' => ['nullable', Rule::in(array_keys($this->availableRoles))],
            'type' => ['required', Rule::in(array_keys($this->availableTypes))],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'action_url' => ['nullable', 'string', 'max:255'],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:published_at'],
        ]);

        $systemNotification->update([
            'user_id' => $validated['user_id'],
            'target_role' => $validated['target_role'] ?? null,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'message' => $validated['message'],
            'action_url' => $validated['action_url'] ?? null,
            'published_at' => $validated['published_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return redirect()
            ->route('system-notifications.show', $systemNotification)
            ->with('success', 'Notifikasi berhasil diperbarui.');
    }

    public function markAsRead(Request $request, SystemNotification $systemNotification): RedirectResponse
    {
        $this->authorizeAccess($request, $systemNotification);

        $systemNotification->markAsRead();

        return back()->with('success', 'Notifikasi ditandai sudah dibaca.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        SystemNotification::query()
            ->where('user_id', $request->user()->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Semua notifikasi kamu ditandai sudah dibaca.');
    }

    public function destroy(Request $request, SystemNotification $systemNotification): RedirectResponse
    {
        $this->authorizeAccess($request, $systemNotification);

        $systemNotification->delete();

        return redirect()
            ->route('system-notifications.index')
            ->with('success', 'Notifikasi berhasil dihapus.');
    }

    private function resolveRecipients(array $validated): Collection
    {
        $query = User::query()
            ->with('role')
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhereNull('is_active');
            });

        if ($validated['target_mode'] === 'user') {
            return $query
                ->whereKey($validated['user_id'])
                ->get();
        }

        if ($validated['target_mode'] === 'role') {
            return $query
                ->whereHas('role', function ($roleQuery) use ($validated) {
                    $roleQuery->where('name', $validated['target_role']);
                })
                ->get();
        }

        return $query->get();
    }

    private function usersForSelect(): Collection
    {
        return User::query()
            ->with('role')
            ->orderBy('name')
            ->get();
    }

    private function authorizeAccess(Request $request, SystemNotification $notification): void
    {
        $user = $request->user();

        if ($this->canManage($user)) {
            return;
        }

        abort_unless($notification->user_id === $user->id, 403);
    }

    private function canManage(User $user): bool
    {
        return $user->hasAnyRole([
            'super_admin',
            'admin',
        ]);
    }
}
