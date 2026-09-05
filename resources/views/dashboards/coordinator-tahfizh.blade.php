<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-0.5">
            <h2 class="font-bold text-lg sm:text-xl text-gray-800 dark:text-zinc-100 leading-tight flex items-center gap-2">
                <x-heroicon-o-book-open class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-600 dark:text-emerald-400" /> Dashboard Koordinator Tahfizh
            </h2>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-zinc-400">
                Pencapaian setoran hafalan, muraja'ah, target, dan ujian tahfizh.
            </p>
        </div>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            {{-- 1. Metric Cards (Bento Modern Grid) --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4.5">
                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden group border border-emerald-500/20 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Hafalan (Bulan Ini)</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-xs">
                            <x-heroicon-o-book-open class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">{{ $stats['hafalan_this_month'] }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                        <p class="text-[10px] sm:text-xs text-zinc-600 dark:text-zinc-400 font-semibold truncate">Hari Ini: {{ $stats['hafalan_today'] }} setoran</p>
                    </div>
                </div>

                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden group border border-teal-500/20 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Muraja'ah (Bulan Ini)</span>
                        <div class="w-8 h-8 rounded-xl bg-teal-500/15 text-teal-600 dark:text-teal-400 flex items-center justify-center shadow-xs">
                            <x-heroicon-o-arrow-path class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ $stats['murajaah_this_month'] }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-teal-500"></span>
                        <p class="text-[10px] sm:text-xs text-teal-700 dark:text-teal-400 font-semibold truncate">Hari Ini: {{ $stats['murajaah_today'] }} murajaah</p>
                    </div>
                </div>

                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden group border border-amber-500/20 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Target Aktif</span>
                        <div class="w-8 h-8 rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center shadow-xs">
                            <x-heroicon-o-check-badge class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ $stats['active_targets'] }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span>
                        <p class="text-[10px] sm:text-xs text-zinc-600 dark:text-zinc-400 font-semibold truncate">Selesai: {{ $stats['completed_targets'] }} target</p>
                    </div>
                </div>

                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden group border border-purple-500/20 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Ujian Tahfizh</span>
                        <div class="w-8 h-8 rounded-xl bg-purple-500/15 text-purple-600 dark:text-purple-400 flex items-center justify-center shadow-xs">
                            <x-heroicon-o-pencil-square class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black text-purple-600 dark:text-purple-400 tracking-tight">{{ $stats['exams_this_month'] }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-purple-500"></span>
                        <p class="text-[10px] sm:text-xs text-purple-700 dark:text-purple-400 font-semibold truncate">Lulus: {{ $stats['passed_exams'] }} murid</p>
                    </div>
                </div>
            </div>

            {{-- 2. Quick Action Shortcuts --}}
            <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-3.5 sm:p-5 shadow-sm">
                <h3 class="text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-3 pb-2 border-b border-zinc-100 dark:border-zinc-800 flex items-center gap-1.5">
                    <x-heroicon-o-bolt class="w-4 h-4 text-amber-500" /> Akses Cepat Menu Tahfizh
                </h3>
                <div class="grid grid-cols-3 sm:grid-cols-3 lg:grid-cols-6 gap-2 sm:gap-3">
                    <a href="{{ route('hafalan-records.create') }}" class="p-2.5 sm:p-4 bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/40 rounded-xl sm:rounded-2xl hover:bg-emerald-100/70 transition text-center group flex flex-col items-center">
                        <x-heroicon-o-plus-circle class="w-5 h-5 sm:w-7 sm:h-7 mb-1 text-emerald-600 dark:text-emerald-400" />
                        <span class="text-[11px] sm:text-xs font-bold text-emerald-900 dark:text-emerald-300">Setoran</span>
                    </a>
                    <a href="{{ route('murajaah-records.fast-input') }}" class="p-2.5 sm:p-4 bg-blue-50/60 dark:bg-blue-950/30 border border-blue-100 dark:border-blue-900/40 rounded-xl sm:rounded-2xl hover:bg-blue-100/70 transition text-center group flex flex-col items-center">
                        <x-heroicon-o-arrow-path class="w-5 h-5 sm:w-7 sm:h-7 mb-1 text-blue-600 dark:text-blue-400" />
                        <span class="text-[11px] sm:text-xs font-bold text-blue-900 dark:text-blue-300">Muraja'ah</span>
                    </a>
                    <a href="{{ route('tahfizh-exams.create') }}" class="p-2.5 sm:p-4 bg-purple-50/60 dark:bg-purple-950/30 border border-purple-100 dark:border-purple-900/40 rounded-xl sm:rounded-2xl hover:bg-purple-100/70 transition text-center group flex flex-col items-center">
                        <x-heroicon-o-clipboard-document-list class="w-5 h-5 sm:w-7 sm:h-7 mb-1 text-purple-600 dark:text-purple-400" />
                        <span class="text-[11px] sm:text-xs font-bold text-purple-900 dark:text-purple-300">Ujian</span>
                    </a>
                    <a href="{{ route('hafalan-targets.index') }}" class="p-2.5 sm:p-4 bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/40 rounded-xl sm:rounded-2xl hover:bg-indigo-100/70 transition text-center group flex flex-col items-center">
                        <x-heroicon-o-check-badge class="w-5 h-5 sm:w-7 sm:h-7 mb-1 text-indigo-600 dark:text-indigo-400" />
                        <span class="text-[11px] sm:text-xs font-bold text-indigo-900 dark:text-indigo-300">Target</span>
                    </a>
                    <a href="{{ route('reports.periodic') }}" class="p-2.5 sm:p-4 bg-amber-50/60 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/40 rounded-xl sm:rounded-2xl hover:bg-amber-100/70 transition text-center group flex flex-col items-center">
                        <x-heroicon-o-chart-bar class="w-5 h-5 sm:w-7 sm:h-7 mb-1 text-amber-600 dark:text-amber-400" />
                        <span class="text-[11px] sm:text-xs font-bold text-amber-900 dark:text-amber-300">Grafik</span>
                    </a>
                    <a href="{{ route('digital-reports.index') }}" class="p-2.5 sm:p-4 bg-rose-50/60 dark:bg-rose-950/30 border border-rose-100 dark:border-rose-900/40 rounded-xl sm:rounded-2xl hover:bg-rose-100/70 transition text-center group flex flex-col items-center">
                        <x-heroicon-o-document-text class="w-5 h-5 sm:w-7 sm:h-7 mb-1 text-rose-600 dark:text-rose-400" />
                        <span class="text-[11px] sm:text-xs font-bold text-rose-900 dark:text-rose-300">Rapor</span>
                    </a>
                </div>
            </div>

            {{-- 3. Recent Feed --}}
            <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-xl sm:rounded-2xl shadow-sm overflow-hidden">
                <div class="px-3.5 sm:px-5 py-3 sm:py-4 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                    <h3 class="text-xs sm:text-base font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                        <x-heroicon-o-clock class="w-4 h-4 sm:w-5 sm:h-5 text-indigo-600 dark:text-indigo-400" /> Setoran Hafalan Terbaru
                    </h3>
                    <span class="text-xs text-gray-400">Riwayat</span>
                </div>

                {{-- Mobile Card List --}}
                <div class="block sm:hidden divide-y divide-zinc-100 dark:divide-zinc-800/60 p-2 space-y-2">
                    @forelse($recentHafalan as $hafalan)
                        <div class="p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/40 space-y-1.5">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h4 class="font-bold text-xs text-zinc-900 dark:text-white">{{ $hafalan->student?->name ?: '-' }}</h4>
                                    <p class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold">{{ $hafalan->surah?->name_latin ?: '-' }} (Ayat {{ $hafalan->ayah_start }}-{{ $hafalan->ayah_end }})</p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300">
                                        {{ strtoupper($hafalan->status) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-zinc-400 pt-0.5">
                                <span>{{ $hafalan->submitted_at?->format('d/m/Y') ?: '-' }}</span>
                                <span>Nilai: <strong class="text-zinc-700 dark:text-zinc-200">{{ $hafalan->score ?: '-' }}</strong></span>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-gray-400">Belum ada setoran hafalan terbaru.</div>
                    @endforelse
                </div>

                {{-- Desktop Table View --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-zinc-400">
                        <thead class="bg-gray-50 dark:bg-zinc-800 text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider">
                            <tr>
                                <th class="p-3">Tanggal</th>
                                <th class="p-3">Murid</th>
                                <th class="p-3">Surah & Ayat</th>
                                <th class="p-3">Nilai</th>
                                <th class="p-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                            @forelse($recentHafalan as $hafalan)
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800/50 transition">
                                    <td class="p-3 font-medium text-gray-900 dark:text-white">{{ $hafalan->submitted_at?->format('d/m/Y') ?: '-' }}</td>
                                    <td class="p-3 font-bold text-gray-900 dark:text-white">{{ $hafalan->student?->name ?: '-' }}</td>
                                    <td class="p-3 text-indigo-600 dark:text-indigo-400 font-semibold">{{ $hafalan->surah?->name_latin ?: '-' }} (Ayat {{ $hafalan->ayah_start }}-{{ $hafalan->ayah_end }})</td>
                                    <td class="p-3 font-extrabold text-gray-900 dark:text-white">{{ $hafalan->score ?: '-' }}</td>
                                    <td class="p-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300">
                                            {{ strtoupper($hafalan->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-4 text-center text-gray-400">Belum ada setoran hafalan terbaru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
