<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Masal Rapor Digital Kelas - {{ $classRoom->name }}</title>
    @vite(['resources/css/app.css'])
    <!-- Tailwind CSS fallback for standalone print -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: 215mm 330mm;
            margin: 10mm 12mm 10mm 12mm;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
                color: black !important;
                margin: 0 !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .page-break {
                page-break-before: always;
                break-before: page;
            }
            .print-container {
                min-height: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .signature-block, .report-section, tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
        body {
            font-family: 'Times New Roman', 'Liberation Serif', serif;
        }
        .report-table {
            border-collapse: collapse;
            width: 100%;
        }
        .report-table th, .report-table td {
            border: 1px solid #000;
        }
    </style>
</head>
<body class="bg-zinc-100 text-gray-900 p-4 sm:p-8" x-data="{ paperSize: 'f4' }" :class="{ 'max-w-[215mm]': paperSize === 'f4', 'max-w-[210mm]': paperSize === 'a4' }">

    <!-- Floating Action Toolbar for bulk print preview (hidden during print) -->
    <div class="max-w-4xl mx-auto mb-6 flex flex-wrap justify-between items-center gap-3 no-print bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md p-4 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-lg">
        <div class="flex items-center gap-3">
            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 animate-pulse"></span>
            <div>
                <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                    <x-heroicon-o-document-text class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                    <span>Cetak Masal Rapor Kelas: {{ $classRoom->name }}</span>
                </h4>
                <p class="text-xs text-gray-500 dark:text-zinc-400">
                    Total: {{ count($sheets) }} Santri &bull; {{ \App\Http\Controllers\StudentReportController::REPORT_PERIODS[$term] }} {{ $academicYear }}
                </p>
                @if ($lockedAt)
                    <p class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-rose-600 dark:text-rose-400">
                        <x-heroicon-o-lock-closed class="w-3.5 h-3.5" /> Terkunci {{ $lockedAt->locale('id')->translatedFormat('d F Y H:i') }} &mdash; data rapor dibekukan
                    </p>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2">
            <!-- Paper Size Selector -->
            <div class="flex items-center bg-gray-100 dark:bg-zinc-800 p-1 rounded-xl text-xs font-semibold">
                <button type="button" @click="paperSize = 'f4'" :class="paperSize === 'f4' ? 'bg-white dark:bg-zinc-700 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700'" class="px-2.5 py-1 rounded-lg transition">
                    F4 (Folio)
                </button>
                <button type="button" @click="paperSize = 'a4'" :class="paperSize === 'a4' ? 'bg-white dark:bg-zinc-700 shadow-sm text-indigo-600 dark:text-indigo-400' : 'text-gray-500 hover:text-gray-700'" class="px-2.5 py-1 rounded-lg transition">
                    A4
                </button>
            </div>

            <button onclick="window.close()" class="px-3.5 py-2 border border-gray-200 dark:border-zinc-700 rounded-xl text-xs font-semibold text-gray-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 hover:bg-gray-50 transition cursor-pointer">
                Tutup
            </button>
            <button onclick="window.print()" class="px-4 py-2 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 text-white rounded-xl text-xs font-bold shadow-md shadow-indigo-600/20 transition flex items-center gap-1.5 cursor-pointer">
                <x-heroicon-o-printer class="w-4 h-4" />
                <span>Cetak Semua Rapor</span>
            </button>
        </div>
    </div>

    @foreach ($sheets as $index => $sheet)
        @include('reports.partials.report-sheet', ['sheet' => $sheet, 'pageBreak' => $index > 0])
    @endforeach

    <!-- Auto Print Trigger script -->
    <script>
        window.addEventListener('DOMContentLoaded', (event) => {
            // Auto open print dialog
            setTimeout(() => {
                window.print();
            }, 800);
        });
    </script>
</body>
</html>
