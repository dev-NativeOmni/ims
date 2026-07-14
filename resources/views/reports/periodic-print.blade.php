<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Perkembangan Berkala - {{ $selectedClass?->name ?? 'Kelas' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
            }
            .page-break {
                page-break-before: always;
            }
        }
        @page {
            size: A4 portrait;
            margin: 1.2cm;
        }
    </style>
</head>
<body class="bg-gray-50 text-zinc-900 min-h-screen p-4 sm:p-8 selection:bg-teal-500 selection:text-white">

    <!-- Top Action bar (no-print) -->
    <div class="no-print max-w-4xl mx-auto mb-6 flex justify-between items-center bg-white p-4 rounded-xl border border-zinc-200 shadow-sm">
        <span class="text-sm text-zinc-550">Laporan cetak siap dikirim ke Kepala Sekolah.</span>
        <div class="flex gap-2">
            <button onclick="window.close()" class="px-4 py-2 border border-zinc-300 rounded-lg text-sm font-semibold text-zinc-700 bg-white hover:bg-zinc-50 cursor-pointer">
                Tutup Halaman
            </button>
            <button onclick="window.print()" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-semibold shadow-sm cursor-pointer">
                Cetak Sekarang
            </button>
        </div>
    </div>

    <!-- Printable Report Sheet -->
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-2xl border border-zinc-200 shadow-sm print:border-none print:shadow-none">
        
        <!-- Header / Kop Surat -->
        <div class="text-center border-b-2 border-zinc-900 pb-6 mb-6">
            <h1 class="text-2xl font-extrabold tracking-tight text-zinc-950 uppercase">HAFIZPLUS MONITORING SYSTEM</h1>
            <p class="text-sm font-medium text-zinc-550 mt-1">Lembaga Tahfidz & Pendidikan Al-Qur'an Terpadu</p>
            <div class="mt-4 text-xs text-zinc-500 flex justify-center gap-4">
                <span>Tanggal Laporan: {{ now()->format('d M Y') }}</span>
                <span>•</span>
                <span>Dicetak Oleh: {{ Auth::user()->name }}</span>
            </div>
        </div>

        <!-- Title of Report -->
        <div class="mb-6 text-center">
            <h2 class="text-lg font-bold text-zinc-900 uppercase">
                LAPORAN KETUNTASAN PERKEMBANGAN {{ $periodType === 'monthly' ? 'BULANAN' : 'TIGA BULANAN (TERM)' }}
            </h2>
            <p class="text-sm text-zinc-650 mt-1">
                @if ($periodType === 'monthly')
                    Periode: {{ $monthsList[$selectedMonth] }} {{ $selectedYear }}
                @else
                    Periode: Term {{ $selectedQuarter }} ({{ $selectedQuarter == 1 ? 'Jul - Sep' : ($selectedQuarter == 2 ? 'Okt - Des' : ($selectedQuarter == 3 ? 'Jan - Mar' : 'Apr - Jun')) }}) {{ $selectedYear }}
                @endif
                · Kelas: {{ $selectedClass?->name ?? '-' }} ({{ $selectedClass?->program?->name ?? '-' }})
            </p>
        </div>

        <!-- Class Summary Metrics -->
        <div class="grid grid-cols-5 border border-zinc-300 rounded-xl mb-8 divide-x divide-zinc-300 text-center">
            <div class="p-3">
                <span class="block text-[10px] font-bold text-zinc-400 uppercase">Total Santri</span>
                <span class="text-lg font-extrabold text-zinc-900 mt-1 block">{{ $summary['total_students'] }}</span>
            </div>
            <div class="p-3">
                <span class="block text-[10px] font-bold text-zinc-400 uppercase">Tuntas</span>
                <span class="text-lg font-extrabold text-blue-600 mt-1 block">{{ $tuntasCount }}</span>
            </div>
            <div class="p-3">
                <span class="block text-[10px] font-bold text-zinc-400 uppercase">Tidak Tuntas</span>
                <span class="text-lg font-extrabold text-rose-600 mt-1 block">{{ $tidakTuntasCount }}</span>
            </div>
            <div class="p-3">
                <span class="block text-[10px] font-bold text-zinc-400 uppercase">Rerata Nilai</span>
                <span class="text-lg font-extrabold text-zinc-900 mt-1 block">{{ $summary['avg_hafalan_score'] }}</span>
            </div>
            <div class="p-3">
                <span class="block text-[10px] font-bold text-zinc-400 uppercase">% Tuntas</span>
                <span class="text-lg font-extrabold text-emerald-600 mt-1 block">
                    {{ $summary['total_students'] > 0 ? round(($tuntasCount / $summary['total_students']) * 100, 1) : 0 }}%
                </span>
            </div>
        </div>

        <!-- Chart 1: Bar Chart (Grafik Ketuntasan) -->
        <div class="mb-10 border border-zinc-200 rounded-2xl p-6 bg-white shadow-sm page-break-after">
            <h3 class="text-sm font-bold text-zinc-800 text-center uppercase tracking-wider mb-2">
                GRAFIK KETUNTASAN TAHFIDZ KELAS {{ $selectedClass?->name ?? 'KELAS' }} BULAN {{ strtoupper($monthsList[$selectedMonth] ?? 'BULAN') }}
            </h3>
            
            <!-- Custom Legend matching the spreadsheet image -->
            <div class="flex justify-center items-center gap-6 text-[10px] font-semibold text-zinc-600 mb-4">
                <span class="flex items-center gap-1.5">
                    <span class="w-3.5 h-3 bg-[#4f81bd]"></span> CAPAIAN
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3.5 h-0.5 bg-[#c00000] relative flex items-center justify-center">
                        <span class="w-1.5 h-1.5 bg-[#c00000] rounded-full absolute"></span>
                    </span> TARGET
                </span>
            </div>

            <div class="relative w-full overflow-hidden" style="height: 320px;">
                <canvas id="barCompletenessChart"></canvas>
            </div>
        </div>

        <!-- Page break for cleaner print layout -->
        <div class="page-break"></div>

        <!-- Chart 2: Pie Chart (Diagram Ketuntasan) -->
        <div class="mb-10 border border-zinc-200 rounded-2xl p-6 bg-white shadow-sm">
            <h3 class="text-sm font-bold text-zinc-800 text-center uppercase tracking-wider mb-6">
                DIAGRAM KETUNTASAN TAHFIDZ KELAS {{ $selectedClass?->name ?? 'KELAS' }} BULAN {{ strtoupper($monthsList[$selectedMonth] ?? 'BULAN') }}
            </h3>
            <div class="relative w-full overflow-hidden flex justify-center items-center" style="height: 300px;">
                <div class="w-72 h-72">
                    <canvas id="pieCompletenessChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Detailed List of Students -->
        <div class="mb-10">
            <h3 class="text-xs font-bold text-zinc-900 mb-3 uppercase tracking-wider">Rincian Capaian Dan Ketuntasan Santri</h3>
            <table class="min-w-full border-collapse border border-zinc-350 text-left text-xs">
                <thead>
                    <tr class="bg-zinc-100 border-b border-zinc-350">
                        <th class="border border-zinc-350 px-4 py-2.5 font-bold text-zinc-800">Nama Santri</th>
                        <th class="border border-zinc-350 px-4 py-2.5 text-center font-bold text-zinc-800 w-24">Target (Baris)</th>
                        <th class="border border-zinc-350 px-4 py-2.5 text-center font-bold text-zinc-800 w-24">Capaian (Baris)</th>
                        <th class="border border-zinc-350 px-4 py-2.5 text-center font-bold text-zinc-800 w-28">Status</th>
                        <th class="border border-zinc-350 px-4 py-2.5 text-center font-bold text-zinc-800 w-24">Jumlah Setoran</th>
                        <th class="border border-zinc-350 px-4 py-2.5 font-bold text-zinc-800">Setoran Terakhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200">
                    @forelse ($studentReports as $row)
                        <tr>
                            <td class="border border-zinc-350 px-4 py-2 font-semibold text-zinc-900">{{ $row['student']->name }}</td>
                            <td class="border border-zinc-350 px-4 py-2 text-center text-zinc-700 font-medium">{{ $row['target_baris'] }} baris</td>
                            <td class="border border-zinc-350 px-4 py-2 text-center text-zinc-700 font-semibold">{{ $row['capaian_baris'] }} baris</td>
                            <td class="border border-zinc-350 px-4 py-2 text-center">
                                @if ($row['is_tuntas'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">Tuntas</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 uppercase">Belum Tuntas</span>
                                @endif
                            </td>
                            <td class="border border-zinc-350 px-4 py-2 text-center text-zinc-650">{{ $row['total_hafalan'] }} kali</td>
                            <td class="border border-zinc-350 px-4 py-2 text-zinc-600 max-w-xs truncate">{{ $row['latest_progress'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="border border-zinc-350 px-4 py-6 text-center text-zinc-550">Tidak ada data perkembangan pada rentang waktu ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Signature Block -->
        <div class="grid grid-cols-2 text-center text-xs mt-16 pb-8">
            <div>
                <p class="text-zinc-550">Mengetahui,</p>
                <p class="font-bold text-zinc-900 mt-0.5">Kepala Sekolah Lembaga</p>
                <div class="h-20"></div>
                <p class="font-bold text-zinc-900 border-b border-zinc-400 w-48 mx-auto pb-1">_______________________</p>
                <p class="text-zinc-400 mt-1">NIP. .............................</p>
            </div>
            
            <div>
                <p class="text-zinc-550">Tanggal: {{ now()->format('d M Y') }}</p>
                <p class="font-bold text-zinc-900 mt-0.5">Guru Pembimbing / Wali Kelas</p>
                <div class="h-20"></div>
                <p class="font-bold text-zinc-900 border-b border-zinc-400 w-48 mx-auto pb-1">{{ Auth::user()->name }}</p>
                <p class="text-zinc-400 mt-1">ID Guru. {{ Auth::user()->id }}</p>
            </div>
        </div>

    </div>

    <!-- Chart rendering script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Data Prep
            const labels = @json(collect($studentReports)->pluck('student.name'));
            const capaianData = @json(collect($studentReports)->pluck('capaian_baris'));
            const targetData = @json(collect($studentReports)->pluck('target_baris'));

            // 1. Combo Bar & Line Chart (Grafik Ketuntasan)
            const barCtx = document.getElementById('barCompletenessChart').getContext('2d');
            new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'CAPAIAN',
                            data: capaianData,
                            backgroundColor: '#4f81bd',
                            borderColor: '#385d8a',
                            borderWidth: 1,
                            barPercentage: 0.6,
                            categoryPercentage: 0.8
                        },
                        {
                            type: 'line',
                            label: 'TARGET',
                            data: targetData,
                            borderColor: '#c00000',
                            backgroundColor: '#c00000',
                            borderWidth: 2.5,
                            tension: 0, // Straight connecting lines
                            fill: false,
                            pointStyle: 'circle',
                            pointRadius: 4,
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false, // For print compatibility
                    plugins: {
                        legend: {
                            display: false // Using our custom HTML legend above
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#e5e7eb'
                            },
                            ticks: {
                                color: '#4b5563',
                                font: {
                                    size: 10
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                autoSkip: false,
                                maxRotation: 90,
                                minRotation: 90,
                                color: '#4b5563',
                                font: {
                                    size: 9,
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                }
            });

            // 2. Pie Chart (Diagram Ketuntasan)
            const pieCtx = document.getElementById('pieCompletenessChart').getContext('2d');
            const tuntasVal = {{ $tuntasCount }};
            const tidakTuntasVal = {{ $tidakTuntasCount }};
            const totalVal = tuntasVal + tidakTuntasVal;
            
            const tuntasPct = totalVal > 0 ? ((tuntasVal / totalVal) * 100).toFixed(1) : '0.0';
            const tidakTuntasPct = totalVal > 0 ? ((tidakTuntasVal / totalVal) * 100).toFixed(1) : '0.0';

            new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: [
                        `TUNTAS (${tuntasPct}%)`, 
                        `TIDAK TUNTAS (${tidakTuntasPct}%)`
                    ],
                    datasets: [{
                        data: [tuntasVal, tidakTuntasVal],
                        backgroundColor: ['#4f81bd', '#c00000'],
                        borderColor: '#ffffff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                color: '#374151',
                                font: {
                                    size: 11,
                                    weight: 'bold'
                                },
                                padding: 15
                            }
                        }
                    }
                }
            });

            // Automatically open print dialog when chart is fully rendered
            setTimeout(function() {
                window.print();
            }, 750);
        });
    </script>
</body>
</html>
