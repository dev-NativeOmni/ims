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
            <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 shadow-sm">
                <h3 class="text-xs font-bold text-zinc-900 dark:text-white uppercase tracking-wider mb-3.5 pb-2 border-b border-zinc-200/70 dark:border-white/10 flex items-center gap-1.5">
                    <x-heroicon-o-bolt class="w-4 h-4 text-amber-500" /> Akses Cepat Menu Tahfizh
                </h3>
                <div class="grid grid-cols-3 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 sm:gap-3">
                    <a href="{{ route('hafalan-records.create') }}" class="p-3 sm:p-4 rounded-2xl glass-liquid-inner hover:border-emerald-500/40 hover:shadow-md hover:-translate-y-0.5 transition-all text-center group flex flex-col items-center">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition">
                            <x-heroicon-o-plus-circle class="w-5 h-5 sm:w-6 sm:h-6" />
                        </div>
                        <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Setoran</span>
                    </a>
                    <a href="{{ route('murajaah-records.fast-input') }}" class="p-3 sm:p-4 rounded-2xl glass-liquid-inner hover:border-teal-500/40 hover:shadow-md hover:-translate-y-0.5 transition-all text-center group flex flex-col items-center">
                        <div class="w-10 h-10 rounded-xl bg-teal-500/15 text-teal-600 dark:text-teal-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition">
                            <x-heroicon-o-arrow-path class="w-5 h-5 sm:w-6 sm:h-6" />
                        </div>
                        <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Muraja'ah</span>
                    </a>
                    <a href="{{ route('tahfizh-exams.create') }}" class="p-3 sm:p-4 rounded-2xl glass-liquid-inner hover:border-purple-500/40 hover:shadow-md hover:-translate-y-0.5 transition-all text-center group flex flex-col items-center">
                        <div class="w-10 h-10 rounded-xl bg-purple-500/15 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition">
                            <x-heroicon-o-clipboard-document-list class="w-5 h-5 sm:w-6 sm:h-6" />
                        </div>
                        <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Ujian</span>
                    </a>
                    <a href="{{ route('hafalan-targets.index') }}" class="p-3 sm:p-4 rounded-2xl glass-liquid-inner hover:border-amber-500/40 hover:shadow-md hover:-translate-y-0.5 transition-all text-center group flex flex-col items-center">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition">
                            <x-heroicon-o-check-badge class="w-5 h-5 sm:w-6 sm:h-6" />
                        </div>
                        <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Target</span>
                    </a>
                    <a href="{{ route('reports.periodic') }}" class="p-3 sm:p-4 rounded-2xl glass-liquid-inner hover:border-blue-500/40 hover:shadow-md hover:-translate-y-0.5 transition-all text-center group flex flex-col items-center">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/15 text-blue-600 dark:text-blue-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition">
                            <x-heroicon-o-chart-bar class="w-5 h-5 sm:w-6 sm:h-6" />
                        </div>
                        <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Grafik</span>
                    </a>
                    <a href="{{ route('digital-reports.index') }}" class="p-3 sm:p-4 rounded-2xl glass-liquid-inner hover:border-rose-500/40 hover:shadow-md hover:-translate-y-0.5 transition-all text-center group flex flex-col items-center">
                        <div class="w-10 h-10 rounded-xl bg-rose-500/15 text-rose-600 dark:text-rose-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition">
                            <x-heroicon-o-document-text class="w-5 h-5 sm:w-6 sm:h-6" />
                        </div>
                        <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Rapor</span>
                    </a>
                </div>
            </div>

            {{-- 3. Recent Feed --}}
            <div class="glass-liquid-card rounded-2xl shadow-sm overflow-hidden">
                <div class="px-4 sm:px-6 py-3.5 sm:py-4 border-b border-zinc-200/70 dark:border-white/10 flex items-center justify-between bg-zinc-50/50 dark:bg-zinc-900/50">
                    <h3 class="text-xs sm:text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                        <x-heroicon-o-clock class="w-4 h-4 sm:w-5 sm:h-5 text-teal-600 dark:text-teal-400" /> Setoran Hafalan Terbaru
                    </h3>
                    <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Riwayat Terkini</span>
                </div>

                {{-- Mobile Card List --}}
                <div class="block sm:hidden divide-y divide-zinc-100 dark:divide-zinc-800/60 p-2 space-y-2">
                    @forelse($recentHafalan as $hafalan)
                        <div class="p-3 rounded-xl glass-liquid-inner space-y-1.5">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h4 class="font-bold text-xs text-zinc-900 dark:text-white">{{ $hafalan->student?->name ?: '-' }}</h4>
                                    <p class="text-[11px] text-teal-600 dark:text-teal-400 font-semibold">{{ $hafalan->surah?->name_latin ?: '-' }} (Ayat {{ $hafalan->ayah_start }}-{{ $hafalan->ayah_end }})</p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                        {{ strtoupper($hafalan->status) }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-zinc-500 dark:text-zinc-400 pt-1 border-t border-zinc-200/40 dark:border-white/5">
                                <span>{{ $hafalan->submitted_at?->format('d/m/Y') ?: '-' }}</span>
                                <span>Nilai: <strong class="text-zinc-900 dark:text-white font-bold">{{ $hafalan->score ?: '-' }}</strong></span>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-zinc-400">Belum ada setoran hafalan terbaru.</div>
                    @endforelse
                </div>

                {{-- Desktop Table View --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="w-full text-left text-sm text-zinc-600 dark:text-zinc-400">
                        <thead class="bg-zinc-50/70 dark:bg-zinc-900/50 text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider border-b border-zinc-200/70 dark:border-white/10">
                            <tr>
                                <th class="py-3.5 px-6">Tanggal</th>
                                <th class="py-3.5 px-6">Santri</th>
                                <th class="py-3.5 px-6">Surah & Ayat</th>
                                <th class="py-3.5 px-6">Nilai</th>
                                <th class="py-3.5 px-6">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200/60 dark:divide-white/5">
                            @forelse($recentHafalan as $hafalan)
                                <tr class="hover:bg-zinc-500/5 dark:hover:bg-white/[0.02] transition">
                                    <td class="py-3 px-6 font-medium text-zinc-900 dark:text-white">{{ $hafalan->submitted_at?->format('d/m/Y') ?: '-' }}</td>
                                    <td class="py-3 px-6 font-bold text-zinc-900 dark:text-white">{{ $hafalan->student?->name ?: '-' }}</td>
                                    <td class="py-3 px-6 text-teal-600 dark:text-teal-400 font-semibold">{{ $hafalan->surah?->name_latin ?: '-' }} (Ayat {{ $hafalan->ayah_start }}-{{ $hafalan->ayah_end }})</td>
                                    <td class="py-3 px-6 font-black text-zinc-900 dark:text-white">{{ $hafalan->score ?: '-' }}</td>
                                    <td class="py-3 px-6">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                            {{ strtoupper($hafalan->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-6 text-center text-zinc-400">Belum ada setoran hafalan terbaru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
