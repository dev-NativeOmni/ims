<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-0.5">
            <h2 class="font-bold text-lg sm:text-xl text-gray-800 dark:text-zinc-100 leading-tight flex items-center gap-2">
                <x-heroicon-o-academic-cap class="w-5 h-5 sm:w-6 sm:h-6 text-emerald-600 dark:text-emerald-400" /> Dashboard Kepala Sekolah
            </h2>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-zinc-400">
                Ringkasan Tahfizh, Adab, dan Tanse — Bulan {{ date('F Y') }}
            </p>
        </div>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            {{-- ═══════════════ TOP KPI CARDS (2-Col Mobile, 6-Col Desktop) ═══════════════ --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-2.5 sm:gap-4">

                {{-- Hafalan Bulan Ini --}}
                <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wide">Hafalan/bln</span>
                        <div class="p-1 sm:p-1.5 bg-teal-50 dark:bg-teal-950/40 text-teal-600 dark:text-teal-400 rounded-lg">
                            <x-heroicon-o-book-open class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-lg sm:text-2xl font-extrabold text-gray-900 dark:text-white">{{ number_format($hafalanThisMonth) }}</p>
                    <p class="text-[10px] sm:text-xs text-teal-600 dark:text-teal-400 mt-0.5 font-semibold">Hari ini: {{ $hafalanToday }}</p>
                </div>

                {{-- Target Selesai --}}
                <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wide">Target ✓</span>
                        <div class="p-1 sm:p-1.5 bg-green-50 dark:bg-green-950/40 text-green-600 dark:text-green-400 rounded-lg">
                            <x-heroicon-o-check-badge class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-lg sm:text-2xl font-extrabold text-gray-900 dark:text-white">{{ $targetRate }}%</p>
                    <p class="text-[10px] sm:text-xs text-green-600 dark:text-green-400 mt-0.5 font-semibold truncate">{{ $completedTargets }} / {{ $activeTargets + $completedTargets }}</p>
                </div>

                {{-- Adab Diisi Hari Ini --}}
                <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wide">Adab Hari Ini</span>
                        <div class="p-1 sm:p-1.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-lg">
                            <x-heroicon-o-check-circle class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-lg sm:text-2xl font-extrabold text-gray-900 dark:text-white">{{ $fillPercentage }}%</p>
                    <p class="text-[10px] sm:text-xs text-emerald-600 dark:text-emerald-400 mt-0.5 font-semibold truncate">{{ $adabFilledToday }}/{{ $totalStudents }} murid</p>
                </div>

                {{-- Rata-rata Adab --}}
                <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wide">Rerata Adab</span>
                        <div class="p-1 sm:p-1.5 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-lg">
                            <x-heroicon-o-star class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-lg sm:text-2xl font-extrabold text-gray-900 dark:text-white">{{ $avgAdabScore }}</p>
                    <p class="text-[10px] sm:text-xs text-amber-600 dark:text-amber-400 mt-0.5 font-semibold truncate">Predikat: {{ $adabGrade }}</p>
                </div>

                {{-- Pelanggaran Bulan Ini --}}
                <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wide">Pelanggaran</span>
                        <div class="p-1 sm:p-1.5 bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-400 rounded-lg">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-lg sm:text-2xl font-extrabold text-gray-900 dark:text-white">{{ $tanseStats['violations'] }}</p>
                    <p class="text-[10px] sm:text-xs text-red-600 dark:text-red-400 mt-0.5 font-semibold truncate">{{ $tanseStats['violation_points'] }} poin</p>
                </div>

                {{-- Penghargaan Bulan Ini --}}
                <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-3 sm:p-4 shadow-sm hover:shadow-md transition">
                    <div class="flex items-center justify-between mb-1 sm:mb-2">
                        <span class="text-[10px] sm:text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wide">Reward</span>
                        <div class="p-1 sm:p-1.5 bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 rounded-lg">
                            <x-heroicon-o-trophy class="w-4 h-4 sm:w-5 sm:h-5" />
                        </div>
                    </div>
                    <p class="text-lg sm:text-2xl font-extrabold text-gray-900 dark:text-white">{{ $tanseStats['rewards'] }}</p>
                    <p class="text-[10px] sm:text-xs text-purple-600 dark:text-purple-400 mt-0.5 font-semibold truncate">Bulan {{ date('M') }}</p>
                </div>
            </div>

            {{-- ═══════════════ SECTION 1: TAHFIZH ═══════════════ --}}
            <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-xl sm:rounded-2xl shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-3.5 sm:px-6 py-3 sm:py-4 border-b border-gray-100 dark:border-zinc-800 bg-gradient-to-r from-teal-50/60 to-transparent dark:from-teal-950/20">
                    <div>
                        <h3 class="text-xs sm:text-base font-bold text-gray-900 dark:text-white flex items-center gap-1.5 sm:gap-2">
                            <x-heroicon-o-book-open class="w-4 h-4 sm:w-5 sm:h-5 text-teal-600 dark:text-teal-400" /> Perkembangan Tahfizh
                        </h3>
                        <p class="text-[11px] sm:text-xs text-gray-500 dark:text-zinc-400 mt-0.5">Aktivitas hafalan per tingkat kelas</p>
                    </div>
                    <a href="{{ route('reports.periodic') }}" class="text-xs font-semibold text-teal-600 dark:text-teal-400 hover:underline">
                        Detail →
                    </a>
                </div>
                <div class="p-3.5 sm:p-6">
                    <div class="grid grid-cols-3 gap-2 sm:gap-4 mb-4 sm:mb-6">
                        <div class="rounded-lg sm:rounded-xl bg-teal-50/50 dark:bg-teal-950/30 border border-teal-100/50 dark:border-teal-900/40 p-2.5 sm:p-4 text-center">
                            <p class="text-[10px] sm:text-xs font-bold text-teal-700 dark:text-teal-300 uppercase tracking-wider mb-0.5">Kelas X</p>
                            <p class="text-xl sm:text-3xl font-extrabold text-teal-800 dark:text-teal-200">{{ $tahfizhByLevel['X'] }}</p>
                            <p class="text-[10px] sm:text-xs text-teal-600 dark:text-teal-400 mt-0.5 hidden sm:block">Setoran bulan ini</p>
                        </div>
                        <div class="rounded-lg sm:rounded-xl bg-cyan-50/50 dark:bg-cyan-950/30 border border-cyan-100/50 dark:border-cyan-900/40 p-2.5 sm:p-4 text-center">
                            <p class="text-[10px] sm:text-xs font-bold text-cyan-700 dark:text-cyan-300 uppercase tracking-wider mb-0.5">Kelas XI</p>
                            <p class="text-xl sm:text-3xl font-extrabold text-cyan-800 dark:text-cyan-200">{{ $tahfizhByLevel['XI'] }}</p>
                            <p class="text-[10px] sm:text-xs text-cyan-600 dark:text-cyan-400 mt-0.5 hidden sm:block">Setoran bulan ini</p>
                        </div>
                        <div class="rounded-lg sm:rounded-xl bg-sky-50/50 dark:bg-sky-950/30 border border-sky-100/50 dark:border-sky-900/40 p-2.5 sm:p-4 text-center">
                            <p class="text-[10px] sm:text-xs font-bold text-sky-700 dark:text-sky-300 uppercase tracking-wider mb-0.5">Kelas XII</p>
                            <p class="text-xl sm:text-3xl font-extrabold text-sky-800 dark:text-sky-200">{{ $tahfizhByLevel['XII'] }}</p>
                            <p class="text-[10px] sm:text-xs text-sky-600 dark:text-sky-400 mt-0.5 hidden sm:block">Setoran bulan ini</p>
                        </div>
                    </div>
                    <div class="relative h-[180px] sm:h-[220px]">
                        <canvas id="tahfizhChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ SECTION 2: ADAB ═══════════════ --}}
            <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-xl sm:rounded-2xl shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-3.5 sm:px-6 py-3 sm:py-4 border-b border-gray-100 dark:border-zinc-800 bg-gradient-to-r from-amber-50/60 to-transparent dark:from-amber-950/20">
                    <div>
                        <h3 class="text-xs sm:text-base font-bold text-gray-900 dark:text-white flex items-center gap-1.5 sm:gap-2">
                            <x-heroicon-o-sparkles class="w-4 h-4 sm:w-5 sm:h-5 text-amber-500 dark:text-amber-400" /> Pengisian Adab
                        </h3>
                        <p class="text-[11px] sm:text-xs text-gray-500 dark:text-zinc-400 mt-0.5">Rata-rata nilai adab per tingkat & kehadiran</p>
                    </div>
                    <a href="{{ route('adab.chart') }}" class="text-xs font-semibold text-amber-600 dark:text-amber-400 hover:underline">
                        Detail →
                    </a>
                </div>
                <div class="p-3.5 sm:p-6">
                    <div class="grid grid-cols-3 gap-2 sm:gap-4 mb-4 sm:mb-6">
                        <div class="rounded-lg sm:rounded-xl bg-amber-50/50 dark:bg-amber-950/30 border border-amber-100/50 dark:border-amber-900/40 p-2.5 sm:p-4 text-center">
                            <p class="text-[10px] sm:text-xs font-bold text-amber-700 dark:text-amber-300 uppercase tracking-wider mb-0.5">Kelas X</p>
                            <p class="text-xl sm:text-3xl font-extrabold text-amber-800 dark:text-amber-200">{{ $adabByLevel['X'] }}</p>
                            <p class="text-[10px] sm:text-xs text-amber-600 dark:text-amber-400 mt-0.5 hidden sm:block">Rerata nilai</p>
                        </div>
                        <div class="rounded-lg sm:rounded-xl bg-orange-50/50 dark:bg-orange-950/30 border border-orange-100/50 dark:border-orange-900/40 p-2.5 sm:p-4 text-center">
                            <p class="text-[10px] sm:text-xs font-bold text-orange-700 dark:text-orange-300 uppercase tracking-wider mb-0.5">Kelas XI</p>
                            <p class="text-xl sm:text-3xl font-extrabold text-orange-800 dark:text-orange-200">{{ $adabByLevel['XI'] }}</p>
                            <p class="text-[10px] sm:text-xs text-orange-600 dark:text-orange-400 mt-0.5 hidden sm:block">Rerata nilai</p>
                        </div>
                        <div class="rounded-lg sm:rounded-xl bg-yellow-50/50 dark:bg-yellow-950/30 border border-yellow-100/50 dark:border-yellow-900/40 p-2.5 sm:p-4 text-center">
                            <p class="text-[10px] sm:text-xs font-bold text-yellow-700 dark:text-yellow-300 uppercase tracking-wider mb-0.5">Kelas XII</p>
                            <p class="text-xl sm:text-3xl font-extrabold text-yellow-800 dark:text-yellow-200">{{ $adabByLevel['XII'] }}</p>
                            <p class="text-[10px] sm:text-xs text-yellow-600 dark:text-yellow-400 mt-0.5 hidden sm:block">Rerata nilai</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                        <div>
                            <p class="text-[11px] sm:text-xs font-bold text-gray-600 dark:text-zinc-400 uppercase tracking-wider text-center mb-2 sm:mb-3">Kehadiran Pengisian Adab Hari Ini</p>
                            <div class="relative h-[160px] sm:h-[180px]">
                                <canvas id="adabDonutChart"></canvas>
                            </div>
                        </div>
                        <div>
                            <p class="text-[11px] sm:text-xs font-bold text-gray-600 dark:text-zinc-400 uppercase tracking-wider text-center mb-2 sm:mb-3">Rata-rata Nilai Adab per Tingkat</p>
                            <div class="relative h-[160px] sm:h-[180px]">
                                <canvas id="adabBarChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ SECTION 3: TANSE ═══════════════ --}}
            <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-xl sm:rounded-2xl shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-3.5 sm:px-6 py-3 sm:py-4 border-b border-gray-100 dark:border-zinc-800 bg-gradient-to-r from-red-50/60 to-transparent dark:from-red-950/20">
                    <div>
                        <h3 class="text-xs sm:text-base font-bold text-gray-900 dark:text-white flex items-center gap-1.5 sm:gap-2">
                            <x-heroicon-o-shield-check class="w-4 h-4 sm:w-5 sm:h-5 text-red-600 dark:text-red-400" /> Perkembangan Tanse
                        </h3>
                        <p class="text-[11px] sm:text-xs text-gray-500 dark:text-zinc-400 mt-0.5">Tren ketahanan sekolah & kedisiplinan</p>
                    </div>
                    <a href="{{ route('student-points.chart') }}" class="text-xs font-semibold text-red-600 dark:text-red-400 hover:underline">
                        Detail →
                    </a>
                </div>
                <div class="p-3.5 sm:p-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-4 mb-4 sm:mb-6">
                        <div class="rounded-lg sm:rounded-xl bg-red-50/50 dark:bg-red-950/30 border border-red-100/50 dark:border-red-900/40 p-2.5 sm:p-4 text-center">
                            <p class="text-[10px] sm:text-xs font-bold text-red-700 dark:text-red-300 uppercase tracking-wider mb-0.5">Pelanggaran</p>
                            <p class="text-xl sm:text-3xl font-extrabold text-red-800 dark:text-red-200">{{ $tanseStats['violations'] }}</p>
                            <p class="text-[10px] sm:text-xs text-red-600 dark:text-red-400 mt-0.5">Bulan ini</p>
                        </div>
                        <div class="rounded-lg sm:rounded-xl bg-orange-50/50 dark:bg-orange-950/30 border border-orange-100/50 dark:border-orange-900/40 p-2.5 sm:p-4 text-center">
                            <p class="text-[10px] sm:text-xs font-bold text-orange-700 dark:text-orange-300 uppercase tracking-wider mb-0.5">Total Poin</p>
                            <p class="text-xl sm:text-3xl font-extrabold text-orange-800 dark:text-orange-200">{{ $tanseStats['violation_points'] }}</p>
                            <p class="text-[10px] sm:text-xs text-orange-600 dark:text-orange-400 mt-0.5">Poin dipotong</p>
                        </div>
                        <div class="rounded-lg sm:rounded-xl bg-yellow-50/50 dark:bg-yellow-950/30 border border-yellow-100/50 dark:border-yellow-900/40 p-2.5 sm:p-4 text-center">
                            <p class="text-[10px] sm:text-xs font-bold text-yellow-700 dark:text-yellow-300 uppercase tracking-wider mb-0.5">Keterlambatan</p>
                            <p class="text-xl sm:text-3xl font-extrabold text-yellow-800 dark:text-yellow-200">{{ $tanseStats['lateness'] }}</p>
                            <p class="text-[10px] sm:text-xs text-yellow-600 dark:text-yellow-400 mt-0.5">Kasus</p>
                        </div>
                        <div class="rounded-lg sm:rounded-xl bg-purple-50/50 dark:bg-purple-950/30 border border-purple-100/50 dark:border-purple-900/40 p-2.5 sm:p-4 text-center">
                            <p class="text-[10px] sm:text-xs font-bold text-purple-700 dark:text-purple-300 uppercase tracking-wider mb-0.5">Reward</p>
                            <p class="text-xl sm:text-3xl font-extrabold text-purple-800 dark:text-purple-200">{{ $tanseStats['rewards'] }}</p>
                            <p class="text-[10px] sm:text-xs text-purple-600 dark:text-purple-400 mt-0.5">Penghargaan</p>
                        </div>
                    </div>
                    <div class="relative h-[180px] sm:h-[220px]">
                        <canvas id="tanseChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ QUICK NAVIGATION ═══════════════ --}}
            <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 rounded-xl sm:rounded-2xl p-3.5 sm:p-5 shadow-sm">
                <h3 class="text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-3 sm:mb-4 flex items-center gap-1.5">
                    <x-heroicon-o-bolt class="w-4 h-4 text-amber-500" /> Akses Cepat Laporan
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-3">
                    <a href="{{ route('reports.periodic') }}"
                        class="flex items-center gap-3 p-3 sm:p-4 bg-teal-50/60 dark:bg-teal-950/30 border border-teal-100 dark:border-teal-900/40 rounded-xl sm:rounded-2xl hover:bg-teal-100/70 dark:hover:bg-teal-950/50 transition">
                        <x-heroicon-o-chart-bar class="w-5 h-5 sm:w-6 sm:h-6 text-teal-600 dark:text-teal-400 shrink-0" />
                        <div>
                            <p class="text-xs sm:text-sm font-bold text-teal-900 dark:text-teal-200">Grafik Tahfizh</p>
                            <p class="text-[10px] sm:text-xs text-teal-700 dark:text-teal-400">Laporan periodik</p>
                        </div>
                    </a>
                    <a href="{{ route('adab.chart') }}"
                        class="flex items-center gap-3 p-3 sm:p-4 bg-amber-50/60 dark:bg-amber-950/30 border border-amber-100 dark:border-amber-900/40 rounded-xl sm:rounded-2xl hover:bg-amber-100/70 dark:hover:bg-amber-950/50 transition">
                        <x-heroicon-o-sparkles class="w-5 h-5 sm:w-6 sm:h-6 text-amber-600 dark:text-amber-400 shrink-0" />
                        <div>
                            <p class="text-xs sm:text-sm font-bold text-amber-900 dark:text-amber-200">Grafik Adab</p>
                            <p class="text-[10px] sm:text-xs text-amber-700 dark:text-amber-400">Monitoring keagamaan</p>
                        </div>
                    </a>
                    <a href="{{ route('student-points.chart') }}"
                        class="flex items-center gap-3 p-3 sm:p-4 bg-red-50/60 dark:bg-red-950/30 border border-red-100 dark:border-red-900/40 rounded-xl sm:rounded-2xl hover:bg-red-100/70 dark:hover:bg-red-950/50 transition">
                        <x-heroicon-o-shield-check class="w-5 h-5 sm:w-6 sm:h-6 text-red-600 dark:text-red-400 shrink-0" />
                        <div>
                            <p class="text-xs sm:text-sm font-bold text-red-900 dark:text-red-200">Grafik Tanse</p>
                            <p class="text-[10px] sm:text-xs text-red-700 dark:text-red-400">Ketahanan sekolah</p>
                        </div>
                    </a>
                </div>
            </div>

        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(63,63,70,0.4)' : 'rgba(228,228,231,0.8)';
            const tickColor = isDark ? '#a1a1aa' : '#71717a';

            // Tahfizh Bar Chart
            new Chart(document.getElementById('tahfizhChart'), {
                type: 'bar',
                data: {
                    labels: ['Kelas X', 'Kelas XI', 'Kelas XII'],
                    datasets: [{
                        label: 'Setoran Hafalan',
                        data: [{{ $tahfizhByLevel['X'] }}, {{ $tahfizhByLevel['XI'] }}, {{ $tahfizhByLevel['XII'] }}],
                        backgroundColor: ['#0d9488','#0891b2','#0369a1'],
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor } },
                        x: { grid: { display: false }, ticks: { color: tickColor } }
                    }
                }
            });

            // Adab Donut
            new Chart(document.getElementById('adabDonutChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Diisi', 'Belum Diisi'],
                    datasets: [{
                        data: [Math.max({{ $adabFilledToday }}, 0), Math.max({{ $totalStudents }} - {{ $adabFilledToday }}, 0)],
                        backgroundColor: ['#10b981','#f59e0b'],
                        borderWidth: 2,
                        borderColor: isDark ? '#18181b' : '#fff',
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '65%',
                    plugins: {
                        legend: { position: 'bottom', labels: { color: tickColor, boxWidth: 12, font: { size: 11 } } }
                    }
                }
            });

            // Adab Bar per Tingkat
            new Chart(document.getElementById('adabBarChart'), {
                type: 'bar',
                data: {
                    labels: ['Kelas X', 'Kelas XI', 'Kelas XII'],
                    datasets: [{
                        label: 'Rata-rata Nilai Adab',
                        data: [{{ $adabByLevel['X'] }}, {{ $adabByLevel['XI'] }}, {{ $adabByLevel['XII'] }}],
                        backgroundColor: ['#f59e0b','#f97316','#eab308'],
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, max: 100, grid: { color: gridColor }, ticks: { color: tickColor } },
                        x: { grid: { display: false }, ticks: { color: tickColor } }
                    }
                }
            });

            // Tanse Trend Line
            const tanseTrend = @json($tanseTrend);
            new Chart(document.getElementById('tanseChart'), {
                type: 'line',
                data: {
                    labels: tanseTrend.map(t => t.label),
                    datasets: [{
                        label: 'Poin Pelanggaran',
                        data: tanseTrend.map(t => t.points),
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239,68,68,0.08)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#ef4444',
                        pointRadius: 5,
                        pointHoverRadius: 7,
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor } },
                        x: { grid: { display: false }, ticks: { color: tickColor } }
                    }
                }
            });
        });
    </script>
    @endpush
</x-app-layout>
