<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight flex items-center gap-2">
                <span>🕋</span> Dashboard Koordinator Adab (Keagamaan)
            </h2>
            <p class="text-sm text-gray-500 dark:text-zinc-400">
                Monitoring harian pengisian kuisioner adab, pembinaan karakter, dan materi keagamaan murid.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Metric Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm hover:shadow-md transition rounded-2xl p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-zinc-400">Adab Diisi Hari Ini</span>
                        <span class="p-2 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl text-lg">✅</span>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">{{ $stats['adab_filled_today'] }} / {{ $stats['total_students'] }}</p>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-semibold">Persentase: {{ $stats['fill_percentage_today'] }}%</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm hover:shadow-md transition rounded-2xl p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-zinc-400">Rerata Adab Bulan Ini</span>
                        <span class="p-2 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xl text-lg">⭐</span>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">{{ $stats['avg_adab_score_month'] }} / 100</p>
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1 font-semibold">Predikat: {{ $stats['adab_grade_month'] }}</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm hover:shadow-md transition rounded-2xl p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-zinc-400">Total Materi Adab</span>
                        <span class="p-2 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl text-lg">📚</span>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">{{ $stats['total_materials'] }}</p>
                    <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-1 font-semibold">Modul Pembinaan Aktif</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm hover:shadow-md transition rounded-2xl p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-zinc-400">Hari Kerja Efektif</span>
                        <span class="p-2 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-xl text-lg">📅</span>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">{{ $stats['effective_days'] }} Hari</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1 font-semibold">Bulan {{ date('F Y') }}</p>
                </div>
            </div>

            {{-- Quick Action Shortcuts --}}
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 border-b pb-3 dark:border-zinc-800 flex items-center gap-2">
                    <span>⚡</span> Akses Cepat Menu Adab
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <a href="{{ route('adab.index') }}" class="p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/50 rounded-2xl hover:bg-emerald-100 transition text-center group">
                        <span class="text-2xl block mb-1">🕋</span>
                        <span class="text-xs font-bold text-emerald-900 dark:text-emerald-300">Monitoring Adab</span>
                    </a>
                    <a href="{{ route('adab.chart') }}" class="p-4 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 rounded-2xl hover:bg-amber-100 transition text-center group">
                        <span class="text-2xl block mb-1">📊</span>
                        <span class="text-xs font-bold text-amber-900 dark:text-amber-300">Grafik Pengisian</span>
                    </a>
                    <a href="{{ route('adab-materials.index') }}" class="p-4 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-900/50 rounded-2xl hover:bg-indigo-100 transition text-center group">
                        <span class="text-2xl block mb-1">📚</span>
                        <span class="text-xs font-bold text-indigo-900 dark:text-indigo-300">Materi Adab</span>
                    </a>
                    <a href="{{ route('settings.adab') }}" class="p-4 bg-purple-50 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-900/50 rounded-2xl hover:bg-purple-100 transition text-center group">
                        <span class="text-2xl block mb-1">⚙️</span>
                        <span class="text-xs font-bold text-purple-900 dark:text-purple-300">Pengaturan Adab</span>
                    </a>
                </div>
            </div>

            {{-- Class Ranking Table --}}
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 border-b pb-3 dark:border-zinc-800">
                    🏆 Peringkat Kedisiplinan Adab Per Kelas (Bulan Ini)
                </h3>
                <div class="space-y-3">
                    @forelse($classRankings as $rank => $c)
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-800/50 rounded-xl border border-gray-100 dark:border-zinc-800">
                            <div class="flex items-center gap-3">
                                <span class="w-7 h-7 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 font-extrabold text-xs flex items-center justify-center">
                                    {{ $rank + 1 }}
                                </span>
                                <span class="font-bold text-sm text-gray-900 dark:text-white">{{ is_array($c) ? $c['name'] : $c->name }}</span>
                            </div>
                            <span class="font-black text-sm text-indigo-600 dark:text-indigo-400">
                                {{ round(is_array($c) ? $c['avg_score'] : $c->avg_score, 1) }} / 100
                            </span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 py-2">Belum ada data peringkat kelas.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
