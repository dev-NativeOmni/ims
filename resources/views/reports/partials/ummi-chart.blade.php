{{--
    Grafik capaian Ummi Kelas 10 (laporan periodik): dua grafik batang MENDATAR supaya nama murid
    terbaca lengkap berapa pun jumlah murid --
    1. Buku Ummi Dewasa (J1 h.1 - J3 h.40), titik target ◆ Jilid & Halaman Buku;
    2. Hafalan surah metode Ummi (Juz 30 mundur, juz berikutnya maju), titik target ● Surah & Ayat.
    Posisi tertulis di ujung batang. Periode term: batang bertumpuk (awal term + tiap bulan).
--}}
@php
    $isTermChart = $periodType !== 'monthly';
    $className = strtoupper($selectedClass?->name ?? '');
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
    $chartHeight = max(260, $ummiRows->count() * 34 + 110);
    $ummiSections = [
        [
            'kind' => 'book',
            'chart' => 'ummiBookChart',
            'donut' => 'ummiBookDonut',
            'title' => 'GRAFIK BUKU UMMI '.$periodLabel.' KELAS '.$className,
            'donut_title' => 'KETUNTASAN BUKU UMMI '.$periodLabel.' KELAS '.$className,
            'legend' => '◆ target Jilid & Halaman Buku',
            'caption' => 'Posisi Buku Ummi ≥ target Jilid & Halaman Buku.',
            'done' => $ummiChart['book_done'],
            'total' => $ummiChart['book_total'],
            'empty' => 'Jilid & Halaman Buku',
        ],
        [
            'kind' => 'hafalan',
            'chart' => 'ummiHafalanChart',
            'donut' => 'ummiHafalanDonut',
            'title' => 'GRAFIK HAFALAN SURAH UMMI '.$periodLabel.' KELAS '.$className,
            'donut_title' => 'KETUNTASAN HAFALAN SURAH '.$periodLabel.' KELAS '.$className,
            'legend' => '● target Surah & Ayat',
            'caption' => 'Hafalan surah ≥ target Surah & Ayat.',
            'done' => $ummiChart['hafalan_done'],
            'total' => $ummiChart['hafalan_total'],
            'empty' => 'Surah & Ayat',
        ],
    ];
@endphp

@foreach ($ummiSections as $section)
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
            <div class="flex justify-between items-start mb-3 flex-wrap gap-2">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $section['title'] }}</h3>
                    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">
                        {{ $section['kind'] === 'book' ? 'Posisi Jilid & halaman terakhir' : 'Posisi hafalan surah terakhir (Juz 30 dari An-Nas)' }}
                        · {{ $section['legend'] }}{{ $isTermChart ? ' · abu-abu = posisi awal term, warna = tambahan tiap bulan' : '' }}.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="downloadChart(@js($section['chart']), @js($section['title']))" class="inline-flex items-center gap-1 px-3 py-1.5 bg-teal-50 hover:bg-teal-100 dark:bg-zinc-800 text-teal-700 dark:text-teal-400 text-xs font-bold rounded-lg border border-teal-200 dark:border-zinc-700 transition cursor-pointer">
                        <x-heroicon-o-arrow-down-tray class="w-3.5 h-3.5" /><span>Unduh PNG</span>
                    </button>
                    <button type="button" onclick="printChart(@js($section['chart']), @js($section['title']))" class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-zinc-800 text-indigo-700 dark:text-indigo-400 text-xs font-bold rounded-lg border border-indigo-200 dark:border-zinc-700 transition cursor-pointer">
                        <x-heroicon-o-printer class="w-3.5 h-3.5" /><span>Cetak F4</span>
                    </button>
                </div>
            </div>
            <div class="relative w-full" style="height: {{ $chartHeight }}px;">
                <canvas id="{{ $section['chart'] }}"></canvas>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm self-start">
            <div class="flex justify-between items-start gap-2 flex-wrap">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $section['donut_title'] }}</h3>
                    <p class="text-[11px] text-gray-500 dark:text-zinc-400">{{ $section['caption'] }}</p>
                </div>
                <div class="flex items-center gap-1.5">
                    <button type="button" onclick="downloadChart(@js($section['donut']), @js($section['donut_title']))" class="px-2 py-1 bg-teal-50 dark:bg-zinc-800 text-teal-700 dark:text-teal-400 text-[11px] font-bold rounded-lg border border-teal-200 dark:border-zinc-700 cursor-pointer">PNG</button>
                    <button type="button" onclick="printChart(@js($section['donut']), @js($section['donut_title']))" class="px-2 py-1 bg-indigo-50 dark:bg-zinc-800 text-indigo-700 dark:text-indigo-400 text-[11px] font-bold rounded-lg border border-indigo-200 dark:border-zinc-700 cursor-pointer">F4</button>
                </div>
            </div>
            @if ($section['total'] > 0)
                <div class="relative mt-3" style="height: 190px;">
                    <canvas id="{{ $section['donut'] }}"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none" style="padding-bottom: 28px;">
                        <span class="text-2xl font-black text-gray-900 dark:text-white">{{ round($section['done'] / $section['total'] * 100, 1) }}%</span>
                        <span class="text-[10px] font-bold text-teal-600">{{ $section['done'] }} dari {{ $section['total'] }} murid</span>
                    </div>
                </div>
            @else
                <p class="mt-6 mb-4 text-center text-xs text-amber-600">Belum ada target {{ $section['empty'] }} di periode ini (Target Bulanan → Ummi).</p>
            @endif
        </div>
    </div>
@endforeach

<script>
    (function () {
        const rows = @json($ummiRows);
        const isTerm = @json($isTermChart);
        const monthNames = @json(array_values($ummiChart['months']));
        const hafalanTicks = @json($ummiChart['hafalan_ticks']);
        const hafalanMax = @json($ummiChart['hafalan_max']);
        const sections = @json($ummiSections);
        const pagesPerJilid = {{ \App\Services\UmmiProgressService::PAGES_PER_JILID }};
        const bookMax = pagesPerJilid * {{ \App\Services\UmmiProgressService::JILID_COUNT }};

        // Sumbu buku: awal tiap jilid & pertengahan (J1, J1 h.20, J2, ...).
        const bookTickLabel = (v) => v >= bookMax ? 'Selesai' : (v % pagesPerJilid === 0 ? 'J' + (v / pagesPerJilid + 1) : 'J' + (Math.floor(v / pagesPerJilid) + 1) + ' h.' + (v % pagesPerJilid));

        function build() {
            if (typeof Chart === 'undefined') {
                return setTimeout(build, 100);
            }
            const isDark = document.documentElement.classList.contains('dark');
            const labelColor = isDark ? '#a1a1aa' : '#475569';
            const gridColor = isDark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.05)';
            const baseColor = isDark ? '#52525b' : '#cbd5e1';

            const config = {
                book: {
                    value: (r) => r.book, base: (r) => r.book_base, months: (r) => r.book_months, target: (r) => r.target_book,
                    label: (r) => r.book_label, targetLabel: (r) => r.target_book_label,
                    colors: ['#0ea5e9', '#0369a1', '#7dd3fc'], color: '#0ea5e9', marker: '#f97316', diamond: true, name: 'BUKU UMMI',
                    scale: {
                        min: 0, max: bookMax,
                        // Layar sempit (HP): cukup awal tiap jilid supaya label tidak bertumpuk.
                        afterBuildTicks: (axis) => {
                            const step = axis.chart.width < 640 ? pagesPerJilid : pagesPerJilid / 2;
                            const ticks = [];
                            for (let v = 0; v <= bookMax; v += step) ticks.push({ value: v });
                            axis.ticks = ticks;
                        },
                        ticks: { callback: (v) => bookTickLabel(v) },
                        grid: { color: (c) => c.tick && c.tick.value % pagesPerJilid === 0 ? (isDark ? 'rgba(255,255,255,0.25)' : 'rgba(2,132,199,0.35)') : gridColor },
                    },
                },
                hafalan: {
                    value: (r) => r.hafalan, base: (r) => r.hafalan_base, months: (r) => r.hafalan_months, target: (r) => r.target_hafalan,
                    label: (r) => r.hafalan_label, targetLabel: (r) => r.target_hafalan_label,
                    colors: ['#10b981', '#047857', '#6ee7b7'], color: '#10b981', marker: '#8b5cf6', diamond: false, name: 'HAFALAN SURAH',
                    scale: {
                        min: 0, max: hafalanMax,
                        // Nama surah di awal tiap surah; surah pendek yang terlalu rapat dilewati.
                        afterBuildTicks: (axis) => {
                            const minGap = hafalanMax / (axis.chart.width < 640 ? 4 : 11);
                            let last = -Infinity;
                            axis.ticks = Object.keys(hafalanTicks).map(Number).sort((a, b) => a - b)
                                .filter((v) => (v - last >= minGap ? ((last = v), true) : false))
                                .map((v) => ({ value: v }));
                        },
                        ticks: { callback: (v) => hafalanTicks[v] ?? '' },
                        grid: { color: gridColor },
                    },
                },
            };

            sections.forEach((section) => {
                const c = config[section.kind];
                const canvas = document.getElementById(section.chart);
                if (!canvas) return;

                const bar = (label, data, color, extra = {}) => ({ label, data, backgroundColor: color, stack: 'posisi', barPercentage: 0.8, categoryPercentage: 0.85, borderRadius: 3, ...extra });
                const datasets = isTerm
                    ? [bar('AWAL TERM', rows.map(c.base), baseColor), ...monthNames.map((m, i) => bar(m.toUpperCase(), rows.map(r => c.months(r)[i] || 0), c.colors[i % 3]))]
                    : [bar(c.name, rows.map(c.value), c.color)];
                // Isian legenda titik target (digambar plugin di bawah).
                datasets.push({ label: c.diamond ? 'TARGET JILID & HALAMAN' : 'TARGET SURAH & AYAT', data: [], type: 'line', pointStyle: c.diamond ? 'rectRot' : 'circle', pointRadius: 6, backgroundColor: c.marker, borderColor: '#ffffff', showLine: false });

                // Posisi di ujung batang + titik target sejajar batangnya.
                const annotate = {
                    id: 'ummiAnnotate',
                    afterDatasetsDraw(chart) {
                        const ctx = chart.ctx;
                        const xScale = chart.scales.x;
                        let lastBar = -1;
                        chart.data.datasets.forEach((d, i) => { if (d.type !== 'line' && chart.isDatasetVisible(i)) lastBar = i; });
                        if (lastBar < 0) return;
                        chart.getDatasetMeta(lastBar).data.forEach((el, i) => {
                            const r = rows[i];
                            const y = el.y;
                            ctx.save();
                            const endX = xScale.getPixelForValue(c.value(r) || 0);
                            const target = c.target(r);
                            const targetX = target ? xScale.getPixelForValue(target) : null;
                            if (target) {
                                const x = xScale.getPixelForValue(target);
                                ctx.fillStyle = c.marker;
                                ctx.strokeStyle = '#ffffff';
                                ctx.lineWidth = 2;
                                ctx.beginPath();
                                if (c.diamond) {
                                    ctx.moveTo(x, y - 7); ctx.lineTo(x + 7, y); ctx.lineTo(x, y + 7); ctx.lineTo(x - 7, y); ctx.closePath();
                                } else {
                                    ctx.arc(x, y, 6, 0, Math.PI * 2);
                                }
                                ctx.fill();
                                ctx.stroke();
                            }

                            // Tulisan posisi di kanan ujung batang (dan di kanan titik target bila target
                            // jatuh dekat ujung batang); bila tidak muat, ditulis putih di dalam batang.
                            ctx.font = 'bold 10px Inter, sans-serif';
                            ctx.textBaseline = 'middle';
                            const text = c.label(r);
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
                    },
                };

                new Chart(canvas, {
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
                            legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8, padding: 14, color: labelColor, font: { weight: 'bold', size: 10 } } },
                            tooltip: {
                                filter: (item) => item.dataset.type !== 'line',
                                callbacks: {
                                    title: (items) => rows[items[0].dataIndex].full_name,
                                    label: () => null,
                                    afterBody: (items) => {
                                        const r = rows[items[0].dataIndex];
                                        const lines = ['Posisi: ' + c.label(r), 'Target: ' + (c.target(r) ? c.targetLabel(r) : 'belum ada')];
                                        if (section.kind === 'hafalan' && r.surahs.length) lines.push('Surah periode ini: ' + r.surahs.join(', '));
                                        return lines;
                                    },
                                },
                            },
                        },
                        scales: {
                            x: { stacked: true, position: 'top', ...c.scale, ticks: { ...c.scale.ticks, color: labelColor, font: { size: 10, weight: '600' }, autoSkip: false, maxRotation: 0 } },
                            y: {
                                stacked: true, grid: { display: false },
                                // Layar sempit: dua kata pertama nama saja.
                                ticks: { color: labelColor, autoSkip: false, font: { size: 11, weight: '600' }, callback: function (v) { const n = rows[v].name; return this.chart.width < 640 ? n.split(' ').slice(0, 2).join(' ') : n; } },
                            },
                        },
                    },
                });

                const donut = document.getElementById(section.donut);
                if (donut) {
                    new Chart(donut, {
                        type: 'doughnut',
                        data: { labels: ['TUNTAS', 'BELUM TUNTAS'], datasets: [{ data: [section.done, section.total - section.done], backgroundColor: ['#0d9488', '#f43f5e'], borderWidth: 3, borderColor: isDark ? '#18181b' : '#ffffff' }] },
                        options: { responsive: true, maintainAspectRatio: false, cutout: '72%', plugins: { datalabels: false, legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, color: labelColor, font: { size: 10, weight: '600' } } } } },
                    });
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', build);
        } else {
            build();
        }
    })();
</script>
