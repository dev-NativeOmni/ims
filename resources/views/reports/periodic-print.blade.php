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
            .print-border {
                border-color: #000000 !important;
            }
        }
        @page {
            size: A4 portrait;
            margin: 1.5cm;
        }
    </style>
</head>
<body class="bg-gray-50 text-zinc-900 min-h-screen p-4 sm:p-8">

    <!-- Top Action bar (no-print) -->
    <div class="no-print max-w-4xl mx-auto mb-6 flex justify-between items-center bg-white p-4 rounded-xl border border-zinc-200 shadow-sm">
        <span class="text-sm text-zinc-500">Laporan cetak siap dikirim ke Kepala Sekolah.</span>
        <div class="flex gap-2">
            <button onclick="window.close()" class="px-4 py-2 border border-zinc-300 rounded-lg text-sm font-semibold text-zinc-700 bg-white hover:bg-zinc-50">
                Tutup Halaman
            </button>
            <button onclick="window.print()" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-semibold shadow-sm">
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
                LAPORAN PERKEMBANGAN {{ $periodType === 'monthly' ? 'BULANAN' : 'TIGA BULANAN (TERM)' }}
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
        <div class="grid grid-cols-4 border border-zinc-300 rounded-xl mb-6 divide-x divide-zinc-300 text-center">
            <div class="p-3">
                <span class="block text-[10px] font-bold text-zinc-400 uppercase">Total Santri</span>
                <span class="text-lg font-extrabold text-zinc-900 mt-1 block">{{ $summary['total_students'] }}</span>
            </div>
            <div class="p-3">
                <span class="block text-[10px] font-bold text-zinc-400 uppercase">Hafalan Baru</span>
                <span class="text-lg font-extrabold text-zinc-900 mt-1 block">{{ $summary['total_hafalan'] }}</span>
            </div>
            <div class="p-3">
                <span class="block text-[10px] font-bold text-zinc-400 uppercase">Murajaah</span>
                <span class="text-lg font-extrabold text-zinc-900 mt-1 block">{{ $summary['total_murajaah'] }}</span>
            </div>
            <div class="p-3">
                <span class="block text-[10px] font-bold text-zinc-400 uppercase">Rerata Nilai</span>
                <span class="text-lg font-extrabold text-zinc-900 mt-1 block">{{ $summary['avg_hafalan_score'] }}</span>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="mb-8 border border-zinc-200 rounded-xl p-4">
            <h3 class="text-xs font-bold text-zinc-900 mb-3 uppercase tracking-wider">Grafik Tren Progres Kelas</h3>
            <div class="relative w-full overflow-hidden" style="height: 240px;">
                <canvas id="printTrendChart"></canvas>
            </div>
        </div>

        <!-- Detailed List of Students -->
        <div class="mb-12">
            <h3 class="text-xs font-bold text-zinc-900 mb-3 uppercase tracking-wider">Rincian Perkembangan Santri</h3>
            <table class="min-w-full border-collapse border border-zinc-350 text-left text-xs">
                <thead>
                    <tr class="bg-zinc-100 border-b border-zinc-350">
                        <th class="border border-zinc-350 px-4 py-2.5 font-bold text-zinc-800">Nama Santri</th>
                        <th class="border border-zinc-350 px-4 py-2.5 text-center font-bold text-zinc-800 w-24">Jumlah Hafalan</th>
                        <th class="border border-zinc-350 px-4 py-2.5 text-center font-bold text-zinc-800 w-24">Jumlah Murajaah</th>
                        <th class="border border-zinc-350 px-4 py-2.5 text-center font-bold text-zinc-800 w-20">Rerata Nilai</th>
                        <th class="border border-zinc-350 px-4 py-2.5 font-bold text-zinc-800">Setoran Terakhir</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200">
                    @forelse ($studentReports as $row)
                        <tr>
                            <td class="border border-zinc-350 px-4 py-2 font-semibold text-zinc-900">{{ $row['student']->name }}</td>
                            <td class="border border-zinc-350 px-4 py-2 text-center text-zinc-700">{{ $row['total_hafalan'] }} kali</td>
                            <td class="border border-zinc-350 px-4 py-2 text-center text-zinc-700">{{ $row['total_murajaah'] }} kali</td>
                            <td class="border border-zinc-350 px-4 py-2 text-center font-bold text-zinc-900">{{ $row['avg_score'] ?? '-' }}</td>
                            <td class="border border-zinc-350 px-4 py-2 text-zinc-600 max-w-xs truncate">{{ $row['latest_progress'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="border border-zinc-350 px-4 py-6 text-center text-zinc-500">Tidak ada data perkembangan pada rentang waktu ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Signature Block -->
        <div class="grid grid-cols-2 text-center text-xs mt-16 pb-8">
            <div>
                <p class="text-zinc-500">Mengetahui,</p>
                <p class="font-bold text-zinc-900 mt-0.5">Kepala Sekolah Lembaga</p>
                <div class="h-20"></div>
                <p class="font-bold text-zinc-900 border-b border-zinc-400 w-48 mx-auto pb-1">_______________________</p>
                <p class="text-zinc-400 mt-1">NIP. .............................</p>
            </div>
            
            <div>
                <p class="text-zinc-500">Tanggal: {{ now()->format('d M Y') }}</p>
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
            const ctx = document.getElementById('printTrendChart').getContext('2d');

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartLabels),
                    datasets: [
                        {
                            label: 'Hafalan Baru',
                            data: @json($hafalanTrend),
                            borderColor: '#0d9488',
                            backgroundColor: 'rgba(13, 148, 136, 0.04)',
                            borderWidth: 2,
                            tension: 0.25,
                            fill: true,
                            pointBackgroundColor: '#0d9488',
                            pointRadius: 4,
                        },
                        {
                            label: 'Murajaah',
                            data: @json($murajaahTrend),
                            borderColor: '#d97706',
                            backgroundColor: 'rgba(217, 119, 6, 0.04)',
                            borderWidth: 2,
                            tension: 0.25,
                            fill: true,
                            pointBackgroundColor: '#d97706',
                            pointRadius: 4,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false, // Turn off animation for instant print compatibility
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            ticks: {
                                stepSize: 1,
                                precision: 0
                            },
                            beginAtZero: true
                        }
                    }
                }
            });

            // Automatically open print dialog when chart is fully rendered
            setTimeout(function() {
                window.print();
            }, 600);
        });
    </script>
</body>
</html>
