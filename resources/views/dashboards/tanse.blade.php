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

            {{-- 1. Metric Cards (Bento Modern Grid) --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4.5">
                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden group border border-rose-500/20 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Pelanggaran/bln</span>
                        <div class="w-8 h-8 rounded-xl bg-rose-500/15 text-rose-600 dark:text-rose-400 flex items-center justify-center shadow-xs">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black text-rose-600 dark:text-rose-400 tracking-tight">{{ $stats['total_violations_month'] }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-rose-500"></span>
                        <p class="text-[10px] sm:text-xs text-rose-700 dark:text-rose-400 font-semibold truncate">Total: -{{ $stats['total_violation_points_month'] }} Poin</p>
                    </div>
                </div>

                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden group border border-amber-500/20 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Keterlambatan</span>
                        <div class="w-8 h-8 rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center shadow-xs">
                            <x-heroicon-o-clock class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ $stats['lateness_count_month'] }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span>
                        <p class="text-[10px] sm:text-xs text-amber-700 dark:text-amber-400 font-semibold truncate">Kasus terlambat</p>
                    </div>
                </div>

                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden group border border-teal-500/20 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Atribut / Seragam</span>
                        <div class="w-8 h-8 rounded-xl bg-teal-500/15 text-teal-600 dark:text-teal-400 flex items-center justify-center shadow-xs">
                            <x-heroicon-o-user class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ $stats['attribute_count_month'] }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-teal-500"></span>
                        <p class="text-[10px] sm:text-xs text-teal-700 dark:text-teal-400 font-semibold truncate">Pelanggaran atribut</p>
                    </div>
                </div>

                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden group border border-emerald-500/20 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Prestasi / Reward</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-sm shadow-xs">
                            🏆
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">{{ $stats['rewards_count_month'] }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                        <p class="text-[10px] sm:text-xs text-emerald-700 dark:text-emerald-400 font-semibold truncate">Reward Poin Positif</p>
                    </div>
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
