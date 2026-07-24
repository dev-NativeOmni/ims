<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-100 leading-tight flex items-center gap-2">
                <span>📖</span> Dashboard Koordinator Tahfizh
            </h2>
            <p class="text-sm text-gray-500 dark:text-zinc-400">
                Ringkasan eksekutif pencapaian setoran hafalan, muraja'ah, target, dan ujian tahfizh murid.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Metric Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm hover:shadow-md transition rounded-2xl p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-zinc-400">Setoran Hafalan (Bulan Ini)</span>
                        <span class="p-2 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl text-lg">📖</span>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">{{ $stats['hafalan_this_month'] }}</p>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-semibold">Hari Ini: {{ $stats['hafalan_today'] }} setoran</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm hover:shadow-md transition rounded-2xl p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-zinc-400">Muraja'ah (Bulan Ini)</span>
                        <span class="p-2 bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 rounded-xl text-lg">🔄</span>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">{{ $stats['murajaah_this_month'] }}</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1 font-semibold">Hari Ini: {{ $stats['murajaah_today'] }} murajaah</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm hover:shadow-md transition rounded-2xl p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-zinc-400">Target Hafalan Aktif</span>
                        <span class="p-2 bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-400 rounded-xl text-lg">🎯</span>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">{{ $stats['active_targets'] }}</p>
                    <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-1 font-semibold">Selesai: {{ $stats['completed_targets'] }} target</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm hover:shadow-md transition rounded-2xl p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-500 dark:text-zinc-400">Ujian Tahfizh (Bulan Ini)</span>
                        <span class="p-2 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xl text-lg">📝</span>
                    </div>
                    <p class="text-3xl font-extrabold text-gray-900 dark:text-white mt-2">{{ $stats['exams_this_month'] }}</p>
                    <p class="text-xs text-amber-600 dark:text-amber-400 mt-1 font-semibold">Lulus: {{ $stats['passed_exams'] }} murid</p>
                </div>
            </div>

            {{-- Quick Action Shortcuts --}}
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 border-b pb-3 dark:border-zinc-800 flex items-center gap-2">
                    <span>⚡</span> Akses Cepat Menu Tahfizh
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <a href="{{ route('hafalan-records.create') }}" class="p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/50 rounded-2xl hover:bg-emerald-100 transition text-center group">
                        <span class="text-2xl block mb-1">➕</span>
                        <span class="text-xs font-bold text-emerald-900 dark:text-emerald-300">Input Setoran</span>
                    </a>
                    <a href="{{ route('murajaah-records.create') }}" class="p-4 bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900/50 rounded-2xl hover:bg-blue-100 transition text-center group">
                        <span class="text-2xl block mb-1">🔄</span>
                        <span class="text-xs font-bold text-blue-900 dark:text-blue-300">Input Muraja'ah</span>
                    </a>
                    <a href="{{ route('tahfizh-exams.create') }}" class="p-4 bg-purple-50 dark:bg-purple-950/30 border border-purple-200 dark:border-purple-900/50 rounded-2xl hover:bg-purple-100 transition text-center group">
                        <span class="text-2xl block mb-1">📋</span>
                        <span class="text-xs font-bold text-purple-900 dark:text-purple-300">Jadwalkan Ujian</span>
                    </a>
                    <a href="{{ route('hafalan-targets.index') }}" class="p-4 bg-indigo-50 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-900/50 rounded-2xl hover:bg-indigo-100 transition text-center group">
                        <span class="text-2xl block mb-1">🎯</span>
                        <span class="text-xs font-bold text-indigo-900 dark:text-indigo-300">Kelola Target</span>
                    </a>
                    <a href="{{ route('reports.periodic') }}" class="p-4 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/50 rounded-2xl hover:bg-amber-100 transition text-center group">
                        <span class="text-2xl block mb-1">📊</span>
                        <span class="text-xs font-bold text-amber-900 dark:text-amber-300">Grafik Perkembangan</span>
                    </a>
                    <a href="{{ route('digital-reports.index') }}" class="p-4 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/50 rounded-2xl hover:bg-rose-100 transition text-center group">
                        <span class="text-2xl block mb-1">📄</span>
                        <span class="text-xs font-bold text-rose-900 dark:text-rose-300">Rapor Digital</span>
                    </a>
                </div>
            </div>

            {{-- Recent Feed --}}
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4 border-b pb-3 dark:border-zinc-800">
                    🕒 Setoran Hafalan Terbaru
                </h3>
                <div class="overflow-x-auto">
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
