@if (!empty($isGrade10))
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan UMMI Landscape — {{ $selectedClass?->name ?? 'Kelas 10' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            @page {
                size: 330mm 215mm landscape; /* F4 / A4 Landscape */
                margin: 0.3cm;
            }
            html, body {
                height: 100%;
                background: white !important;
                color: black !important;
                padding: 0 !important;
                margin: 0 !important;
                overflow: hidden !important;
            }
            #ummiGrade10ReportCard {
                box-shadow: none !important;
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 auto !important;
                padding: 0.75rem 1rem !important;
                border-width: 4px !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 text-zinc-900 p-4 sm:p-8">
    <!-- Top Action Bar (no-print) -->
    <div class="no-print max-w-5xl mx-auto mb-6 flex justify-between items-center bg-white p-4 rounded-2xl border border-zinc-200 shadow-sm">
        <div>
            <h3 class="text-sm font-bold text-zinc-900">Laporan Capaian Tahfidz UMMI — {{ $selectedClass?->name }}</h3>
            <p class="text-xs text-zinc-500">Format Halaman: F4 / A4 Landscape</p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.close()" class="px-4 py-2 border border-zinc-300 rounded-xl text-xs font-bold text-zinc-700 bg-white hover:bg-zinc-50 cursor-pointer">
                Tutup
            </button>
            <button onclick="window.print()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm cursor-pointer flex items-center gap-1.5">
                🖨️ Cetak Sekarang (F4 / A4 Landscape)
            </button>
        </div>
    </div>

    <!-- Printable Landscape Sheet -->
    <div class="max-w-5xl mx-auto">
        @include('reports.partials.ummi-grade10-card')
    </div>
</body>
</html>
@else
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
            <p class="text-sm text-zinc-655 mt-1">
                @if ($periodType === 'monthly')
                    Periode: {{ $monthsList[$selectedMonth] ?? '-' }} {{ $selectedYear }}
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
                <span class="text-lg font-extrabold text-zinc-900 mt-1 block">{{ $summary['total_students'] ?? 0 }}</span>
            </div>
            <div class="p-3">
                <span class="block text-[10px] font-bold text-zinc-400 uppercase">Tuntas</span>
                <span class="text-lg font-extrabold text-blue-600 mt-1 block">{{ $tuntasCount ?? 0 }}</span>
            </div>
            <div class="p-3">
                <span class="block text-[10px] font-bold text-zinc-400 uppercase">Tidak Tuntas</span>
                <span class="text-lg font-extrabold text-rose-600 mt-1 block">{{ $tidakTuntasCount ?? 0 }}</span>
            </div>
            <div class="p-3">
                <span class="block text-[10px] font-bold text-zinc-400 uppercase">Rerata Nilai</span>
                <span class="text-lg font-extrabold text-zinc-900 mt-1 block">{{ $summary['avg_hafalan_score'] ?? 0 }}</span>
            </div>
            <div class="p-3">
                <span class="block text-[10px] font-bold text-zinc-400 uppercase">% Tuntas</span>
                <span class="text-lg font-extrabold text-emerald-600 mt-1 block">
                    {{ ($summary['total_students'] ?? 0) > 0 ? round((($tuntasCount ?? 0) / $summary['total_students']) * 100, 1) : 0 }}%
                </span>
            </div>
        </div>

        <!-- Chart 1: Bar Chart (Grafik Ketuntasan) -->
        <div class="mb-10 border border-zinc-200 rounded-2xl p-6 bg-white shadow-sm page-break-after">
            <h3 class="text-sm font-bold text-zinc-800 text-center uppercase tracking-wider mb-2">
                Grafik Ketuntasan Target Setoran Per Murid
            </h3>
            <div class="relative w-full h-[320px]">
                <canvas id="studentCompletenessChart"></canvas>
            </div>
        </div>

        <!-- Chart 2: Pie Chart (Distribusi Ketuntasan) -->
        <div class="mb-8 border border-zinc-200 rounded-2xl p-6 bg-white shadow-sm">
            <h3 class="text-sm font-bold text-zinc-800 text-center uppercase tracking-wider mb-2">
                Persentase Ketuntasan Kelas
            </h3>
            <div class="relative w-full h-[220px]">
                <canvas id="pieCompletenessChart"></canvas>
            </div>
        </div>

        <!-- Student Detail Table -->
        <div class="mt-8 overflow-hidden rounded-xl border border-zinc-300">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-zinc-100 text-zinc-700 font-bold border-b border-zinc-300">
                        <th class="py-2 px-3 border-r border-zinc-300 w-10 text-center">No</th>
                        <th class="py-2 px-3 border-r border-zinc-300">Nama Murid</th>
                        <th class="py-2 px-3 border-r border-zinc-300 text-center">Setoran (Target vs Realisasi)</th>
                        <th class="py-2 px-3 border-r border-zinc-300 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200">
                    @forelse ($studentReports as $idx => $r)
                        <tr>
                            <td class="py-2 px-3 text-center border-r border-zinc-200 font-semibold text-zinc-500">{{ $idx + 1 }}</td>
                            <td class="py-2 px-3 border-r border-zinc-200 font-bold text-zinc-900">{{ $r['student_name'] }}</td>
                            <td class="py-2 px-3 border-r border-zinc-200 text-center font-semibold text-zinc-700">
                                {{ $r['completed_targets'] }} / {{ $r['total_targets'] }} Target
                            </td>
                            <td class="py-2 px-3 text-center font-bold">
                                @if ($r['completed_targets'] >= $r['total_targets'] && $r['total_targets'] > 0)
                                    <span class="text-blue-600 uppercase">TUNTAS</span>
                                @else
                                    <span class="text-rose-600 uppercase">TIDAK TUNTAS</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-4 text-center text-zinc-500">Tidak ada data murid.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Chart rendering logic for non-Grade 10 -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const barCtx = document.getElementById('studentCompletenessChart')?.getContext('2d');
            if (barCtx) {
                const labels = {!! json_encode(array_column($studentReports, 'student_name')) !!};
                const targets = {!! json_encode(array_column($studentReports, 'total_targets')) !!};
                const completed = {!! json_encode(array_column($studentReports, 'completed_targets')) !!};

                new Chart(barCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Target Setoran',
                                data: targets,
                                backgroundColor: '#4f81bd',
                            },
                            {
                                label: 'Terealisasi',
                                data: completed,
                                backgroundColor: '#9bbb59',
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                    }
                });
            }

            const pieCtx = document.getElementById('pieCompletenessChart')?.getContext('2d');
            if (pieCtx) {
                new Chart(pieCtx, {
                    type: 'pie',
                    data: {
                        labels: ['TUNTAS', 'TIDAK TUNTAS'],
                        datasets: [{
                            data: [{{ $tuntasCount ?? 0 }}, {{ $tidakTuntasCount ?? 0 }}],
                            backgroundColor: ['#4f81bd', '#c00000']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                    }
                });
            }
        });
    </script>
</body>
</html>
@endif
