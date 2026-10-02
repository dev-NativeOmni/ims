<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapor Digital Terpadu - {{ $sheet['student']['name'] }}</title>
    @vite(['resources/css/app.css'])
    <!-- Tailwind CSS fallback for standalone print -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Lembar rapor = satu halaman F4 penuh berbingkai (lihat .print-container & .rapor-border). */
        @page {
            size: 215mm 330mm;
            margin: 0;
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
                height: 330mm !important;
                min-height: 0 !important;
                margin: 0 !important;
                border: none !important;
                box-shadow: none !important;
                overflow: hidden;
            }
            .signature-block, .report-section, tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
        body {
            font-family: 'Times New Roman', 'Liberation Serif', serif;
        }
        /* Lembar F4 berbingkai: isi berada di dalam garis dalam bingkai (garis di 18,2 mm dari tepi). */
        .print-container {
            position: relative;
            box-sizing: border-box;
            width: 215mm;
            min-height: 330mm;
            padding: 21mm 23mm;
        }
        .rapor-border {
            position: absolute;
            top: 0;
            left: 0;
            width: 215mm;
            height: 330mm;
            pointer-events: none;
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

    <!-- Floating Action Toolbar for print preview (hidden during print) -->
    <div class="max-w-4xl mx-auto mb-6 flex flex-wrap justify-between items-center gap-3 no-print bg-white/95 dark:bg-zinc-900/95 backdrop-blur-md p-4 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-lg">
        <div class="flex items-center gap-3">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <div>
                <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                    <x-heroicon-o-document-text class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                    <span>Pratinjau Cetak Rapor Digital</span>
                </h4>
                <p class="text-xs text-gray-500 dark:text-zinc-400">
                    {{ $sheet['student']['name'] }} &bull; {{ $sheet['student']['class'] ?: '-' }} &bull; {{ $sheet['period_label'] }} {{ $sheet['academic_year'] }}
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
            <button onclick="window.print()" class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/20 transition flex items-center gap-1.5 cursor-pointer">
                <x-heroicon-o-printer class="w-4 h-4" />
                <span>Cetak / Simpan PDF</span>
            </button>
        </div>
    </div>

    @include('reports.partials.report-sheet', ['sheet' => $sheet])

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
