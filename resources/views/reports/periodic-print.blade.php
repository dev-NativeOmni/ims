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
            <h1 class="text-2xl font-extrabold tracking-tight text-zinc-950 uppercase">IMS MONITORING SYSTEM</h1>
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
                <span class="block text-[10px] font-bold text-zinc-400 uppercase">Total Murid</span>
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

        <!-- Detailed List of Students grouped by Teacher & Halaqah -->
        <div class="mb-10 space-y-10">
            <h3 class="text-xs font-bold text-zinc-900 mb-3 uppercase tracking-wider">Rincian Capaian Dan Ketuntasan Murid per Halaqah</h3>
            
            @forelse ($groupedReports as $teacherName => $halaqahs)
                <div class="space-y-6" style="page-break-inside: avoid; break-inside: avoid;">
                    <!-- Teacher Banner -->
                    <div class="border-b-2 border-zinc-900 pb-1.5 mt-4">
                        <h4 class="text-xs font-bold text-zinc-950 uppercase tracking-wide">
                            PEMBIMBING (USTADZ/USTADZAH): {{ $teacherName }}
                        </h4>
                    </div>

                    @foreach ($halaqahs as $halaqahLabel => $reports)
                        <div class="mb-6" style="page-break-inside: avoid; break-inside: avoid;">
                            <!-- Halaqah Header -->
                            <div class="flex justify-between items-center bg-zinc-50 border border-zinc-350 px-4 py-1.5 mb-1.5">
                                <span class="text-[10px] font-bold text-zinc-900 uppercase">Halaqah: {{ $halaqahLabel }}</span>
                                <span class="text-[9px] text-zinc-500 font-semibold">{{ count($reports) }} Murid</span>
                            </div>

                            <table class="min-w-full border-collapse border border-zinc-350 text-left text-[11px]">
                                <thead>
                                    <!-- Row 1: Header groups -->
                                    <tr class="bg-zinc-100 border-b border-zinc-350 text-center font-bold text-zinc-800">
                                        <th rowspan="2" class="border border-zinc-350 px-3 py-2 text-left w-10 align-middle">No</th>
                                        <th rowspan="2" class="border border-zinc-350 px-3 py-2 text-left align-middle min-w-[150px]">Nama Murid</th>
                                        <th rowspan="2" class="border border-zinc-350 px-3 py-2 align-middle">Halaqah</th>
                                        <th colspan="2" class="border border-zinc-350 px-2 py-1">Target</th>
                                        <th colspan="2" class="border border-zinc-350 px-2 py-1">Capaian</th>
                                        <th rowspan="2" class="border border-zinc-350 px-3 py-2 align-middle">Ketercapaian</th>
                                        <th colspan="3" class="border border-zinc-350 px-2 py-1">Kehadiran</th>
                                        <th rowspan="2" class="border border-zinc-350 px-3 py-2 align-middle w-20">Pelanggaran</th>
                                    </tr>
                                    <!-- Row 2: Header subcolumns -->
                                    <tr class="bg-zinc-100 border-b border-zinc-350 text-center font-semibold text-[9px] text-zinc-700">
                                        <th class="border border-zinc-350 px-2 py-1 font-semibold">Surah</th>
                                        <th class="border border-zinc-350 px-2 py-1 w-12 font-semibold">Ayat</th>
                                        <th class="border border-zinc-350 px-2 py-1 border-l font-semibold">Surat</th>
                                        <th class="border border-zinc-350 px-2 py-1 w-12 font-semibold">Ayat</th>
                                        <th class="border border-zinc-350 px-1 py-1 text-rose-700 w-8 font-bold">A</th>
                                        <th class="border border-zinc-350 px-1 py-1 text-amber-600 w-8 font-bold">I</th>
                                        <th class="border border-zinc-350 px-1 py-1 text-blue-600 w-8 font-bold">S</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($reports as $index => $row)
                                        <tr class="border-b border-zinc-350 text-center">
                                            <td class="border border-zinc-350 px-3 py-1.5 text-left text-zinc-500">{{ $index + 1 }}</td>
                                            <td class="border border-zinc-350 px-3 py-1.5 text-left font-bold text-zinc-900">
                                                {{ $row['student']->name }}
                                                <div class="text-[9px] text-zinc-400 font-normal mt-0.5">NIS: {{ $row['student']->student_number ?: '-' }}</div>
                                            </td>
                                            <td class="border border-zinc-350 px-3 py-1.5 text-zinc-650">{{ $row['halaqah_label'] }}</td>
                                            <td class="border border-zinc-350 px-2 py-1.5 text-zinc-700 font-medium">{{ $row['target_surah'] }}</td>
                                            <td class="border border-zinc-350 px-2 py-1.5 text-zinc-900 font-semibold text-center">{{ $row['target_ayat'] }}</td>
                                            <td class="border border-zinc-350 px-2 py-1.5 text-zinc-700 font-medium">{{ $row['capaian_surah'] }}</td>
                                            <td class="border border-zinc-350 px-2 py-1.5 text-zinc-900 font-bold text-center">{{ $row['capaian_ayat'] }}</td>
                                            <td class="border border-zinc-350 px-3 py-1.5">
                                                @if ($row['is_tuntas'])
                                                    <span class="text-emerald-700 font-bold uppercase text-[9px]">Tuntas</span>
                                                @else
                                                    <span class="text-rose-700 font-bold uppercase text-[9px]">Belum Tuntas</span>
                                                @endif
                                            </td>
                                            <!-- Kehadiran (A/I/S) defaults to - as it is not tracked in DB -->
                                            <td class="border border-zinc-350 px-1 py-1.5 text-zinc-400">-</td>
                                            <td class="border border-zinc-350 px-1 py-1.5 text-zinc-400">-</td>
                                            <td class="border border-zinc-350 px-1 py-1.5 text-zinc-400">-</td>
                                            <td class="border border-zinc-350 px-3 py-1.5 font-bold {{ $row['violations_count'] > 0 ? 'text-rose-700' : 'text-zinc-400' }}">
                                                {{ $row['violations_count'] }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    <!-- Summary Row -->
                                    <tr class="bg-zinc-50 font-bold text-zinc-900">
                                        <td colspan="3" class="border border-zinc-350 px-3 py-1.5 text-right uppercase text-[10px]">Prosentase Ketuntasan:</td>
                                        @php
                                            $totalGroup = count($reports);
                                            $tuntasGroup = collect($reports)->where('is_tuntas', true)->count();
                                            $tidakTuntasGroup = $totalGroup - $tuntasGroup;
                                            $tuntasPct = $totalGroup > 0 ? round(($tuntasGroup / $totalGroup) * 100, 1) : 0;
                                            $tidakTuntasPct = $totalGroup > 0 ? round(($tidakTuntasGroup / $totalGroup) * 100, 1) : 0;
                                        @endphp
                                        <td colspan="2" class="border border-zinc-350 px-2 py-1.5 text-center text-emerald-700">Tuntas: {{ $tuntasPct }}%</td>
                                        <td colspan="2" class="border border-zinc-350 px-2 py-1.5 text-center text-rose-700">Belum Tuntas: {{ $tidakTuntasPct }}%</td>
                                        <td colspan="5" class="border border-zinc-350 px-3 py-1.5 text-center text-zinc-500 font-medium">
                                            Tuntas: {{ $tuntasGroup }} murid, Belum Tuntas: {{ $tidakTuntasGroup }} murid
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="border border-zinc-300 p-8 text-center text-zinc-500 text-xs rounded-xl">
                    Tidak ada data perkembangan pada rentang waktu ini.
                </div>
            @endforelse
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
