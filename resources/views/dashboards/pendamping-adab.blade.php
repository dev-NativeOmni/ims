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
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4.5">
                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden group border border-emerald-500/20 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Adab Hari Ini</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-xs">
                            <x-heroicon-o-check-circle class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ $stats['adab_filled_today'] }} <span class="text-xs sm:text-sm font-normal text-zinc-400">/ {{ $stats['total_students'] }}</span></p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                        <p class="text-[10px] sm:text-xs text-emerald-700 dark:text-emerald-400 font-semibold truncate">Persentase: {{ $stats['fill_percentage_today'] }}%</p>
                    </div>
                </div>

                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden group border border-amber-500/20 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Rerata Adab / Bln</span>
                        <div class="w-8 h-8 rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center shadow-xs">
                            <x-heroicon-o-star class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ $stats['avg_adab_score_month'] }} <span class="text-xs sm:text-sm font-normal text-zinc-400">/ 100</span></p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-amber-500"></span>
                        <p class="text-[10px] sm:text-xs text-amber-700 dark:text-amber-400 font-semibold truncate">Predikat: {{ $stats['adab_grade_month'] }}</p>
                    </div>
                </div>

                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden group border border-teal-500/20 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Materi Adab</span>
                        <div class="w-8 h-8 rounded-xl bg-teal-500/15 text-teal-600 dark:text-teal-400 flex items-center justify-center shadow-xs">
                            <x-heroicon-o-academic-cap class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ $stats['total_materials'] }}</p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-teal-500"></span>
                        <p class="text-[10px] sm:text-xs text-teal-700 dark:text-teal-400 font-semibold truncate">Modul Aktif</p>
                    </div>
                </div>

                <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300 relative overflow-hidden group border border-emerald-500/20 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] sm:text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Hari Kerja Efektif</span>
                        <div class="w-8 h-8 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-xs">
                            <x-heroicon-o-calendar class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-2xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ $stats['effective_days'] }} <span class="text-xs sm:text-sm font-normal text-zinc-400">Hari</span></p>
                    <div class="flex items-center gap-1.5 mt-2">
                        <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                        <p class="text-[10px] sm:text-xs text-emerald-700 dark:text-emerald-400 font-semibold truncate">Bulan {{ date('F Y') }}</p>
                    </div>
                </div>
            </div>

            {{-- 2. Quick Action Shortcuts --}}
            <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 shadow-sm">
                <h3 class="text-xs font-bold text-zinc-900 dark:text-white uppercase tracking-wider mb-3.5 pb-2 border-b border-zinc-200/70 dark:border-white/10 flex items-center gap-1.5">
                    <x-heroicon-o-bolt class="w-4 h-4 text-amber-500" /> Akses Cepat Menu Adab
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 @if(auth()->user()->hasAnyRole(['super_admin', 'admin', 'supervisor'])) lg:grid-cols-4 @endif gap-2.5 sm:gap-3">
                    <a href="{{ route('adab.index') }}" class="p-3 sm:p-4 rounded-2xl glass-liquid-inner hover:border-emerald-500/40 hover:shadow-md hover:-translate-y-0.5 transition-all text-center group flex flex-col items-center">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition">
                            <x-heroicon-o-sparkles class="w-5 h-5 sm:w-6 sm:h-6" />
                        </div>
                        <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Monitoring Adab</span>
                    </a>
                    <a href="{{ route('adab.chart') }}" class="p-3 sm:p-4 rounded-2xl glass-liquid-inner hover:border-amber-500/40 hover:shadow-md hover:-translate-y-0.5 transition-all text-center group flex flex-col items-center">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition">
                            <x-heroicon-o-chart-bar class="w-5 h-5 sm:w-6 sm:h-6" />
                        </div>
                        <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Grafik Pengisian</span>
                    </a>
                    <a href="{{ route('adab-materials.index') }}" class="p-3 sm:p-4 rounded-2xl glass-liquid-inner hover:border-indigo-500/40 hover:shadow-md hover:-translate-y-0.5 transition-all text-center group flex flex-col items-center">
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/15 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition">
                            <x-heroicon-o-book-open class="w-5 h-5 sm:w-6 sm:h-6" />
                        </div>
                        <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Materi Adab</span>
                    </a>
                    @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'supervisor']))
                        <a href="{{ route('settings.adab') }}" class="p-3 sm:p-4 rounded-2xl glass-liquid-inner hover:border-purple-500/40 hover:shadow-md hover:-translate-y-0.5 transition-all text-center group flex flex-col items-center">
                            <div class="w-10 h-10 rounded-xl bg-purple-500/15 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition">
                                <x-heroicon-o-cog-6-tooth class="w-5 h-5 sm:w-6 sm:h-6" />
                            </div>
                            <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">Pengaturan</span>
                        </a>
                    @endif
                </div>
            </div>

            {{-- 3. Class Ranking Table --}}
            <div class="glass-liquid-card rounded-2xl p-4 sm:p-5 shadow-sm">
                <h3 class="text-xs sm:text-sm font-bold text-zinc-900 dark:text-white uppercase tracking-wider mb-3.5 pb-2 border-b border-zinc-200/70 dark:border-white/10 flex items-center gap-1.5">
                    <x-heroicon-o-trophy class="w-4 h-4 sm:w-5 sm:h-5 text-amber-500" /> Peringkat Adab Per Kelas (Bulan Ini)
                </h3>
                <div class="space-y-2.5">
                    @forelse($classRankings as $rank => $c)
                        <div class="flex items-center justify-between p-3 rounded-xl glass-liquid-inner hover:border-amber-500/30 transition-all">
                            <div class="flex items-center gap-3">
                                <span class="w-7 h-7 rounded-full bg-amber-500/20 text-amber-700 dark:text-amber-300 font-black text-xs flex items-center justify-center border border-amber-500/30">
                                    {{ $rank + 1 }}
                                </span>
                                <span class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white">{{ is_array($c) ? $c['name'] : $c->name }}</span>
                            </div>
                            <span class="font-black text-xs sm:text-sm text-teal-600 dark:text-teal-400">
                                {{ round(is_array($c) ? $c['avg_score'] : $c->avg_score, 1) }} <span class="text-[10px] sm:text-xs font-normal text-zinc-400">/ 100</span>
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-zinc-400 py-4 text-center">Belum ada data peringkat kelas.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
