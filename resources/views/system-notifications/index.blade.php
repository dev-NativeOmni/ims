<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Notifikasi
            </h2>
            <p class="text-sm text-gray-600">
                Daftar pemberitahuan sistem HafizPlus.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">
                            Pusat Notifikasi
                        </h3>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $unreadCount }} notifikasi belum dibaca.
                        </p>
                    </div>

                    @if ($unreadCount > 0)
                        <form method="POST" action="{{ route('system-notifications.read-all') }}">
                            @csrf
                            @method('PATCH')

                            <button type="submit"
                                    class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-800">
                                Tandai Semua Dibaca
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                @forelse ($notifications as $notification)
                    <div class="p-5 border-b border-gray-100 {{ $notification->is_unread ? 'bg-blue-50' : 'bg-white' }}">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    @if ($notification->is_unread)
                                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-blue-600"></span>
                                    @endif

                                    <h4 class="font-semibold text-gray-900">
                                        {{ $notification->title }}
                                    </h4>

                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">
                                        {{ ucfirst(str_replace('_', ' ', $notification->type)) }}
                                    </span>
                                </div>

                                @if ($notification->message)
                                    <p class="text-sm text-gray-700">
                                        {{ $notification->message }}
                                    </p>
                                @endif

                                <p class="text-xs text-gray-500">
                                    {{ $notification->created_at?->format('d M Y H:i') }}
                                    @if ($notification->read_at)
                                        · Dibaca {{ $notification->read_at->format('d M Y H:i') }}
                                    @endif
                                </p>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                @if ($notification->is_unread)
                                    <form method="POST" action="{{ route('system-notifications.read', $notification) }}">
                                        @csrf
                                        @method('PATCH')

                                        <button type="submit"
                                                class="rounded-lg border border-blue-200 bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">
                                            Baca
                                        </button>
                                    </form>
                                @endif

                                @if ($notification->action_url)
                                    <a href="{{ $notification->action_url }}"
                                       class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                        Buka
                                    </a>
                                @endif

                                <form method="POST" action="{{ route('system-notifications.destroy', $notification) }}"
                                      onsubmit="return confirm('Hapus notifikasi ini?')">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                            class="rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-10 text-center">
                        <p class="text-sm font-medium text-gray-900">
                            Belum ada notifikasi.
                        </p>
                        <p class="text-sm text-gray-600 mt-1">
                            Notifikasi sistem akan muncul di halaman ini.
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