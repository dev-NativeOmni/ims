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
        /* Ukuran lembar & bingkai: lihat blok style di partials/report-sheet. */
        .report-table {
            border-collapse: collapse;
            width: 100%;
        }
        .report-table th, .report-table td {
            border: 1px solid #000;
        }
    </style>
</head>
<body class="text-gray-900 p-4 sm:p-8">

    @include('reports.partials.print-toolbar', [
        'title' => 'Pratinjau Cetak Rapor Digital',
        'subtitle' => $sheet['student']['name'].' • '.($sheet['student']['class'] ?: '-').' • '.$sheet['period_label'].' '.$sheet['academic_year'],
        'lockedAt' => $lockedAt,
        'printLabel' => 'Cetak / Simpan PDF',
    ])

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
