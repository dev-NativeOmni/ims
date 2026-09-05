<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-0.5">
            <h2 class="font-bold text-lg sm:text-xl text-gray-800 dark:text-zinc-100 leading-tight flex items-center gap-2">
                <x-heroicon-o-sparkles class="w-5 h-5 sm:w-6 sm:h-6 text-amber-500 dark:text-amber-400" /> Dashboard Koordinator Adab
            </h2>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-zinc-400">
                Monitoring harian kuisioner adab, pembinaan karakter, dan materi.
            </p>
        </div>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            {{-- 1. Metric Cards (Bento Modern Grid) --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-white/[0.08] shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 rounded-2xl p-4 sm:p-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Adab Hari Ini</span>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-xl flex items-center justify-center">
                            <x-heroicon-o-check-circle class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ $stats['adab_filled_today'] }} <span class="text-xs sm:text-sm font-normal text-zinc-400">/ {{ $stats['total_students'] }}</span></p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <p class="text-[10px] sm:text-xs text-emerald-600 dark:text-emerald-400 font-semibold truncate">Persentase: {{ $stats['fill_percentage_today'] }}%</p>
                    </div>
                </div>

                <div class="bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-white/[0.08] shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 rounded-2xl p-4 sm:p-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Rerata Adab / Bln</span>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 rounded-xl flex items-center justify-center">
                            <x-heroicon-o-star class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ $stats['avg_adab_score_month'] }} <span class="text-xs sm:text-sm font-normal text-zinc-400">/ 100</span></p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        <p class="text-[10px] sm:text-xs text-amber-600 dark:text-amber-400 font-semibold truncate">Predikat: {{ $stats['adab_grade_month'] }}</p>
                    </div>
                </div>

                <div class="bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-white/[0.08] shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 rounded-2xl p-4 sm:p-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Materi Adab</span>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 rounded-xl flex items-center justify-center">
                            <x-heroicon-o-academic-cap class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ $stats['total_materials'] }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-teal-500"></span>
                        <p class="text-[10px] sm:text-xs text-teal-600 dark:text-teal-400 font-semibold truncate">Modul Aktif</p>
                    </div>
                </div>

                <div class="bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-white/[0.08] shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 rounded-2xl p-4 sm:p-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Hari Kerja Efektif</span>
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-xl flex items-center justify-center">
                            <x-heroicon-o-calendar class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ $stats['effective_days'] }} <span class="text-xs sm:text-sm font-normal text-zinc-400">Hari</span></p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        <p class="text-[10px] sm:text-xs text-emerald-600 dark:text-emerald-400 font-semibold truncate">Bulan {{ date('F Y') }}</p>
                    </div>
                </div>
            </div>

            {{-- 2. Quick Action Shortcuts --}}
            <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-3.5 sm:p-5 shadow-sm">
                <h3 class="text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-3 pb-2 border-b border-zinc-100 dark:border-zinc-800 flex items-center gap-1.5">
                    <x-heroicon-o-bolt class="w-4 h-4 text-amber-500" /> Akses Cepat Menu Adab
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 @if(auth()->user()->hasAnyRole(['super_admin', 'admin', 'supervisor'])) lg:grid-cols-4 @endif gap-2 sm:gap-3">
                    <a href="{{ route('adab.index') }}" class="p-2.5 sm:p-4 bg-emerald-50/60 dark:bg-emerald-950/30 border border-emerald-100 dark:border-emerald-900/40 rounded-xl sm:rounded-2xl hover:bg-emerald-100/70 transition text-center group flex flex-col items-center">
                        <x-heroicon-o-sparkles class="w-5 h-5 sm:w-7 sm:h-7 mb-1 text-emerald-600 dark:text-emerald-400" />
                        <span class="text-[11px] sm:text-xs font-bold text-emerald-900 dark:text-emerald-300">Monitoring Adab</span>
                    </a>
                    <a href="{{ route('adab.chart') }}" class="p-2.5 sm:p-4 bg-amber-50/60 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/40 rounded-xl sm:rounded-2xl hover:bg-amber-100/70 transition text-center group flex flex-col items-center">
                        <x-heroicon-o-chart-bar class="w-5 h-5 sm:w-7 sm:h-7 mb-1 text-amber-600 dark:text-amber-400" />
                        <span class="text-[11px] sm:text-xs font-bold text-amber-900 dark:text-amber-300">Grafik Pengisian</span>
                    </a>
                    <a href="{{ route('adab-materials.index') }}" class="p-2.5 sm:p-4 bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-100 dark:border-indigo-900/40 rounded-xl sm:rounded-2xl hover:bg-indigo-100/70 transition text-center group flex flex-col items-center">
                        <x-heroicon-o-book-open class="w-5 h-5 sm:w-7 sm:h-7 mb-1 text-indigo-600 dark:text-indigo-400" />
                        <span class="text-[11px] sm:text-xs font-bold text-indigo-900 dark:text-indigo-300">Materi Adab</span>
                    </a>
                    @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'supervisor']))
                        <a href="{{ route('settings.adab') }}" class="p-2.5 sm:p-4 bg-purple-50/60 dark:bg-purple-950/30 border border-purple-100 dark:border-purple-900/40 rounded-xl sm:rounded-2xl hover:bg-purple-100/70 transition text-center group flex flex-col items-center">
                            <x-heroicon-o-cog-6-tooth class="w-5 h-5 sm:w-7 sm:h-7 mb-1 text-purple-600 dark:text-purple-400" />
                            <span class="text-[11px] sm:text-xs font-bold text-purple-900 dark:text-purple-300">Pengaturan</span>
                        </a>
                    @endif
                </div>
            </div>

            {{-- 3. Class Ranking Table --}}
            <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-3.5 sm:p-5 shadow-sm">
                <h3 class="text-xs sm:text-base font-bold text-gray-900 dark:text-white mb-3 pb-2 border-b border-zinc-100 dark:border-zinc-800 flex items-center gap-1.5">
                    <x-heroicon-o-trophy class="w-4 h-4 sm:w-5 sm:h-5 text-amber-500" /> Peringkat Adab Per Kelas (Bulan Ini)
                </h3>
                <div class="space-y-2">
                    @forelse($classRankings as $rank => $c)
                        <div class="flex items-center justify-between p-2.5 sm:p-3 bg-zinc-50 dark:bg-zinc-800/40 rounded-lg sm:rounded-xl border border-zinc-100 dark:border-zinc-800">
                            <div class="flex items-center gap-2.5">
                                <span class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 font-extrabold text-[11px] sm:text-xs flex items-center justify-center">
                                    {{ $rank + 1 }}
                                </span>
                                <span class="font-bold text-xs sm:text-sm text-gray-900 dark:text-white">{{ is_array($c) ? $c['name'] : $c->name }}</span>
                            </div>
                            <span class="font-extrabold text-xs sm:text-sm text-indigo-600 dark:text-indigo-400">
                                {{ round(is_array($c) ? $c['avg_score'] : $c->avg_score, 1) }} <span class="text-[10px] sm:text-xs font-normal text-gray-400">/ 100</span>
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 py-3 text-center">Belum ada data peringkat kelas.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
