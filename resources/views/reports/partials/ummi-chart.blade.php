{{--
    Grafik capaian Ummi Kelas 10 (laporan periodik): SATU grafik batang mendatar, tiap murid dua
    batang -- Buku Ummi Dewasa (biru, sumbu atas J1 h.1 - J3 h.40, ◆ target Jilid & Halaman Buku)
    dan hafalan surah metode Ummi (hijau, sumbu bawah Juz 30 mundur lalu juz berikutnya maju,
    ● target Surah & Ayat). Posisi tertulis di ujung batang. Periode term: bertumpuk per bulan.
    Di sampingnya satu kartu berisi dua donut ketuntasan (Buku & Hafalan).
--}}
@php
    $isTermChart = $periodType !== 'monthly';
    $className = strtoupper($selectedClass?->name ?? '');
    $ummiTitle = 'GRAFIK CAPAIAN UMMI '.$periodLabel.' KELAS '.$className;
    $ummiDonutTitle = 'KETUNTASAN UMMI '.$periodLabel.' KELAS '.$className;
    $ummiRows = collect($ummiChart['rows'])->map(fn ($row) => [
        'name' => \Illuminate\Support\Str::limit($row['student']->name, 26, '…'),
        'full_name' => $row['student']->name,
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
    ])->values();
    $chartHeight = max(300, $ummiRows->count() * 52 + 150);
    $ummiDonuts = [
        ['id' => 'ummiBookDonut', 'label' => 'Buku Ummi', 'caption' => 'Posisi buku ≥ target Jilid & Halaman Buku', 'done' => $ummiChart['book_done'], 'total' => $ummiChart['book_total'], 'empty' => 'Jilid & Halaman Buku'],
        ['id' => 'ummiHafalanDonut', 'label' => 'Hafalan Surah', 'caption' => 'Hafalan ≥ target Surah & Ayat', 'done' => $ummiChart['hafalan_done'], 'total' => $ummiChart['hafalan_total'], 'empty' => 'Surah & Ayat'],
    ];
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
        <div class="flex justify-between items-start mb-3 flex-wrap gap-2">
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $ummiTitle }}</h3>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">
                    <span class="font-semibold text-sky-600">Biru</span> = Buku Ummi (sumbu atas, ◆ target Jilid &amp; Halaman Buku) ·
                    <span class="font-semibold text-emerald-600">hijau</span> = Hafalan Surah (sumbu bawah, ● target Surah &amp; Ayat){{ $isTermChart ? ' · abu-abu = posisi awal term, warna = tambahan tiap bulan' : '' }}.
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
        <div class="relative w-full" style="height: {{ $chartHeight }}px;">
            <canvas id="ummiChart"></canvas>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm self-start">
        <div class="flex justify-between items-start gap-2 flex-wrap">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $ummiDonutTitle }}</h3>
            <div class="flex items-center gap-1.5">
                <button type="button" onclick="ummiDonutsExport('download', @js($ummiDonutTitle))" class="inline-flex items-center gap-1 px-2.5 py-1 bg-teal-50 dark:bg-zinc-800 text-teal-700 dark:text-teal-400 text-[11px] font-bold rounded-lg border border-teal-200 dark:border-zinc-700 cursor-pointer">
                    <x-heroicon-o-arrow-down-tray class="w-3 h-3" /><span>PNG</span>
                </button>
                <button type="button" onclick="ummiDonutsExport('print', @js($ummiDonutTitle))" class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-50 dark:bg-zinc-800 text-indigo-700 dark:text-indigo-400 text-[11px] font-bold rounded-lg border border-indigo-200 dark:border-zinc-700 cursor-pointer">
                    <x-heroicon-o-printer class="w-3 h-3" /><span>F4</span>
                </button>
            </div>
        </div>
        <div class="mt-3 grid grid-cols-2 lg:grid-cols-1 gap-4">
            @foreach ($ummiDonuts as $donut)
                <div class="text-center">
                    <p class="text-xs font-extrabold uppercase tracking-wider {{ $loop->first ? 'text-sky-600' : 'text-emerald-600' }}">{{ $donut['label'] }}</p>
                    <p class="text-[10px] text-gray-500 dark:text-zinc-400">{{ $donut['caption'] }}</p>
                    @if ($donut['total'] > 0)
                        <div class="relative mt-2 mx-auto" style="height: 180px;">
                            <canvas id="{{ $donut['id'] }}"></canvas>
                        </div>
                    @else
                        <p class="mt-6 mb-4 text-xs text-amber-600">Belum ada target {{ $donut['empty'] }} di periode ini (Target Bulanan → Ummi).</p>
                    @endif
                </div>
            @endforeach
        </div>
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
        const bookMax = pagesPerJilid * {{ \App\Services\UmmiProgressService::JILID_COUNT }};

        // Sumbu buku: awal tiap jilid & pertengahan (J1, J1 h.20, J2, ...).
        const bookTickLabel = (v) => v >= bookMax ? 'Selesai' : (v % pagesPerJilid === 0 ? 'J' + (v / pagesPerJilid + 1) : 'J' + (Math.floor(v / pagesPerJilid) + 1) + ' h.' + (v % pagesPerJilid));

        // Kartu ketuntasan: dua donut digabung jadi satu gambar berjudul (unduh PNG / cetak F4).
        window.ummiDonutsExport = function (mode, title) {
            const width = 900;
            const height = 460;
            const out = document.createElement('canvas');
            out.width = width;
            out.height = height;
            const ctx = out.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, width, height);
            ctx.textAlign = 'center';
            ctx.fillStyle = '#111827';
            ctx.font = 'bold 18px Arial, sans-serif';
            ctx.fillText(title, width / 2, 34);
            ctx.fillStyle = '#6B7280';
            ctx.font = '13px Arial, sans-serif';
            ctx.fillText('TAD-SMAIA7', width / 2, 56);
            donuts.forEach((d, i) => {
                const el = document.getElementById(d.id);
                const cx = width / 4 + (i * width) / 2;
                ctx.fillStyle = i === 0 ? '#0284c7' : '#059669';
                ctx.font = 'bold 15px Arial, sans-serif';
                ctx.fillText(d.label.toUpperCase(), cx, 96);
                ctx.fillStyle = '#6B7280';
                ctx.font = '12px Arial, sans-serif';
                ctx.fillText(d.caption, cx, 116);
                if (el) {
                    const drawHeight = height - 150;
                    const drawWidth = drawHeight * el.width / el.height;
                    ctx.drawImage(el, cx - drawWidth / 2, 132, drawWidth, drawHeight);
                } else {
                    ctx.fillStyle = '#d97706';
                    ctx.fillText('Belum ada target ' + d.empty, cx, 260);
                }
            });
            const url = out.toDataURL('image/png');
            if (mode === 'download') {
                const a = document.createElement('a');
                a.href = url;
                a.download = title + '.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                return;
            }
            const win = window.open('', '_blank');
            win.document.write('<html><head><title>' + title + '</title><style>@page{size:330mm 215mm;margin:1.5cm}body{margin:0;text-align:center}img{max-width:100%;max-height:180mm}</style></head><body><img src="' + url + '" onload="window.print()"></body></html>');
            win.document.close();
        };

        function build() {
            if (typeof Chart === 'undefined') {
                return setTimeout(build, 100);
            }
            const isDark = document.documentElement.classList.contains('dark');
            const labelColor = isDark ? '#a1a1aa' : '#475569';
            const gridColor = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.05)';
            const baseColor = isDark ? '#52525b' : '#cbd5e1';

            const kinds = {
                buku: {
                    axis: 'xBook', value: (r) => r.book, target: (r) => r.target_book, label: (r) => r.book_label,
                    marker: '#f97316', diamond: true, colors: ['#0ea5e9', '#0369a1', '#7dd3fc'], color: '#0ea5e9',
                },
                hafalan: {
                    axis: 'xHafalan', value: (r) => r.hafalan, target: (r) => r.target_hafalan, label: (r) => r.hafalan_label,
                    marker: '#8b5cf6', diamond: false, colors: ['#10b981', '#047857', '#6ee7b7'], color: '#10b981',
                },
            };

            const bar = (label, data, color, stack) => ({
                label, data, backgroundColor: color, stack, xAxisID: kinds[stack].axis,
                barPercentage: 0.92, categoryPercentage: 0.78, borderRadius: 3,
            });
            const datasets = [];
            ['buku', 'hafalan'].forEach((stack) => {
                const k = kinds[stack];
                const prefix = stack === 'buku' ? 'BUKU' : 'HAFALAN';
                if (isTerm) {
                    datasets.push(bar(prefix + ': AWAL TERM', rows.map(r => stack === 'buku' ? r.book_base : r.hafalan_base), baseColor, stack));
                    monthNames.forEach((m, i) => datasets.push(bar(prefix + ' ' + m.toUpperCase(), rows.map(r => (stack === 'buku' ? r.book_months : r.hafalan_months)[i] || 0), k.colors[i % 3], stack)));
                } else {
                    datasets.push(bar(stack === 'buku' ? 'BUKU UMMI' : 'HAFALAN SURAH', rows.map(k.value), k.color, stack));
                }
            });
            // Isian legenda titik target (digambar plugin di bawah).
            datasets.push(
                { label: 'TARGET JILID & HALAMAN', data: [], type: 'line', xAxisID: 'xBook', pointStyle: 'rectRot', pointRadius: 6, backgroundColor: '#f97316', borderColor: '#ffffff', showLine: false },
                { label: 'TARGET SURAH & AYAT', data: [], type: 'line', xAxisID: 'xHafalan', pointStyle: 'circle', pointRadius: 6, backgroundColor: '#8b5cf6', borderColor: '#ffffff', showLine: false },
            );

            const annotate = {
                id: 'ummiAnnotate',
                // Garis tipis pemisah antarmurid.
                beforeDatasetsDraw(chart) {
                    const yScale = chart.scales.y;
                    const ctx = chart.ctx;
                    ctx.save();
                    ctx.strokeStyle = isDark ? 'rgba(255,255,255,0.08)' : 'rgba(15,23,42,0.08)';
                    ctx.setLineDash([4, 4]);
                    for (let i = 0; i < rows.length - 1; i++) {
                        const y = (yScale.getPixelForValue(i) + yScale.getPixelForValue(i + 1)) / 2;
                        ctx.beginPath();
                        ctx.moveTo(chart.chartArea.left, y);
                        ctx.lineTo(chart.chartArea.right, y);
                        ctx.stroke();
                    }
                    ctx.restore();
                },
                // Posisi di ujung batang + titik target sejajar batangnya.
                afterDatasetsDraw(chart) {
                    const ctx = chart.ctx;
                    Object.entries(kinds).forEach(([stack, k]) => {
                        let last = -1;
                        chart.data.datasets.forEach((d, i) => { if (d.stack === stack && chart.isDatasetVisible(i)) last = i; });
                        if (last < 0) return;
                        const xScale = chart.scales[k.axis];
                        chart.getDatasetMeta(last).data.forEach((el, i) => {
                            const r = rows[i];
                            const y = el.y;
                            const endX = xScale.getPixelForValue(k.value(r) || 0);
                            const target = k.target(r);
                            const targetX = target ? xScale.getPixelForValue(target) : null;
                            ctx.save();
                            if (target) {
                                ctx.fillStyle = k.marker;
                                ctx.strokeStyle = '#ffffff';
                                ctx.lineWidth = 2;
                                ctx.beginPath();
                                if (k.diamond) {
                                    ctx.moveTo(targetX, y - 6); ctx.lineTo(targetX + 6, y); ctx.lineTo(targetX, y + 6); ctx.lineTo(targetX - 6, y); ctx.closePath();
                                } else {
                                    ctx.arc(targetX, y, 5, 0, Math.PI * 2);
                                }
                                ctx.fill();
                                ctx.stroke();
                            }
                            ctx.font = 'bold 10px Inter, sans-serif';
                            ctx.textBaseline = 'middle';
                            const text = k.label(r);
                            const width = ctx.measureText(text).width;
                            let textX = endX + 8;
                            if (targetX !== null && targetX >= endX - 8 && targetX - 8 < textX + width) {
                                textX = targetX + 10;
                            }
                            if (textX + width < chart.chartArea.right) {
                                ctx.textAlign = 'left';
                                ctx.fillStyle = isDark ? '#e4e4e7' : '#0f172a';
                                ctx.fillText(text, textX, y);
                            } else {
                                ctx.textAlign = 'right';
                                ctx.fillStyle = '#ffffff';
                                ctx.fillText(text, endX - 6, y);
                            }
                            ctx.restore();
                        });
                    });
                },
            };

            const axisTicks = { color: labelColor, font: { size: 10, weight: '600' }, autoSkip: false, maxRotation: 0 };
            new Chart(document.getElementById('ummiChart'), {
                type: 'bar',
                data: { labels: rows.map(r => r.name), datasets },
                plugins: [annotate],
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { right: 12 } },
                    interaction: { mode: 'index', intersect: false, axis: 'y' },
                    plugins: {
                        datalabels: false,
                        legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8, padding: 12, color: labelColor, font: { weight: 'bold', size: 10 } } },
                        tooltip: {
                            filter: (item) => item.dataset.type !== 'line',
                            callbacks: {
                                title: (items) => rows[items[0].dataIndex].full_name,
                                label: () => null,
                                afterBody: (items) => {
                                    const r = rows[items[0].dataIndex];
                                    const lines = [
                                        'Buku Ummi: ' + r.book_label + '  (target ' + (r.target_book ? r.target_book_label : 'belum ada') + ')',
                                        'Hafalan: ' + r.hafalan_label + '  (target ' + (r.target_hafalan ? r.target_hafalan_label : 'belum ada') + ')',
                                    ];
                                    if (r.surahs.length) lines.push('Surah periode ini: ' + r.surahs.join(', '));
                                    return lines;
                                },
                            },
                        },
                    },
                    scales: {
                        xBook: {
                            type: 'linear', position: 'top', stacked: true, min: 0, max: bookMax,
                            title: { display: true, text: 'BUKU UMMI (JILID & HALAMAN)', color: '#0284c7', font: { size: 10, weight: 'bold' } },
                            // Layar sempit (HP): cukup awal tiap jilid supaya label tidak bertumpuk.
                            afterBuildTicks: (axis) => {
                                const step = axis.chart.width < 640 ? pagesPerJilid : pagesPerJilid / 2;
                                const ticks = [];
                                for (let v = 0; v <= bookMax; v += step) ticks.push({ value: v });
                                axis.ticks = ticks;
                            },
                            ticks: { ...axisTicks, color: '#0284c7', callback: (v) => bookTickLabel(v) },
                            grid: { color: (c) => c.tick && c.tick.value % pagesPerJilid === 0 ? (isDark ? 'rgba(255,255,255,0.22)' : 'rgba(2,132,199,0.3)') : gridColor },
                        },
                        xHafalan: {
                            type: 'linear', position: 'bottom', stacked: true, min: 0, max: hafalanMax,
                            title: { display: true, text: 'HAFALAN SURAH (JUZ 30 DARI AN-NAS)', color: '#059669', font: { size: 10, weight: 'bold' } },
                            // Nama surah di awal tiap surah; surah pendek yang terlalu rapat dilewati.
                            afterBuildTicks: (axis) => {
                                const minGap = hafalanMax / (axis.chart.width < 640 ? 4 : 11);
                                let last = -Infinity;
                                axis.ticks = Object.keys(hafalanTicks).map(Number).sort((a, b) => a - b)
                                    .filter((v) => (v - last >= minGap ? ((last = v), true) : false))
                                    .map((v) => ({ value: v }));
                            },
                            ticks: { ...axisTicks, color: '#059669', callback: (v) => hafalanTicks[v] ?? '' },
                            grid: { drawOnChartArea: false },
                        },
                        y: {
                            stacked: true, grid: { display: false },
                            // Layar sempit: dua kata pertama nama saja.
                            ticks: { color: labelColor, autoSkip: false, font: { size: 11, weight: '600' }, callback: function (v) { const n = rows[v].name; return this.chart.width < 640 ? n.split(' ').slice(0, 2).join(' ') : n; } },
                        },
                    },
                },
            });

            // Donut dengan persentase digambar di kanvas (ikut terbawa saat diunduh/dicetak).
            const centerText = {
                id: 'ummiCenterText',
                afterDraw(chart) {
                    const d = chart.config.options.plugins.ummiCenter;
                    const { left, right, top, bottom } = chart.chartArea;
                    const cx = (left + right) / 2;
                    const cy = (top + bottom) / 2;
                    const ctx = chart.ctx;
                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillStyle = isDark ? '#ffffff' : '#0f172a';
                    ctx.font = '900 22px Inter, sans-serif';
                    ctx.fillText((Math.round(d.done / d.total * 1000) / 10) + '%', cx, cy - 8);
                    ctx.fillStyle = '#0d9488';
                    ctx.font = 'bold 10px Inter, sans-serif';
                    ctx.fillText(d.done + ' dari ' + d.total + ' murid', cx, cy + 14);
                    ctx.restore();
                },
            };
            donuts.forEach((d) => {
                const canvas = document.getElementById(d.id);
                if (!canvas) return;
                new Chart(canvas, {
                    type: 'doughnut',
                    data: { labels: ['TUNTAS', 'BELUM TUNTAS'], datasets: [{ data: [d.done, d.total - d.done], backgroundColor: ['#0d9488', '#f43f5e'], borderWidth: 3, borderColor: isDark ? '#18181b' : '#ffffff' }] },
                    plugins: [centerText],
                    options: {
                        responsive: true, maintainAspectRatio: false, cutout: '72%',
                        plugins: { ummiCenter: d, datalabels: false, legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, color: labelColor, font: { size: 10, weight: '600' } } } },
                    },
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
