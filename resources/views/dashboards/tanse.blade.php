<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-0.5">
            <h2 class="font-bold text-lg sm:text-xl text-gray-800 dark:text-zinc-100 leading-tight flex items-center gap-2">
                <x-heroicon-o-shield-check class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600 dark:text-indigo-400" /> Dashboard Koordinator Tanse
            </h2>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-zinc-400">
                Monitoring kedisiplinan, pelanggaran tata tertib, dan prestasi.
            </p>
        </div>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            {{-- 1. Metric Cards (2-Col Mobile Grid, 4-Col Desktop) --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
                <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 shadow-sm hover:shadow-md transition rounded-xl sm:rounded-2xl p-3.5 sm:p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] sm:text-sm font-medium text-gray-500 dark:text-zinc-400">Pelanggaran (Bulan Ini)</span>
                        <div class="p-1.5 sm:p-2 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-lg sm:rounded-xl">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4 sm:w-6 sm:h-6" />
                        </div>
                    </div>
                    <p class="text-xl sm:text-3xl font-extrabold text-gray-900 dark:text-white mt-1">{{ $stats['total_violations_month'] }}</p>
                    <p class="text-[10px] sm:text-xs text-rose-600 dark:text-rose-400 mt-1 font-semibold truncate">Total: -{{ $stats['total_violation_points_month'] }} Poin</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 shadow-sm hover:shadow-md transition rounded-xl sm:rounded-2xl p-3.5 sm:p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] sm:text-sm font-medium text-gray-500 dark:text-zinc-400">Keterlambatan</span>
                        <div class="p-1.5 sm:p-2 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-lg sm:rounded-xl">
                            <x-heroicon-o-clock class="w-4 h-4 sm:w-6 sm:h-6" />
                        </div>
                    </div>
                    <p class="text-xl sm:text-3xl font-extrabold text-gray-900 dark:text-white mt-1">{{ $stats['lateness_count_month'] }}</p>
                    <p class="text-[10px] sm:text-xs text-amber-600 dark:text-amber-400 mt-1 font-semibold truncate">Kasus terlambat</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 shadow-sm hover:shadow-md transition rounded-xl sm:rounded-2xl p-3.5 sm:p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] sm:text-sm font-medium text-gray-500 dark:text-zinc-400">Atribut / Seragam</span>
                        <div class="p-1.5 sm:p-2 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-lg sm:rounded-xl">
                            <x-heroicon-o-user class="w-4 h-4 sm:w-6 sm:h-6" />
                        </div>
                    </div>
                    <p class="text-xl sm:text-3xl font-extrabold text-gray-900 dark:text-white mt-1">{{ $stats['attribute_count_month'] }}</p>
                    <p class="text-[10px] sm:text-xs text-blue-600 dark:text-blue-400 mt-1 font-semibold truncate">Pelanggaran atribut</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 shadow-sm hover:shadow-md transition rounded-xl sm:rounded-2xl p-3.5 sm:p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] sm:text-sm font-medium text-gray-500 dark:text-zinc-400">Prestasi / Reward</span>
                        <span class="p-1 sm:p-1.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-lg sm:rounded-xl text-sm sm:text-lg">🏆</span>
                    </div>
                    <p class="text-xl sm:text-3xl font-extrabold text-gray-900 dark:text-white mt-1">{{ $stats['rewards_count_month'] }}</p>
                    <p class="text-[10px] sm:text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-semibold truncate">Reward Poin Positif</p>
                </div>
            </div>

            {{-- 2. Quick Action Shortcuts --}}
            <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-3.5 sm:p-5 shadow-sm">
                <h3 class="text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-3 pb-2 border-b border-zinc-100 dark:border-zinc-800 flex items-center gap-1.5">
                    <x-heroicon-o-bolt class="w-4 h-4 text-amber-500" /> Akses Cepat Menu Tanse
                </h3>
                <div class="grid grid-cols-3 gap-2 sm:gap-3">
                    <a href="{{ route('student-points.create') }}" class="p-2.5 sm:p-4 bg-rose-50/60 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/40 rounded-xl sm:rounded-2xl hover:bg-rose-100/70 transition text-center group flex flex-col items-center">
                        <span class="text-lg sm:text-2xl block mb-0.5">➕</span>
                        <span class="text-[11px] sm:text-xs font-bold text-rose-900 dark:text-rose-300">Catat Poin</span>
                    </a>
                    <a href="{{ route('student-points.index') }}" class="p-2.5 sm:p-4 bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/40 rounded-xl sm:rounded-2xl hover:bg-indigo-100/70 transition text-center group flex flex-col items-center">
                        <span class="text-lg sm:text-2xl block mb-0.5">📋</span>
                        <span class="text-[11px] sm:text-xs font-bold text-indigo-900 dark:text-indigo-300">Daftar Poin</span>
                    </a>
                    <a href="{{ route('student-points.chart') }}" class="p-2.5 sm:p-4 bg-amber-50/60 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/40 rounded-xl sm:rounded-2xl hover:bg-amber-100/70 transition text-center group flex flex-col items-center">
                        <span class="text-lg sm:text-2xl block mb-0.5">📊</span>
                        <span class="text-[11px] sm:text-xs font-bold text-amber-900 dark:text-amber-300">Grafik</span>
                    </a>
                </div>
            </div>

            {{-- 3. Recent Violations Feed --}}
            <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-xl sm:rounded-2xl shadow-sm overflow-hidden">
                <div class="px-3.5 sm:px-5 py-3 sm:py-4 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                    <h3 class="text-xs sm:text-base font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                        <x-heroicon-o-clipboard-document-list class="w-4 h-4 sm:w-5 sm:h-5 text-indigo-600 dark:text-indigo-400" /> Catatan Kedisiplinan Terbaru
                    </h3>
                    <span class="text-xs text-gray-400">Riwayat</span>
                </div>

                {{-- Mobile Card List --}}
                <div class="block sm:hidden divide-y divide-zinc-100 dark:divide-zinc-800/60 p-2 space-y-2">
                    @forelse($recentPoints as $point)
                        <div class="p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/40 space-y-1.5">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h4 class="font-bold text-xs text-zinc-900 dark:text-white">{{ $point->student?->name ?: '-' }}</h4>
                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">{{ $point->notes ?: '-' }}</p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="font-black text-xs {{ $point->type === 'reward' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ $point->type === 'reward' ? '+' : '-' }}{{ $point->points }} Poin
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-zinc-400 pt-0.5">
                                <span>{{ $point->date?->format('d/m/Y') ?: '-' }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-bold {{ $point->type === 'reward' ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300' : 'bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300' }}">
                                    {{ \App\Models\StudentPoint::getTypeLabel($point->type) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-gray-400">Belum ada catatan kedisiplinan terbaru.</div>
                    @endforelse
                </div>

                {{-- Desktop Table View --}}
                <div class="hidden sm:block overflow-x-auto">
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
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $point->type === 'reward' ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300' : 'bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300' }}">
                                            {{ \App\Models\StudentPoint::getTypeLabel($point->type) }}
                                        </span>
                                    </td>
                                    <td class="p-3 font-medium text-gray-700 dark:text-zinc-300">{{ $point->notes ?: '-' }}</td>
                                    <td class="p-3 font-black text-sm {{ $point->type === 'reward' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
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
