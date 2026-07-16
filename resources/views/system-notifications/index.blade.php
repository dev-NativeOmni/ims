<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Notifikasi Sistem
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Kelola dan baca notifikasi internal IMS.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if (($summary['unread'] ?? 0) > 0)
                    <form method="POST" action="{{ route('system-notifications.mark-all-read') }}">
                        @csrf
                        @method('PATCH')

                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-lg border border-emerald-300 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">
                            Tandai Semua Dibaca
                        </button>
                    </form>
                @endif

                @if ($canManage)
                    <a href="{{ route('system-notifications.create') }}"
                       class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                        Buat Notifikasi
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Total</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ number_format($summary['total'] ?? 0) }}
                    </p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Belum Dibaca</p>
                    <p class="mt-2 text-3xl font-bold text-red-600">
                        {{ number_format($summary['unread'] ?? 0) }}
                    </p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Sudah Dibaca</p>
                    <p class="mt-2 text-3xl font-bold text-emerald-600">
                        {{ number_format($summary['read'] ?? 0) }}
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('system-notifications.index') }}"
                      class="grid grid-cols-1 gap-4 md:grid-cols-5">
                    @if ($canManage)
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">
                                Scope
                            </label>

                            <select name="scope"
                                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                                <option value="inbox" @selected($scope === 'inbox')>Inbox Saya</option>
                                <option value="all" @selected($scope === 'all')>Semua Notifikasi</option>
                            </select>
                        </div>
                    @endif

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">
                            Status
                        </label>

                        <select name="status"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Semua</option>
                            <option value="unread" @selected(request('status') === 'unread')>Belum Dibaca</option>
                            <option value="read" @selected(request('status') === 'read')>Sudah Dibaca</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">
                            Tipe
                        </label>

                        <select name="type"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Semua</option>
                            @foreach ($availableTypes as $value => $label)
                                <option value="{{ $value }}" @selected(request('type') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">
                            Role Target
                        </label>

                        <select name="target_role"
                                class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Semua</option>
                            @foreach ($availableRoles as $value => $label)
                                <option value="{{ $value }}" @selected(request('target_role') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit"
                                class="inline-flex w-full items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                            Filter
                        </button>

                        <a href="{{ route('system-notifications.index') }}"
                           class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                @forelse ($notifications as $notification)
                    <div class="border-b border-gray-100 p-5 last:border-b-0 {{ $notification->isUnread() ? 'bg-emerald-50/40' : 'bg-white' }}">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($notification->isUnread())
                                        <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">
                                            Baru
                                        </span>
                                    @endif

                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $notification->type_badge_class }}">
                                        {{ $notification->type_label }}
                                    </span>

                                    <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">
                                        {{ $notification->target_role_label }}
                                    </span>
                                </div>

                                <a href="{{ route('system-notifications.show', $notification) }}"
                                   class="mt-3 block text-base font-bold text-gray-900 hover:text-emerald-700">
                                    {{ $notification->title }}
                                </a>

                                <p class="mt-1 line-clamp-2 text-sm leading-6 text-gray-600">
                                    {{ $notification->message }}
                                </p>

                                <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                    <span>Dibuat: {{ $notification->created_at?->format('d M Y H:i') }}</span>
                                    <span>Penerima: {{ $notification->user?->name ?? '-' }}</span>

                                    @if ($notification->creator)
                                        <span>Oleh: {{ $notification->creator->name }}</span>
                                    @endif

                                    @if ($notification->read_at)
                                        <span>Dibaca: {{ $notification->read_at->format('d M Y H:i') }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('system-notifications.show', $notification) }}"
                                   class="btn-action-detail">
                                    Detail
                                </a>

                                @if ($canManage)
                                    <a href="{{ route('system-notifications.edit', $notification) }}"
                                       class="btn-action-edit">
                                        Edit
                                    </a>
                                @endif

                                @if ($notification->isUnread())
                                    <form method="POST" action="{{ route('system-notifications.mark-as-read', $notification) }}">
                                        @csrf
                                        @method('PATCH')

                                        <button type="submit"
                                                class="btn-action-complete">
                                            Dibaca
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <p class="text-base font-semibold text-gray-900">
                            Belum ada notifikasi.
                        </p>

                        <p class="mt-1 text-sm text-gray-600">
                            Buat notifikasi baru untuk mengirim pesan internal ke pengguna.
                        </p>
                    </div>
                @endforelse
            </div>

            @if ($notifications->hasPages())
                <div>
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>