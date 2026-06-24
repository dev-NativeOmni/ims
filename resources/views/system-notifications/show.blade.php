<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Detail Notifikasi
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Informasi lengkap notifikasi sistem.
                </p>
            </div>

            <a href="{{ route('system-notifications.index') }}"
               class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Kembali
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($systemNotification->isUnread())
                        <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold text-red-700">
                            Belum Dibaca
                        </span>
                    @else
                        <span class="inline-flex rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">
                            Sudah Dibaca
                        </span>
                    @endif

                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $systemNotification->type_badge_class }}">
                        {{ $systemNotification->type_label }}
                    </span>

                    <span class="inline-flex rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-700">
                        {{ $systemNotification->target_role_label }}
                    </span>
                </div>

                <h3 class="mt-4 text-2xl font-bold text-gray-900">
                    {{ $systemNotification->title }}
                </h3>

                <p class="mt-4 whitespace-pre-line text-sm leading-6 text-gray-700">
                    {{ $systemNotification->message }}
                </p>

                <div class="mt-6 grid grid-cols-1 gap-4 border-t border-gray-100 pt-6 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Penerima</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ $systemNotification->user?->name ?? '-' }}
                            @if ($systemNotification->user?->username)
                                · {{ $systemNotification->user->username }}
                            @endif
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Dibuat Oleh</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ $systemNotification->creator?->name ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Dibuat</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ $systemNotification->created_at?->format('d M Y H:i') ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Dibaca</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ $systemNotification->read_at?->format('d M Y H:i') ?? '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Published At</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ $systemNotification->published_at?->format('d M Y H:i') ?? 'Langsung aktif' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Expires At</p>
                        <p class="mt-1 text-sm font-medium text-gray-900">
                            {{ $systemNotification->expires_at?->format('d M Y H:i') ?? 'Tidak kedaluwarsa' }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    @if ($systemNotification->action_url)
                        <a href="{{ $systemNotification->action_url }}"
                           class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                            Buka Terkait
                        </a>
                    @endif

                    @if ($systemNotification->isUnread())
                        <form method="POST" action="{{ route('system-notifications.mark-as-read', $systemNotification) }}">
                            @csrf
                            @method('PATCH')

                            <button type="submit"
                                    class="inline-flex items-center justify-center rounded-lg border border-emerald-300 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">
                                Tandai Dibaca
                            </button>
                        </form>
                    @endif

                    @if ($canManage)
                        <a href="{{ route('system-notifications.edit', $systemNotification) }}"
                           class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Edit
                        </a>
                    @endif

                    <form method="POST"
                          action="{{ route('system-notifications.destroy', $systemNotification) }}"
                          onsubmit="return confirm('Hapus notifikasi ini?')">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-lg border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                            Hapus
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>