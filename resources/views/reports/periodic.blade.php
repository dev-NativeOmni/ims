<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                    Laporan & Grafik Perkembangan Berkala
                </h2>
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    Pantau tren grafik setoran hafalan & murajaah serta capaian target kelas secara berkala.
                </p>
            </div>
            
            @if ($selectedClass)
                <a href="{{ route('reports.periodic.print', request()->query()) }}" target="_blank" class="no-print inline-flex items-center gap-2 rounded-xl bg-teal-600 hover:bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.82l-.24.24c-1.316 1.316-3.484 1.316-4.8 0L1 13.38V9.25a2.25 2.25 0 012.25-2.25h15.5A2.25 2.25 0 0121 9.25v4.13l-.68.68c-1.316 1.316-3.484 1.316-4.8 0l-.24-.24M6.72 13.82A4.488 4.488 0 005.25 17v3.25h13.5V17c0-1.28-.52-2.438-1.37-3.18M6.72 13.82h10.56M9 11.25h.008v.008H9v-.008z" />
                    </svg>
                    <span>Cetak Laporan</span>
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Filter Form -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-5 shadow-sm transition-colors duration-200">
                <form method="GET" action="{{ route('reports.periodic') }}" x-data="{ periodType: '{{ $periodType }}' }" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    
                    <!-- ClassRoom Selector -->
                    <div>
                        <label for="class_room_id" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Kelas</label>
                        <select name="class_room_id" id="class_room_id" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm">
                            @foreach ($classRooms as $class)
                                <option value="{{ $class->id }}" {{ $selectedClassId == $class->id ? 'selected' : '' }}>{{ $class->name }} ({{ $class->program?->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Period Type Selector -->
                    <div>
                        <label for="period_type" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Rentang Waktu</label>
                        <select name="period_type" id="period_type" x-model="periodType" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm">
                            <option value="monthly">Bulanan</option>
                            <option value="quarterly">Tiga Bulanan (Term)</option>
                        </select>
                    </div>

                    <!-- Month Selector / Quarter Selector (Toggled by alpine) -->
                    <div>
                        <!-- Monthly Option -->
                        <div x-show="periodType === 'monthly'">
                            <label for="month" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Bulan</label>
                            <select name="month" id="month" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm">
                                @foreach ($monthsList as $key => $name)
                                    <option value="{{ $key }}" {{ $selectedMonth == $key ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Quarterly Option -->
                        <div x-show="periodType === 'quarterly'" style="display: none;">
                            <label for="quarter" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Term (Triwulan)</label>
                            <select name="quarter" id="quarter" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm">
                                <option value="1" {{ $selectedQuarter == 1 ? 'selected' : '' }}>Term 1 (Jul - Sep)</option>
                                <option value="2" {{ $selectedQuarter == 2 ? 'selected' : '' }}>Term 2 (Okt - Des)</option>
                                <option value="3" {{ $selectedQuarter == 3 ? 'selected' : '' }}>Term 3 (Jan - Mar)</option>
                                <option value="4" {{ $selectedQuarter == 4 ? 'selected' : '' }}>Term 4 (Apr - Jun)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Year and Submit Button -->
                    <div class="flex gap-2">
                        <div class="w-24">
                            <label for="year" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Tahun</label>
                            <input type="number" name="year" id="year" value="{{ $selectedYear }}" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm">
                        </div>
                        <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 border border-transparent rounded-xl text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 shadow-sm transition-colors duration-150 min-h-[42px]">
                            Filter
                        </button>
                    </div>

                </form>
            </div>

            @if ($selectedClass)
                <!-- Metrics Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                        <div class="text-xs font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider">Total Santri</div>
                        <div class="mt-2 text-3xl font-extrabold text-gray-900 dark:text-white">{{ $summary['total_students'] }}</div>
                        <div class="mt-1 text-xs text-gray-500">Santri terdaftar aktif di kelas</div>
                    </div>
                    
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                        <div class="text-xs font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider">Total Setoran</div>
                        <div class="mt-2 text-3xl font-extrabold text-teal-600 dark:text-teal-400">{{ $summary['total_hafalan'] }}</div>
                        <div class="mt-1 text-xs text-gray-500">Setoran hafalan baru lulus</div>
                    </div>

                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                        <div class="text-xs font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider">Total Murajaah</div>
                        <div class="mt-2 text-3xl font-extrabold text-amber-600 dark:text-amber-450">{{ $summary['total_murajaah'] }}</div>
                        <div class="mt-1 text-xs text-gray-500">Pengulangan hafalan lulus</div>
                    </div>

                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                        <div class="text-xs font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider">Rerata Nilai Hafalan</div>
                        <div class="mt-2 text-3xl font-extrabold text-gray-900 dark:text-white">{{ $summary['avg_hafalan_score'] }}</div>
                        <div class="mt-1 text-xs text-gray-500">Skala penilaian 100</div>
                    </div>
                </div>

                <!-- Chart container -->
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                    <div class="mb-4 border-b border-gray-150 dark:border-zinc-800 pb-3 flex justify-between items-center flex-wrap gap-2">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                Tren Aktivitas Perkembangan Kelas: {{ $selectedClass->name }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">
                                Grafik garis membandingkan volume setoran hafalan baru dengan murajaah selama periode terpilih.
                            </p>
                        </div>
                        <div class="flex gap-4 text-xs font-semibold">
                            <span class="flex items-center gap-1.5 text-zinc-500">
                                <span class="w-3.5 h-3.5 bg-teal-550 rounded"></span> Hafalan Baru
                            </span>
                            <span class="flex items-center gap-1.5 text-zinc-500">
                                <span class="w-3.5 h-3.5 bg-amber-500 rounded"></span> Murajaah
                            </span>
                        </div>
                    </div>

                    <div class="relative w-full overflow-hidden" style="height: 320px;">
                        <canvas id="periodicTrendChart"></canvas>
                    </div>
                </div>

                <!-- Detailed table per student -->
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-gray-150 dark:border-zinc-800 bg-zinc-50/30 dark:bg-zinc-900/50">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Rincian Perkembangan Santri</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Daftar akumulasi aktivitas setoran masing-masing santri selama periode terpilih.</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                            <thead class="bg-zinc-50 dark:bg-zinc-900/30 text-left text-xs font-semibold text-zinc-500 uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">Nama Santri</th>
                                    <th class="px-6 py-4 text-center">Jumlah Hafalan</th>
                                    <th class="px-6 py-4 text-center">Jumlah Murajaah</th>
                                    <th class="px-6 py-4 text-center">Nilai Rata-rata</th>
                                    <th class="px-6 py-4">Perkembangan Terakhir</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800/60">
                                @forelse ($studentReports as $row)
                                    <tr class="hover:bg-zinc-550/[0.01] dark:hover:bg-white/[0.01]">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $row['student']->name }}</div>
                                            <div class="text-xs text-gray-400 mt-0.5">NIS: {{ $row['student']->student_number ?: '-' }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm font-medium text-teal-600 dark:text-teal-400">
                                            {{ $row['total_hafalan'] }} kali
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm font-medium text-amber-600 dark:text-amber-450">
                                            {{ $row['total_murajaah'] }} kali
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm font-extrabold text-gray-900 dark:text-white">
                                            {{ $row['avg_score'] ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-zinc-400 max-w-xs truncate">
                                            {{ $row['latest_progress'] }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-zinc-500">
                                            Tidak ada data perkembangan santri pada rentang waktu ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-100 dark:border-yellow-900/30 rounded-2xl p-6 text-center text-yellow-800 dark:text-yellow-400">
                     Belum ada kelas yang dapat Anda akses atau tidak ada data santri terdaftar.
                </div>
            @endif

        </div>
    </div>

    @if ($selectedClass)
        <!-- ChartJS script -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('periodicTrendChart').getContext('2d');
                const isDark = document.documentElement.classList.contains('dark');
                
                const gridColor = isDark ? 'rgba(63, 63, 70, 0.3)' : 'rgba(228, 228, 231, 0.8)';
                const labelColor = isDark ? '#a1a1aa' : '#71717a';

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json($chartLabels),
                        datasets: [
                            {
                                label: 'Hafalan Baru',
                                data: @json($hafalanTrend),
                                borderColor: '#0d9488',
                                backgroundColor: 'rgba(13, 148, 136, 0.08)',
                                borderWidth: 3.5,
                                tension: 0.3,
                                fill: true,
                                pointBackgroundColor: '#0d9488',
                                pointHoverRadius: 6,
                            },
                            {
                                label: 'Murajaah',
                                data: @json($murajaahTrend),
                                borderColor: '#d97706',
                                backgroundColor: 'rgba(217, 119, 6, 0.08)',
                                borderWidth: 3.5,
                                tension: 0.3,
                                fill: true,
                                pointBackgroundColor: '#d97706',
                                pointHoverRadius: 6,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                grid: {
                                    color: gridColor
                                },
                                ticks: {
                                    color: labelColor,
                                    stepSize: 1,
                                    precision: 0
                                },
                                beginAtZero: true
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: labelColor
                                }
                            }
                        }
                    }
                });
            });
        </script>
    @endif
</x-app-layout>
