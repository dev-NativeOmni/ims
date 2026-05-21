<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Notifikasi
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Pantau target terlambat, hafalan perlu perbaikan, dan murajaah yang perlu tindak lanjut.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('system-notifications.refresh') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                        Refresh Notifikasi
                    </button>
                </form>

                <form method="POST" action="{{ route('system-notifications.mark-all-as-read') }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                            class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Tandai Semua Dibaca
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Total</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $summary['total'] }}</p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Belum Dibaca</p>
                    <p class="mt-2 text-3xl font-bold text-red-600">{{ $summary['unread'] }}</p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Target Terlambat</p>
                    <p class="mt-2 text-3xl font-bold text-orange-600">{{ $summary['target_overdue'] }}</p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Hafalan</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $summary['hafalan_attention'] }}</p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Murajaah</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $summary['murajaah_attention'] }}</p>
                </div>
            </div>

            <div class="rounded-xl bg-white shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">
                        Daftar Notifikasi
                    </h3>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse ($notifications as $notification)
                        @php
                            $severityClass = match ($notification->severity) {
                                'danger' => 'bg-red-50 text-red-700 border-red-200',
                                'warning' => 'bg-orange-50 text-orange-700 border-orange-200',
                                'success' => 'bg-green-50 text-green-700 border-green-200',
                                default => 'bg-blue-50 text-blue-700 border-blue-200',
                            };
                        @endphp

                        <div class="px-6 py-5 {{ $notification->read_at ? 'bg-white' : 'bg-emerald-50/40' }}">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $severityClass }}">
                                            {{ $notification->type_label }}
                                        </span>

                                        @if (! $notification->read_at)
                                            <span class="inline-flex rounded-full bg-red-600 px-2 py-1 text-xs font-semibold text-white">
                                                Baru
                                            </span>
                                        @endif
                                    </div>

                                    <div>
                                        <h4 class="font-semibold text-gray-900">
                                            {{ $notification->title }}
                                        </h4>

                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ $notification->message }}
                                        </p>
                                    </div>

                                    <p class="text-xs text-gray-400">
                                        Dibuat: {{ $notification->created_at?->format('d M Y H:i') }}
                                        @if ($notification->read_at)
                                            · Dibaca: {{ $notification->read_at?->format('d M Y H:i') }}
                                        @endif
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center justify-start gap-2 lg:justify-end">
                                    @if ($notification->action_url)
                                        <a href="{{ $notification->action_url }}"
                                           class="inline-flex items-center rounded-lg bg-gray-900 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-700">
                                            Buka
                                        </a>
                                    @endif

                                    @if (! $notification->read_at)
                                        <form method="POST" action="{{ route('system-notifications.mark-as-read', $notification) }}">
                                            @csrf
                                            @method('PATCH')

                                            <button type="submit"
                                                    class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                                                Tandai Dibaca
                                            </button>
                                        </form>
                                    @endif

                                    <form method="POST" action="{{ route('system-notifications.destroy', $notification) }}"
                                          onsubmit="return confirm('Hapus notifikasi ini?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="inline-flex items-center rounded-lg border border-red-300 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-10 text-center text-gray-500">
                            Belum ada notifikasi. Klik <strong>Refresh Notifikasi</strong> untuk memeriksa target dan catatan terbaru.
                        </div>
                    @endforelse
                </div>

                <div class="border-t border-gray-100 px-6 py-4">
                    {{ $notifications->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>