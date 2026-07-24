<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight flex items-center gap-2">
                <span>🛡️</span> Dashboard Koordinator Ketahanan Sekolah (Tanse)
            </h2>
            <p class="text-sm text-gray-500 dark:text-zinc-400">
                Monitoring poin kedisiplinan, pelanggaran tata tertib, keterlambatan, atribut, dan prestasi murid.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Metric Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm hover:shadow-md transition rounded-2xl p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-zinc-400">Total Pelanggaran (Bulan Ini)</span>
                        <span class="p-2 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-xl text-lg">⚠️</span>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">{{ $stats['total_violations_month'] }}</p>
                    <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 font-semibold">Total Poin: -{{ $stats['total_violation_points_month'] }} Poin</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm hover:shadow-md transition rounded-2xl p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-zinc-400">Keterlambatan</span>
                        <span class="p-2 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xl text-lg">⏰</span>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">{{ $stats['lateness_count_month'] }}</p>
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1 font-semibold">Pelanggaran Keterlambatan</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm hover:shadow-md transition rounded-2xl p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-zinc-400">Atribut / Seragam</span>
                        <span class="p-2 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-xl text-lg">👔</span>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">{{ $stats['attribute_count_month'] }}</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1 font-semibold">Pelanggaran Atribut</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm hover:shadow-md transition rounded-2xl p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-zinc-400">Prestasi / Penghargaan</span>
                        <span class="p-2 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl text-lg">🏆</span>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">{{ $stats['rewards_count_month'] }}</p>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-semibold">Reward Poin Positif</p>
                </div>
            </div>

            {{-- Quick Action Shortcuts --}}
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 border-b pb-3 dark:border-zinc-800 flex items-center gap-2">
                    <span>⚡</span> Akses Cepat Menu Tanse
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <a href="{{ route('student-points.create') }}" class="p-4 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/50 rounded-2xl hover:bg-rose-100 transition text-center group">
                        <span class="text-2xl block mb-1">➕</span>
                        <span class="text-xs font-bold text-rose-900 dark:text-rose-300">Catat Poin Baru</span>
                    </a>
                    <a href="{{ route('student-points.index') }}" class="p-4 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-900/50 rounded-2xl hover:bg-indigo-100 transition text-center group">
                        <span class="text-2xl block mb-1">📋</span>
                        <span class="text-xs font-bold text-indigo-900 dark:text-indigo-300">Daftar Poin & Disiplin</span>
                    </a>
                    <a href="{{ route('student-points.chart') }}" class="p-4 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 rounded-2xl hover:bg-amber-100 transition text-center group">
                        <span class="text-2xl block mb-1">📊</span>
                        <span class="text-xs font-bold text-amber-900 dark:text-amber-300">Grafik Perkembangan Tanse</span>
                    </a>
                </div>
            </div>

            {{-- Recent Violations Feed --}}
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 border-b pb-3 dark:border-zinc-800">
                    📋 Catatan Kedisiplinan Terbaru
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-zinc-400">
                        <thead class="bg-gray-50 dark:bg-zinc-800 text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider">
                            <tr>
                                <th class="p-3">Tanggal</th>
                                <th class="p-3">Murid</th>
                                <th class="p-3">Tipe</th>
                                <th class="p-3">Keterangan</th>
                                <th class="p-3">Poin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @forelse($recentPoints as $point)
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition">
                                    <td class="p-3 font-medium text-gray-900 dark:text-white">{{ $point->date?->format('d/m/Y') ?: '-' }}</td>
                                    <td class="p-3 font-bold text-gray-900 dark:text-white">{{ $point->student?->name ?: '-' }}</td>
                                    <td class="p-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $point->type === 'reward' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' }}">
                                            {{ \App\Models\StudentPoint::getTypeLabel($point->type) }}
                                        </span>
                                    </td>
                                    <td class="p-3 font-medium text-gray-700 dark:text-zinc-300">{{ $point->notes ?: '-' }}</td>
                                    <td class="p-3 font-black text-sm {{ $point->type === 'reward' ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $point->type === 'reward' ? '+' : '-' }}{{ $point->points }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-4 text-center text-gray-400">Belum ada catatan kedisiplinan terbaru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
