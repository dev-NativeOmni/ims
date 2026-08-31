<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                    Laporan & Grafik Perkembangan Berkala
                </h2>
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    Pantau tren grafik setoran hafalan & murajaah serta capaian target kelas secara berkala.
                </p>
            </div>
            
            @if ($selectedClass)
                <a href="{{ route('reports.periodic.print', request()->query()) }}" target="_blank" class="no-print inline-flex items-center gap-2 rounded-xl bg-teal-600 hover:bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition duration-150 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.82l-.24.24c-1.316 1.316-3.484 1.316-4.8 0L1 13.38V9.25a2.25 2.25 0 012.25-2.25h15.5A2.25 2.25 0 0121 9.25v4.13l-.68.68c-1.316 1.316-3.484 1.316-4.8 0l-.24-.24M6.72 13.82A4.488 4.488 0 005.25 17v3.25h13.5V17c0-1.28-.52-2.438-1.37-3.18M6.72 13.82h10.56M9 11.25h.008v.008H9v-.008z" />
                    </svg>
                    <span>Cetak Laporan</span>
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Filter Form -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-5 shadow-sm transition-colors duration-200">
                <form method="GET" action="{{ route('reports.periodic') }}" x-data="{ periodType: '{{ $periodType }}' }" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    
                    <!-- ClassRoom Selector -->
                    <div>
                        <label for="class_room_id" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Kelas</label>
                        <select name="class_room_id" id="class_room_id" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm">
                            @foreach ($classRooms as $class)
                                <option value="{{ $class->id }}" {{ $selectedClassId == $class->id ? 'selected' : '' }}>{{ $class->name }} ({{ $class->program?->name }})</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Period Type Selector -->
                    <div>
                        <label for="period_type" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Rentang Waktu</label>
                        <select name="period_type" id="period_type" x-model="periodType" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm">
                            <option value="monthly">Bulanan</option>
                            <option value="quarterly">Tiga Bulanan (Term)</option>
                        </select>
                    </div>

                    <!-- Month Selector / Quarter Selector (Toggled by alpine) -->
                    <div>
                        <!-- Monthly Option -->
                        <div x-show="periodType === 'monthly'">
                            <label for="month" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Bulan</label>
                            <select name="month" id="month" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm">
                                @foreach ($monthsList as $key => $name)
                                    <option value="{{ $key }}" {{ $selectedMonth == $key ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Quarterly Option -->
                        <div x-show="periodType === 'quarterly'" style="display: none;">
                            <label for="quarter" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Term (Triwulan)</label>
                            <select name="quarter" id="quarter" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm">
                                <option value="1" {{ $selectedQuarter == 1 ? 'selected' : '' }}>Term 1 (Jul - Sep)</option>
                                <option value="2" {{ $selectedQuarter == 2 ? 'selected' : '' }}>Term 2 (Okt - Des)</option>
                                <option value="3" {{ $selectedQuarter == 3 ? 'selected' : '' }}>Term 3 (Jan - Mar)</option>
                                <option value="4" {{ $selectedQuarter == 4 ? 'selected' : '' }}>Term 4 (Apr - Jun)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Year and Submit Button -->
                    <div class="flex gap-2">
                        <div class="w-24">
                            <label for="year" class="block text-xs font-semibold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Tahun</label>
                            <input type="number" name="year" id="year" value="{{ $selectedYear }}" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-teal-500 focus:ring-teal-500 text-sm">
                        </div>
                        <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 border border-transparent rounded-xl text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 shadow-sm transition-colors duration-150 min-h-[42px]">
                            Filter
                        </button>
                    </div>

                </form>
            </div>

            @if ($selectedClass)
                <!-- Metrics Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                        <div class="text-xs font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider">Total Murid</div>
                        <div class="mt-2 text-3xl font-extrabold text-gray-900 dark:text-white">{{ $summary['total_students'] }}</div>
                        <div class="mt-1 text-xs text-gray-500">Murid terdaftar aktif di kelas</div>
                    </div>
                    
                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                        <div class="text-xs font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider">Total Setoran</div>
                        <div class="mt-2 text-3xl font-extrabold text-teal-600 dark:text-teal-400">{{ $summary['total_hafalan'] }}</div>
                        <div class="mt-1 text-xs text-gray-500">Setoran hafalan baru lulus</div>
                    </div>

                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                        <div class="text-xs font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider">Total Murajaah</div>
                        <div class="mt-2 text-3xl font-extrabold text-amber-600 dark:text-amber-450">{{ $summary['total_murajaah'] }}</div>
                        <div class="mt-1 text-xs text-gray-500">Pengulangan hafalan lulus</div>
                    </div>

                    <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                        <div class="text-xs font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider">Rerata Nilai Hafalan</div>
                        <div class="mt-2 text-3xl font-extrabold text-gray-900 dark:text-white">{{ $summary['avg_hafalan_score'] }}</div>
                        <div class="mt-1 text-xs text-gray-500">Skala penilaian 100</div>
                    </div>
                </div>

                @php
                    $names = [];
                    $capaians = [];
                    $targets = [];
                    foreach ($studentReports as $rep) {
                        $parts = explode(' ', $rep['student']->name);
                        $shortName = count($parts) > 2 ? $parts[0] . ' ' . $parts[1] . '..' : $rep['student']->name;
                        $names[] = $shortName;
                        $capaians[] = (int) $rep['capaian_baris'];
                        $targets[] = (int) $rep['target_baris'];
                    }
                    $monthName = $monthsList[$selectedMonth] ?? '';
                    $className = $selectedClass?->name ?? '';
                    $titleCapaian = "GRAFIK CAPAIAN BULAN " . strtoupper($monthName) . " KELAS " . strtoupper($className);
                    $titleKetuntasan = "KETUNTASAN BULAN KELAS " . strtoupper($className);
                @endphp

                @if (!empty($isGrade10))
                    <!-- Grade 10 Ummi Capaian Tahfidz Card View -->
                    <div class="space-y-4">
                        <div class="flex justify-between items-center bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 p-4 rounded-2xl shadow-sm flex-wrap gap-3">
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                    Laporan Capaian Tahfidz — Pembelajaran UMMI {{ $selectedClass?->name }}
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Tampilan khusus Kelas 10 menyajikan Jilid, Halaman, Capaian Hafalan UMMI, dan Ziyadah.
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button" onclick="downloadUmmiCard()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow transition cursor-pointer">
                                    📥 Unduh PNG
                                </button>
                                <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow transition cursor-pointer">
                                    🖨️ Cetak Halaman
                                </button>
                            </div>
                        </div>

                        @include('reports.partials.ummi-grade10-card')
                    </div>
                @else
                    <!-- Standard Grade 11 & 12 Charts Grid -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left: Capaian & Target Chart (w-2/3) -->
                        <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                                    <div>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                            {{ $titleCapaian }}
                                        </h3>
                                        <p class="text-xs text-gray-550 dark:text-zinc-400 mt-1">
                                            Membandingkan jumlah capaian baris setoran (batang) dengan target baris (garis).
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" onclick="downloadChart('capaianChart', '{{ $titleCapaian }}')" class="inline-flex items-center gap-1 px-3 py-1.5 bg-teal-50 hover:bg-teal-100 dark:bg-zinc-800 text-teal-700 dark:text-teal-400 text-xs font-bold rounded-lg border border-teal-200 dark:border-zinc-700 transition cursor-pointer">
                                            📥 Unduh PNG
                                        </button>
                                        <button type="button" onclick="printChart('capaianChart', '{{ $titleCapaian }}')" class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-zinc-800 text-indigo-700 dark:text-indigo-400 text-xs font-bold rounded-lg border border-indigo-200 dark:border-zinc-700 transition cursor-pointer">
                                            🖨️ Cetak F4
                                        </button>
                                    </div>
                                </div>
                                <div class="relative w-full overflow-x-auto" style="height: 380px;">
                                    <canvas id="capaianChart" style="min-width: 600px; height: 350px;"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Ketuntasan Pie Chart (w-1/3) -->
                        <div class="lg:col-span-1 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                                    <div>
                                        <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                            {{ $titleKetuntasan }}
                                        </h3>
                                        <p class="text-xs text-gray-550 dark:text-zinc-400 mt-1">
                                            Persentase ketuntasan target bulanan kelas.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" onclick="downloadChart('ketuntasanChart', '{{ $titleKetuntasan }}')" class="inline-flex items-center gap-1 px-3 py-1.5 bg-teal-50 hover:bg-teal-100 dark:bg-zinc-800 text-teal-700 dark:text-teal-400 text-xs font-bold rounded-lg border border-teal-200 dark:border-zinc-700 transition cursor-pointer">
                                            📥 Unduh PNG
                                        </button>
                                        <button type="button" onclick="printChart('ketuntasanChart', '{{ $titleKetuntasan }}')" class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 dark:bg-zinc-800 text-indigo-700 dark:text-indigo-400 text-xs font-bold rounded-lg border border-indigo-200 dark:border-zinc-700 transition cursor-pointer">
                                            🖨️ Cetak F4
                                        </button>
                                    </div>
                                </div>
                                <div class="relative w-full flex justify-center items-center" style="height: 380px;">
                                    <div style="width: 280px; height: 280px;">
                                        <canvas id="ketuntasanChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Detailed table per student grouped by Teacher & Halaqah -->
                <div class="space-y-8">
                    @forelse ($groupedReports as $teacherName => $halaqahs)
                        <div class="space-y-6">
                            <!-- Teacher Header Bar -->
                            <div class="bg-teal-600 dark:bg-teal-700 text-white px-6 py-3.5 rounded-2xl shadow-sm flex justify-between items-center">
                                <h3 class="text-sm font-extrabold tracking-wider uppercase">Pembimbing: {{ $teacherName }}</h3>
                                <span class="text-xs bg-white/20 px-3 py-1 rounded-full font-bold">
                                    {{ collect($halaqahs)->flatten(1)->count() }} Murid
                                </span>
                            </div>

                            @foreach ($halaqahs as $halaqahLabel => $reports)
                                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl shadow-sm overflow-hidden">
                                    <!-- Halaqah Header -->
                                    <div class="px-6 py-4 border-b border-gray-150 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 flex justify-between items-center">
                                        <div>
                                            <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Halaqah: {{ $halaqahLabel }}</h4>
                                            <p class="text-[11px] text-gray-500 dark:text-zinc-400 mt-0.5">Kelompok halaqah di bawah asuhan {{ $teacherName }}</p>
                                        </div>
                                    </div>

                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-xs">
                                            <thead class="bg-zinc-50 dark:bg-zinc-900/30 text-zinc-500 dark:text-zinc-400 font-bold uppercase tracking-wider text-center">
                                                <!-- Row 1: Main Headers -->
                                                <tr class="border-b border-zinc-200 dark:border-zinc-800">
                                                    <th rowspan="2" class="px-4 py-3 text-left w-12 align-middle">No</th>
                                                    <th rowspan="2" class="px-4 py-3 text-left align-middle min-w-[200px]">Nama Murid</th>
                                                    <th rowspan="2" class="px-4 py-3 align-middle">Halaqah</th>
                                                    <th colspan="2" class="px-4 py-2 border-b border-zinc-200 dark:border-zinc-800 text-center">Target</th>
                                                    <th colspan="2" class="px-4 py-2 border-b border-zinc-200 dark:border-zinc-800 text-center">Capaian</th>
                                                    <th rowspan="2" class="px-4 py-3 align-middle">Ketercapaian</th>
                                                    <th colspan="3" class="px-4 py-2 border-b border-zinc-200 dark:border-zinc-800 text-center">Kehadiran</th>
                                                    <th rowspan="2" class="px-4 py-3 align-middle w-24">Pelanggaran</th>
                                                </tr>
                                                <!-- Row 2: Sub-headers -->
                                                <tr class="border-b border-zinc-200 dark:border-zinc-800">
                                                    <th class="px-3 py-1.5 border-r border-zinc-200 dark:border-zinc-800 font-medium">Surat</th>
                                                    <th class="px-3 py-1.5 font-medium">Ayat</th>
                                                    <th class="px-3 py-1.5 border-r border-zinc-200 dark:border-zinc-800 font-medium">Surat</th>
                                                    <th class="px-3 py-1.5 font-medium">Ayat</th>
                                                    <th class="px-2 py-1.5 font-semibold text-rose-600">A</th>
                                                    <th class="px-2 py-1.5 font-semibold text-amber-500">I</th>
                                                    <th class="px-2 py-1.5 font-semibold text-blue-500">S</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800/60 text-center text-gray-700 dark:text-zinc-300">
                                                @foreach ($reports as $index => $row)
                                                    <tr class="hover:bg-zinc-550/[0.01] dark:hover:bg-white/[0.01]">
                                                        <td class="px-4 py-3 text-left font-medium text-gray-500 w-12">{{ $index + 1 }}</td>
                                                        <td class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">
                                                            <div>{{ $row['student']->name }}</div>
                                                            <div class="text-[10px] text-gray-400 font-normal mt-0.5">NIS: {{ $row['student']->student_number ?: '-' }}</div>
                                                        </td>
                                                        <td class="px-4 py-3 text-gray-500 dark:text-zinc-450">{{ $row['halaqah_label'] }}</td>
                                                        <td class="px-3 py-3 border-r border-zinc-150 dark:border-zinc-800 font-medium">{{ $row['target_surah'] }}</td>
                                                        <td class="px-3 py-3 font-semibold">{{ $row['target_ayat'] }}</td>
                                                        <td class="px-3 py-3 border-r border-zinc-150 dark:border-zinc-800 font-medium text-teal-600 dark:text-teal-400">{{ $row['capaian_surah'] }}</td>
                                                        <td class="px-3 py-3 font-bold text-teal-600 dark:text-teal-400">{{ $row['capaian_ayat'] }}</td>
                                                        <td class="px-4 py-3">
                                                            @if ($row['is_tuntas'])
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/30 uppercase">Tuntas</span>
                                                            @else
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-450 border border-rose-200 dark:border-rose-900/30 uppercase">Tidak Tuntas</span>
                                                            @endif
                                                        </td>
                                                         <td class="px-2 py-3 font-semibold {{ $row['alpa'] > 0 ? 'text-rose-650' : 'text-gray-400' }}">{{ $row['alpa'] ?: '-' }}</td>
                                                         <td class="px-2 py-3 font-semibold {{ $row['izin'] > 0 ? 'text-amber-500' : 'text-gray-400' }}">{{ $row['izin'] ?: '-' }}</td>
                                                         <td class="px-2 py-3 font-semibold {{ $row['sakit'] > 0 ? 'text-blue-500' : 'text-gray-400' }}">{{ $row['sakit'] ?: '-' }}</td>
                                                        <!-- Pelanggaran -->
                                                        <td class="px-4 py-3 font-bold {{ $row['violations_count'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400' }}">
                                                            {{ $row['violations_count'] }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Table Footer with Completeness summary matching spreadsheet -->
                                    <div class="px-6 py-3.5 bg-zinc-50 dark:bg-zinc-900/40 border-t border-gray-150 dark:border-zinc-800 text-[11px] font-bold text-gray-650 dark:text-zinc-400 flex justify-end gap-6">
                                        @php
                                            $totalGroup = count($reports);
                                            $tuntasGroup = collect($reports)->where('is_tuntas', true)->count();
                                            $tidakTuntasGroup = $totalGroup - $tuntasGroup;
                                            $tuntasPct = $totalGroup > 0 ? round(($tuntasGroup / $totalGroup) * 100, 1) : 0;
                                            $tidakTuntasPct = $totalGroup > 0 ? round(($tidakTuntasGroup / $totalGroup) * 100, 1) : 0;
                                        @endphp
                                        <span class="flex items-center gap-1">
                                            Tuntas: <span class="text-teal-600 font-extrabold">{{ $tuntasPct }}%</span> ({{ $tuntasGroup }} murid)
                                        </span>
                                        <span class="flex items-center gap-1">
                                            Tidak Tuntas: <span class="text-rose-600 font-extrabold">{{ $tidakTuntasPct }}%</span> ({{ $tidakTuntasGroup }} murid)
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-8 text-center text-sm text-gray-500 dark:text-zinc-500 shadow-sm">
                            Tidak ada data perkembangan murid pada rentang waktu ini.
                        </div>
                    @endforelse
                </div>
            @else
                <div class="bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-100 dark:border-yellow-900/30 rounded-2xl p-6 text-center text-yellow-800 dark:text-yellow-400">
                     Belum ada kelas yang dapat Anda akses atau tidak ada data murid terdaftar.
                </div>
            @endif

        </div>
    </div>

    @if ($selectedClass)
        <!-- ChartJS & DataLabels script & html2canvas -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
        <script>
            function downloadUmmiCard() {
                const cardEl = document.getElementById('ummiGrade10ReportCard');
                if (!cardEl) {
                    alert('Elemen laporan tidak ditemukan.');
                    return;
                }
                
                html2canvas(cardEl, {
                    scale: 2,
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    logging: false
                }).then(canvas => {
                    const a = document.createElement('a');
                    a.download = 'Laporan_Capaian_Ummi_{{ $selectedClass?->name }}_{{ $monthName }}.png';
                    a.href = canvas.toDataURL('image/png');
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                }).catch(err => {
                    console.error('Gagal mengunduh gambar:', err);
                    alert('Gagal mengunduh gambar. Silakan coba lagi.');
                });
            }

            // Global functions for download and print
            function downloadChart(chartId, title) {
                const canvas = document.getElementById(chartId);
                const tempCanvas = document.createElement('canvas');

                const bannerHeight = 70;
                tempCanvas.width = canvas.width;
                tempCanvas.height = canvas.height + bannerHeight;

                const tempCtx = tempCanvas.getContext('2d');
                
                // Fill white background
                tempCtx.fillStyle = '#ffffff';
                tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
                
                // Draw Title Header Banner
                tempCtx.textAlign = 'center';
                tempCtx.fillStyle = '#111827';
                tempCtx.font = 'bold 18px Arial, sans-serif';
                tempCtx.fillText(title, tempCanvas.width / 2, 32);

                tempCtx.fillStyle = '#6B7280';
                tempCtx.font = '13px Arial, sans-serif';
                tempCtx.fillText("IMS-SMAIA7", tempCanvas.width / 2, 54);

                // Divider line
                tempCtx.strokeStyle = '#E5E7EB';
                tempCtx.lineWidth = 1;
                tempCtx.beginPath();
                tempCtx.moveTo(20, 62);
                tempCtx.lineTo(tempCanvas.width - 20, 62);
                tempCtx.stroke();

                // Draw original chart below banner
                tempCtx.drawImage(canvas, 0, bannerHeight);
                
                const url = tempCanvas.toDataURL('image/png');
                const a = document.createElement('a');
                a.href = url;
                a.download = title + '.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            }

            function printChart(chartId, title) {
                const canvas = document.getElementById(chartId);
                
                // Draw on a temp white canvas with 2x resolution
                const tempCanvas = document.createElement('canvas');
                tempCanvas.width = canvas.width * 2;
                tempCanvas.height = canvas.height * 2;
                const tempCtx = tempCanvas.getContext('2d');
                tempCtx.scale(2, 2);
                
                // Fill white
                tempCtx.fillStyle = '#ffffff';
                tempCtx.fillRect(0, 0, canvas.width, canvas.height);
                tempCtx.drawImage(canvas, 0, 0);
                
                const url = tempCanvas.toDataURL('image/png');
                
                const win = window.open('', '_blank');
                win.document.write(`
                    <html>
                        <head>
                            <title>${title}</title>
                            <style>
                                @page {
                                    size: 330mm 215mm; /* F4 Landscape */
                                    margin: 1.5cm;
                                }
                                body {
                                    font-family: Arial, sans-serif;
                                    text-align: center;
                                    margin: 0;
                                    padding: 0;
                                    background: white;
                                }
                                .header-title {
                                    font-size: 24px;
                                    font-weight: bold;
                                    text-transform: uppercase;
                                    margin-top: 15px;
                                    margin-bottom: 25px;
                                    color: #111;
                                }
                                .chart-frame {
                                    width: 100%;
                                    height: 165mm;
                                    display: flex;
                                    justify-content: center;
                                    align-items: center;
                                }
                                img {
                                    max-width: 100%;
                                    max-height: 100%;
                                    object-fit: contain;
                                }
                            </style>
                        </head>
                        <body onload="setTimeout(() => { window.print(); window.close(); }, 500)">
                            <div class="header-title">${title}</div>
                            <div class="chart-frame">
                                <img src="${url}">
                            </div>
                        </body>
                    </html>
                `);
                win.document.close();
            }

            document.addEventListener('DOMContentLoaded', function() {
                // Register datalabels plugin
                Chart.register(ChartDataLabels);

                const isDark = document.documentElement.classList.contains('dark');
                const gridColor = isDark ? 'rgba(63, 63, 70, 0.3)' : 'rgba(228, 228, 231, 0.8)';
                const labelColor = isDark ? '#a1a1aa' : '#71717a';

                // --- CAPAIAN vs TARGET CHART ---
                const capaianCtx = document.getElementById('capaianChart').getContext('2d');
                new Chart(capaianCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($names),
                        datasets: [
                            {
                                label: 'CAPAIAN',
                                type: 'bar',
                                data: @json($capaians),
                                backgroundColor: '#1d70b8', // Blue bar matching screenshot
                                borderColor: '#1d70b8',
                                borderWidth: 1,
                                order: 2,
                                datalabels: {
                                    anchor: 'start',
                                    align: 'end',
                                    offset: 4,
                                    color: '#ffffff', // White numbers inside the bar at the bottom
                                    font: {
                                        weight: 'bold',
                                        size: 10
                                    }
                                }
                            },
                            {
                                label: 'TARGET',
                                type: 'line',
                                data: @json($targets),
                                borderColor: '#c1392b', // Red line matching screenshot
                                backgroundColor: '#c1392b',
                                borderWidth: 2.5,
                                pointBackgroundColor: '#c1392b',
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                fill: false,
                                order: 1,
                                datalabels: {
                                    anchor: 'end',
                                    align: 'top',
                                    color: '#c1392b', // Red numbers above the points
                                    font: {
                                        weight: 'bold',
                                        size: 10
                                    }
                                }
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    color: labelColor,
                                    font: {
                                        weight: 'bold'
                                    }
                                }
                            },
                            tooltip: {
                                enabled: true
                            }
                        },
                        scales: {
                            y: {
                                grid: {
                                    color: gridColor
                                },
                                ticks: {
                                    color: labelColor,
                                    stepSize: 10
                                },
                                beginAtZero: true
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                ticks: {
                                    color: labelColor,
                                    font: {
                                        size: 9
                                    },
                                    minRotation: 90,
                                    maxRotation: 90
                                }
                            }
                        }
                    }
                });

                // --- KETUNTASAN PIE CHART ---
                const ketuntasanCtx = document.getElementById('ketuntasanChart').getContext('2d');
                new Chart(ketuntasanCtx, {
                    type: 'pie',
                    data: {
                        labels: ['TUNTAS', 'TIDAK TUNTAS'],
                        datasets: [{
                            data: [{{ $tuntasCount }}, {{ $tidakTuntasCount }}],
                            backgroundColor: [
                                '#1d70b8', // Blue for Tuntas
                                '#c1392b'  // Red/brown for Tidak Tuntas
                            ],
                            borderWidth: 1,
                            borderColor: isDark ? '#18181b' : '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false // Hidden because labels are drawn directly on slices
                            },
                            datalabels: {
                                formatter: (value, ctx) => {
                                    let sum = 0;
                                    let dataArr = ctx.chart.data.datasets[0].data;
                                    dataArr.map(data => {
                                        sum += data;
                                    });
                                    let percentage = (value * 100 / sum).toFixed(1) + "%";
                                    return ctx.chart.data.labels[ctx.dataIndex] + "\n" + percentage;
                                },
                                color: '#ffffff',
                                font: {
                                    weight: 'bold',
                                    size: 11
                                },
                                textAlign: 'center'
                            }
                        }
                    }
                });
            });
        </script>
    @endif
</x-app-layout>
