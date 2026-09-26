{{--
    Grafik capaian Ummi Kelas 10 (laporan periodik): per murid dua batang --
    Buku Ummi Dewasa (sumbu kiri, J1 h.1 - J3 h.40) dan hafalan surah metode Ummi (sumbu kanan,
    Juz 30 mundur lalu juz berikutnya maju). Titik target: ◆ Jilid & Halaman Buku, ● Surah & Ayat.
    Periode term: batang bertumpuk (posisi awal term + tambahan tiap bulan).
--}}
@php
    $isTermChart = $periodType !== 'monthly';
    $className = strtoupper($selectedClass?->name ?? '');
    $ummiTitle = 'GRAFIK CAPAIAN UMMI '.$periodLabel.' KELAS '.$className;
    $ummiBookTitle = 'KETUNTASAN BUKU UMMI '.$periodLabel.' KELAS '.$className;
    $ummiHafalanTitle = 'KETUNTASAN HAFALAN SURAH '.$periodLabel.' KELAS '.$className;
    $ummiRows = collect($ummiChart['rows'])->map(function ($row) {
        $parts = explode(' ', $row['student']->name);

        return [
            'labels' => [
                count($parts) > 2 ? $parts[0].' '.$parts[1].'..' : $row['student']->name,
                'Buku: '.$row['book_label'],
                'Hafal: '.($row['last_surah'] ?? $row['hafalan_label']),
            ],
            'name' => $row['student']->name,
            'book' => (int) $row['book'],
            'hafalan' => (int) $row['hafalan'],
            'book_label' => $row['book_label'],
            'hafalan_label' => $row['hafalan_label'],
            'target_book' => $row['target_book'],
            'target_hafalan' => $row['target_hafalan'],
            'target_book_label' => $row['target_book_label'],
            'target_hafalan_label' => $row['target_hafalan_label'],
            'surahs' => $row['surahs_period'],
            'book_base' => $row['book_base'] ?? 0,
            'hafalan_base' => $row['hafalan_base'] ?? 0,
            'book_months' => array_values($row['book_months'] ?? []),
            'hafalan_months' => array_values($row['hafalan_months'] ?? []),
        ];
    })->values();
    $ummiDonuts = [
        ['id' => 'ummiBookDonut', 'title' => $ummiBookTitle, 'done' => $ummiChart['book_done'], 'total' => $ummiChart['book_total'], 'caption' => 'Posisi Buku Ummi ≥ target Jilid & Halaman Buku.'],
        ['id' => 'ummiHafalanDonut', 'title' => $ummiHafalanTitle, 'done' => $ummiChart['hafalan_done'], 'total' => $ummiChart['hafalan_total'], 'caption' => 'Hafalan surah ≥ target Surah & Ayat.'],
    ];
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
        <div class="flex justify-between items-start mb-4 flex-wrap gap-2">
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $ummiTitle }}</h3>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">
                    Batang biru = Buku Ummi (sumbu kiri) · batang hijau = hafalan surah (sumbu kanan) ·
                    ◆ target Jilid &amp; Halaman Buku · ● target Surah &amp; Ayat{{ $isTermChart ? ' · abu-abu = posisi awal term, warna = tambahan tiap bulan' : '' }}.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="downloadChart('ummiChart', @js($ummiTitle))" class="inline-flex items-center gap-1 px-3 py-1.5 bg-teal-50 hover:bg-teal-100 dark:bg-zinc-800 text-teal-700 dark:text-teal-400 text-xs font-bold rounded-lg border border-teal-200 dark:border-zinc-700 transition cursor-pointer">
                    <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" /><span>Unduh PNG</span>
                </button>
                <button type="button" onclick="printChart('ummiChart', @js($ummiTitle))" class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-zinc-800 text-indigo-700 dark:text-indigo-400 text-xs font-bold rounded-lg border border-indigo-200 dark:border-zinc-700 transition cursor-pointer">
                    <x-heroicon-o-printer class="w-3.5 h-3.5" /><span>Cetak F4</span>
                </button>
            </div>
        </div>
        <div class="relative w-full overflow-x-auto touch-scroll" style="height: 470px;">
            <div style="min-width: {{ max(560, $ummiRows->count() * 78) }}px; height: 450px;">
                <canvas id="ummiChart"></canvas>
            </div>
        </div>
    </div>

    <div class="lg:col-span-1 grid grid-cols-1 gap-6">
        @foreach ($ummiDonuts as $donut)
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                <div class="flex justify-between items-start gap-2 flex-wrap">
                    <div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $donut['title'] }}</h3>
                        <p class="text-[11px] text-gray-500 dark:text-zinc-400">{{ $donut['caption'] }}</p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" onclick="downloadChart(@js($donut['id']), @js($donut['title']))" class="px-2 py-1 bg-teal-50 dark:bg-zinc-800 text-teal-700 dark:text-teal-400 text-[11px] font-bold rounded-lg border border-teal-200 dark:border-zinc-700 cursor-pointer">PNG</button>
                        <button type="button" onclick="printChart(@js($donut['id']), @js($donut['title']))" class="px-2 py-1 bg-indigo-50 dark:bg-zinc-800 text-indigo-700 dark:text-indigo-400 text-[11px] font-bold rounded-lg border border-indigo-200 dark:border-zinc-700 cursor-pointer">F4</button>
                    </div>
                </div>
                @if ($donut['total'] > 0)
                    <div class="relative mt-3" style="height: 170px;">
                        <canvas id="{{ $donut['id'] }}"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none" style="padding-bottom: 28px;">
                            <span class="text-2xl font-black text-gray-900 dark:text-white">{{ round($donut['done'] / $donut['total'] * 100, 1) }}%</span>
                            <span class="text-[10px] font-bold text-teal-600">{{ $donut['done'] }} dari {{ $donut['total'] }} murid</span>
                        </div>
                    </div>
                @else
                    <p class="mt-6 mb-4 text-center text-xs text-amber-600">Belum ada target {{ $loop->first ? 'Jilid & Halaman Buku' : 'Surah & Ayat' }} di periode ini (Target Bulanan → Ummi).</p>
                @endif
            </div>
        @endforeach
    </div>
</div>

<script>
    (function () {
        const rows = @json($ummiRows);
        const isTerm = @json($isTermChart);
        const monthNames = @json(array_values($ummiChart['months']));
        const hafalanTicks = @json($ummiChart['hafalan_ticks']);
        const hafalanMax = @json($ummiChart['hafalan_max']);
        const donuts = @json($ummiDonuts);
        const pagesPerJilid = {{ \App\Services\UmmiProgressService::PAGES_PER_JILID }};

        const pageLabel = (v) => v <= 0 ? '' : 'J' + (Math.floor((v - 1) / pagesPerJilid) + 1) + ' h.' + (((v - 1) % pagesPerJilid) + 1);

        function build() {
            if (typeof Chart === 'undefined') {
                return setTimeout(build, 100);
            }
            const isDark = document.documentElement.classList.contains('dark');
            const labelColor = isDark ? '#a1a1aa' : '#64748b';
            const gridColor = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.06)';
            const bookColors = ['#0ea5e9', '#0284c7', '#38bdf8'];
            const hafalanColors = ['#10b981', '#059669', '#34d399'];
            const baseColor = isDark ? '#52525b' : '#cbd5e1';

            const bar = (label, data, color, stack, axis, extra = {}) => ({
                label, data, backgroundColor: color, stack, yAxisID: axis, type: 'bar',
                barPercentage: 0.9, categoryPercentage: 0.7, borderRadius: 3, ...extra,
            });

            let datasets;
            if (isTerm) {
                datasets = [
                    bar('BUKU: AWAL TERM', rows.map(r => r.book_base), baseColor, 'buku', 'y'),
                    ...monthNames.map((m, i) => bar('BUKU ' + m.toUpperCase(), rows.map(r => r.book_months[i] || 0), bookColors[i % 3], 'buku', 'y')),
                    bar('HAFALAN: AWAL TERM', rows.map(r => r.hafalan_base), baseColor, 'hafalan', 'y1'),
                    ...monthNames.map((m, i) => bar('HAFALAN ' + m.toUpperCase(), rows.map(r => r.hafalan_months[i] || 0), hafalanColors[i % 3], 'hafalan', 'y1')),
                ];
            } else {
                datasets = [
                    bar('BUKU UMMI', rows.map(r => r.book), '#0ea5e9', 'buku', 'y'),
                    bar('HAFALAN SURAH', rows.map(r => r.hafalan), '#10b981', 'hafalan', 'y1'),
                ];
            }
            // Isian legenda untuk dua jenis titik target (digambar plugin di bawah).
            datasets.push(
                { label: 'TARGET JILID & HALAMAN', data: [], type: 'line', yAxisID: 'y', pointStyle: 'rectRot', pointRadius: 6, backgroundColor: '#f97316', borderColor: '#ffffff', showLine: false },
                { label: 'TARGET SURAH & AYAT', data: [], type: 'line', yAxisID: 'y1', pointStyle: 'circle', pointRadius: 6, backgroundColor: '#8b5cf6', borderColor: '#ffffff', showLine: false },
            );

            // Titik target tepat di atas batangnya masing-masing (bukan di tengah kategori).
            const targetMarkers = {
                id: 'ummiTargetMarkers',
                afterDatasetsDraw(chart) {
                    const ctx = chart.ctx;
                    const lastOfStack = (stack) => {
                        let index = -1;
                        chart.data.datasets.forEach((d, i) => { if (d.stack === stack && chart.isDatasetVisible(i)) index = i; });
                        return index;
                    };
                    const draw = (stack, key, axis, color, diamond) => {
                        const di = lastOfStack(stack);
                        if (di < 0) return;
                        chart.getDatasetMeta(di).data.forEach((el, i) => {
                            const value = rows[i][key];
                            if (!value) return;
                            const x = el.x;
                            const y = chart.scales[axis].getPixelForValue(value);
                            ctx.save();
                            ctx.fillStyle = color;
                            ctx.strokeStyle = '#ffffff';
                            ctx.lineWidth = 2;
                            ctx.beginPath();
                            if (diamond) {
                                ctx.moveTo(x, y - 7); ctx.lineTo(x + 7, y); ctx.lineTo(x, y + 7); ctx.lineTo(x - 7, y);
                                ctx.closePath();
                            } else {
                                ctx.arc(x, y, 6, 0, Math.PI * 2);
                            }
                            ctx.fill();
                            ctx.stroke();
                            ctx.restore();
                        });
                    };
                    draw('buku', 'target_book', 'y', '#f97316', true);
                    draw('hafalan', 'target_hafalan', 'y1', '#8b5cf6', false);
                },
            };

            new Chart(document.getElementById('ummiChart'), {
                type: 'bar',
                data: { labels: rows.map(r => r.labels), datasets },
                plugins: [targetMarkers],
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        // Plugin datalabels (terdaftar global) dimatikan penuh untuk grafik ini.
                        datalabels: false,
                        legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8, padding: 14, color: labelColor, font: { weight: 'bold', size: 10 } } },
                        tooltip: {
                            filter: (item) => item.dataset.type === 'bar',
                            callbacks: {
                                title: (items) => rows[items[0].dataIndex].name,
                                label: () => null,
                                afterBody: (items) => {
                                    const r = rows[items[0].dataIndex];
                                    const lines = [
                                        'Buku Ummi: ' + r.book_label + (r.target_book ? '  (target ' + r.target_book_label + ')' : ''),
                                        'Hafalan: ' + r.hafalan_label + (r.target_hafalan ? '  (target ' + r.target_hafalan_label + ')' : ''),
                                    ];
                                    if (r.surahs.length) lines.push('Surah periode ini: ' + r.surahs.join(', '));
                                    return lines;
                                },
                            },
                        },
                    },
                    scales: {
                        x: { stacked: true, grid: { display: false }, ticks: { color: labelColor, autoSkip: false, maxRotation: 0, font: { size: 9, weight: '600' } } },
                        y: {
                            stacked: true, min: 0, max: pagesPerJilid * 3, position: 'left',
                            title: { display: true, text: 'Buku Ummi (Jilid & Halaman)', color: '#0284c7', font: { weight: 'bold', size: 10 } },
                            ticks: { stepSize: 10, color: '#0284c7', font: { size: 9 }, callback: (v) => pageLabel(v) },
                            grid: { color: (c) => c.tick && c.tick.value % pagesPerJilid === 0 ? (isDark ? 'rgba(255,255,255,0.25)' : 'rgba(2,132,199,0.35)') : gridColor },
                        },
                        y1: {
                            stacked: true, min: 0, max: hafalanMax, position: 'right',
                            title: { display: true, text: 'Hafalan Surah (Ummi)', color: '#059669', font: { weight: 'bold', size: 10 } },
                            grid: { drawOnChartArea: false },
                            // Nama surah di awal tiap surah; surah pendek yang terlalu rapat dilewati supaya terbaca.
                            afterBuildTicks: (axis) => {
                                const minGap = hafalanMax / 18;
                                let last = -Infinity;
                                axis.ticks = Object.keys(hafalanTicks).map(Number).sort((a, b) => a - b)
                                    .filter((v) => (v - last >= minGap ? ((last = v), true) : false))
                                    .map((v) => ({ value: v }));
                            },
                            ticks: { autoSkip: false, color: '#059669', font: { size: 9 }, callback: (v) => hafalanTicks[v] ?? '' },
                        },
                    },
                },
            });

            donuts.forEach((d) => {
                const canvas = document.getElementById(d.id);
                if (!canvas) return;
                new Chart(canvas, {
                    type: 'doughnut',
                    data: { labels: ['TUNTAS', 'BELUM TUNTAS'], datasets: [{ data: [d.done, d.total - d.done], backgroundColor: ['#0d9488', '#f43f5e'], borderWidth: 3, borderColor: isDark ? '#18181b' : '#ffffff' }] },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { datalabels: false, legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, color: labelColor, font: { size: 10, weight: '600' } } } } },
                });
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', build);
        } else {
            build();
        }
    })();
</script>
