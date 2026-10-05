{{--
    Satu lembar rapor cetak (dipakai cetak per santri & per kelas).
    $sheet: isi rapor siap tampil dari StudentReportController::buildSheet() -- dihitung
    langsung, atau simpanan yang dibekukan saat periode dikunci. Hanya baca dari $sheet
    supaya rapor terkunci tetap utuh walau data/pengaturan berubah.
    $signatureUris: path berkas tanda tangan => data URI.
    $pageBreak: true untuk lembar kedua dst. pada cetak per kelas.
    $live: true di pratinjau Pengaturan Rapor -- teks kop/periode/pejabat & tanda tangan diikat ke
    state Alpine form (x-text), modul diikat ke centang Modul (x-show), posisi logo & font ke
    pilihan Tampilan Cetak (logoPosition, reportFont). Cetak: false.
    Posisi logo & font (StudentReportController::printLayout()) selalu dari pengaturan terkini.
--}}
@php
    $live = $live ?? false;
    $layout = \App\Http\Controllers\StudentReportController::printLayout();
    $fonts = \App\Http\Controllers\StudentReportController::REPORT_FONTS;
    // Cetak: hanya logo di posisi terpilih. Pratinjau: ketiga posisi dirender, ditampilkan sesuai pilihan.
    $logoAt = fn (string $position) => $live ? 'x-show="logoPosition === \''.$position.'\'"'.($layout['logo'] === $position ? '' : ' style="display: none"') : '';
    $hasLogo = fn (string $position) => $live || $layout['logo'] === $position;
    $signature = fn ($key) => $signatureUris[$sheet['signatories'][$key]['signature'] ?? ''] ?? null;
    $adabCount = count($sheet['adab']['categories']);
    // Atribut pengikat Alpine untuk pratinjau (ekspresi ditulis tetap di sini, bukan dari input).
    $bind = fn (string $expression) => $live ? 'x-text="'.$expression.'"' : '';
    // Modul yang dicetak (Pengaturan Rapor > Modul); simpanan terkunci lama tanpa 'modules' = semua tampil.
    $showModule = fn (string $module) => $live || ($sheet['modules'][$module] ?? true);
    $moduleToggle = fn (string $flag) => $live ? 'x-show="'.$flag.'"' : '';
    $liveOfficials = [
        'coord_tahfizh' => ['coordTahfizhName', 'coordTahfizhNik', null],
        'coord_keagamaan' => ['coordKeagamaanName', 'coordKeagamaanNik', null],
        'coord_tanse' => ['coordTanseName', 'coordTanseNik', null],
        'headmaster' => ['headmasterName', 'headmasterNik', 'headmasterTitle'],
    ];
@endphp
@once
    <style>
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
            max-width: none;
            pointer-events: none;
        }
        /* Kertas selalu putih: tangkal aturan mode gelap global app.css (.dark .bg-white dst., !important)
           saat lembar tampil di halaman aplikasi (pratinjau Pengaturan Rapor). */
        .dark .print-container.bg-white { background-color: #fff !important; color: #000 !important; border: none !important; backdrop-filter: none !important; -webkit-backdrop-filter: none !important; }
        .dark .print-container .bg-gray-100 { background-color: #f3f4f6 !important; }
        .dark .print-container .text-gray-900, .dark .print-container .text-gray-800 { color: #1f2937 !important; }
        .dark .print-container .text-gray-600 { color: #4b5563 !important; }
        .dark .print-container .text-gray-500 { color: #6b7280 !important; }
        .rapor-content { transform-origin: top left; }
    </style>
    <script>
        /* Rapor harus muat satu halaman F4. Bila isi lebih tinggi dari ruang di dalam bingkai (logo di tengah,
           font lebar, catatan panjang), isi diperkecil seperlunya; lebar ikut dilebarkan supaya tetap penuh.
           Ruang tanda tangan sengaja sama di layar & cetak, jadi ukuran di layar = ukuran cetak. */
        window.fitRaporSheets = function () {
            document.querySelectorAll('.print-container').forEach((sheet) => {
                const content = sheet.querySelector('.rapor-content');
                const style = getComputedStyle(sheet);
                const available = sheet.querySelector('.rapor-border').offsetHeight - parseFloat(style.paddingTop) - parseFloat(style.paddingBottom);
                let scale = 1;
                for (let i = 0; i < 4; i++) {
                    content.style.width = `${100 / scale}%`;
                    content.style.transform = scale < 1 ? `scale(${scale})` : '';
                    const height = content.offsetHeight;
                    content.style.marginBottom = `${height * scale - height}px`;
                    if (height * scale <= available + 0.5) break;
                    scale = available / height;
                }
            });
        };
        document.addEventListener('DOMContentLoaded', () => {
            fitRaporSheets();
            // Pratinjau Pengaturan Rapor: ukur ulang saat isian/pilihan form mengubah tinggi isi.
            const observer = new ResizeObserver(() => requestAnimationFrame(fitRaporSheets));
            document.querySelectorAll('.rapor-content').forEach((content) => observer.observe(content));
        });
        window.addEventListener('load', () => fitRaporSheets());
        window.addEventListener('beforeprint', () => fitRaporSheets());
    </script>
@endonce
<div class="print-container mx-auto bg-white shadow-sm {{ ($pageBreak ?? false) ? 'page-break mt-8 print:mt-0' : '' }}" style="font-family: {{ $fonts[$layout['font']][1] }};"
     @if ($live) :style="{ fontFamily: @js(collect($fonts)->map(fn ($font) => $font[1]))[reportFont] }" @endif>
    {{-- Bingkai hias F4 (vektor, dibuat dari contoh sekolah); isi lembar ada di dalam garis dalamnya. --}}
    <img src="{{ asset('images/rapor-border-f4.svg') }}" class="rapor-border" alt="" aria-hidden="true">

    <div class="rapor-content">

    <!-- Kop Surat Terpadu -->
    <div class="grid grid-cols-[85px_1fr_85px] items-center border-b border-black pb-3 mb-4">
        <!-- Logo SMA Islam Al Azhar 7: kiri / tengah (di atas basmalah) / kanan, menurut Pengaturan Rapor -->
        <div class="shrink-0 flex justify-start">
            @if ($hasLogo('left'))
                <img src="{{ asset('images/logo_alazhar7.png') }}" class="h-20 w-auto object-contain" alt="Logo SMA Islam Al Azhar 7" {!! $logoAt('left') !!} />
            @endif
        </div>

        <!-- Title & Basmalah -->
        <div class="flex-1 flex flex-col items-center px-2">
            @if ($hasLogo('center'))
                <img src="{{ asset('images/logo_alazhar7.png') }}" class="h-14 w-auto object-contain mb-1" alt="Logo SMA Islam Al Azhar 7" {!! $logoAt('center') !!} />
            @endif
            <img src="{{ asset('images/image1.png') }}" class="h-6 object-contain mb-2" alt="Basmalah" />
            <h1 class="text-xs sm:text-sm font-black text-black uppercase tracking-wider text-center" {!! $bind('reportMainTitle') !!}>{{ $sheet['letterhead']['main_title'] }}</h1>
            <h2 class="text-[10px] sm:text-xs font-bold text-black uppercase text-center mt-0.5" {!! $bind('reportSchoolName') !!}>{{ $sheet['letterhead']['school_name'] }}</h2>

            <!-- Periode Rapor -->
            <div class="border border-black px-4 py-0.5 mt-2 bg-gray-50 text-[9px] font-bold text-black uppercase" {!! $bind('periodLabels[reportPeriod]') !!}>
                {{ $sheet['period_label'] }}
            </div>

            <p class="text-[9px] font-bold text-black mt-1">Tahun Ajaran <span {!! $bind('academicYear') !!}>{{ $sheet['academic_year'] }}</span></p>
        </div>

        <!-- Kolom kanan: logo bila posisi kanan, selain itu penyeimbang kop -->
        <div class="shrink-0 flex justify-end">
            @if ($hasLogo('right'))
                <img src="{{ asset('images/logo_alazhar7.png') }}" class="h-20 w-auto object-contain" alt="Logo SMA Islam Al Azhar 7" {!! $logoAt('right') !!} />
            @endif
        </div>
    </div>

    <!-- Identitas Siswa: Nama & NIS di kiri, Kelas & Term di kanan (sebaris) -->
    <table class="w-full text-xs text-black mb-4" style="line-height: 1.6;">
        <colgroup>
            <col class="w-20"><col class="w-4"><col>
            <col class="w-14"><col class="w-4"><col class="w-28">
        </colgroup>
        <tr>
            <td class="font-bold">Nama</td>
            <td>:</td>
            <td>{{ $sheet['student']['name'] }}</td>
            <td class="font-bold">Kelas</td>
            <td>:</td>
            <td>{{ $sheet['student']['class'] ?: '-' }}</td>
        </tr>
        <tr>
            <td class="font-bold">NIS/NISN</td>
            <td>:</td>
            <td>{{ $sheet['student']['number'] ?: '-' }}</td>
            <td class="font-bold">Term</td>
            <td>:</td>
            <td{!! $live ? ' x-text="'.e(\Illuminate\Support\Js::from(\App\Http\Controllers\StudentReportController::TERM_ROMAN)).'[reportPeriod]"' : '' !!}>{{ \App\Http\Controllers\StudentReportController::TERM_ROMAN[$sheet['term']] }}</td>
        </tr>
    </table>

    <!-- I. LAPORAN TAHFIZH -->
    @if ($showModule('tahfizh'))
    <div class="mb-4 space-y-2" {!! $moduleToggle('showTahfizh') !!}>
        <h3 class="text-xs font-black uppercase text-black">I. LAPORAN TAHFIZH</h3>

        @php
            $tahfizh = $sheet['tahfizh'];
            $isUmmi = ($tahfizh['layout'] ?? null) === 'ummi';
            $positionCols = $isUmmi ? ['jilid' => 'Jilid', 'halaman' => 'Hal.', 'surah' => 'Surah', 'ayat' => 'Ayat'] : ['surah' => 'Surah', 'ayat' => 'Ayat'];
            // 'description' ada sejak deskripsi bawaan Tuntas/Tidak Tuntas; simpanan terkunci yang lebih lama belum punya.
            $tahfizhDescription = $tahfizh['description'] ?? (($tahfizh['notes'] ?? null) ?: ($tahfizh['completed'] ? 'Tercapai.' : 'Belum tercapai.'));
        @endphp
        @if (isset($tahfizh['rows']))
            @include('reports.partials.report-sheet-tahfizh-legacy')
        @else
            {{-- Satu baris untuk triwulan rapor, sama dengan Target Triwulan / Capaian Akhir di Laporan Triwulan.
                 Kelas 10/Ummi: Jilid|Hal.|Surah|Ayat + kolom Nilai; Kelas 11/12: Surah|Ayat + Baris, tanpa nilai.
                 Deskripsi di baris bawah selebar tabel (kolom sempit membuat lembar melewati bingkai). --}}
            <table class="w-full border border-black text-xs text-center">
                <thead>
                    <tr class="bg-gray-100 border-b border-black font-bold">
                        <th rowspan="2" class="p-1.5 border-r border-black w-8">No.</th>
                        <th colspan="{{ count($positionCols) }}" class="p-1.5 border-r border-black border-b">TARGET TRIWULAN</th>
                        <th colspan="{{ count($positionCols) }}" class="p-1.5 border-r border-black border-b">CAPAIAN AKHIR</th>
                        @unless ($isUmmi)
                            <th rowspan="2" class="p-1.5 border-r border-black">BARIS<br><span class="font-normal">Capaian / Target</span></th>
                        @endunless
                        <th rowspan="2" class="p-1.5 w-20 {{ $isUmmi ? 'border-r border-black' : '' }}">STATUS</th>
                        @if ($isUmmi)
                            <th rowspan="2" class="p-1.5 w-32">NILAI</th>
                        @endif
                    </tr>
                    <tr class="bg-gray-100 border-b border-black">
                        @foreach ([1, 2] as $unused)
                            @foreach ($positionCols as $label)
                                <th class="p-1 border-r border-black font-semibold">{{ $label }}</th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-black">
                        <td class="p-1.5 border-r border-black align-middle">1</td>
                        @foreach (['target', 'capaian'] as $side)
                            @foreach (array_keys($positionCols) as $key)
                                <td class="p-1.5 border-r border-black align-middle font-semibold">{{ $tahfizh[$side][$key] ?? '-' }}</td>
                            @endforeach
                        @endforeach
                        @unless ($isUmmi)
                            <td class="p-1.5 border-r border-black align-middle font-semibold whitespace-nowrap">
                                {{ $tahfizh['lines'] ? $tahfizh['lines']['achieved'].' / '.$tahfizh['lines']['target'] : '-' }}
                            </td>
                        @endunless
                        <td class="p-1.5 align-middle font-bold whitespace-nowrap {{ $isUmmi ? 'border-r border-black' : '' }} {{ $tahfizh['completed'] ? 'text-green-700' : 'text-rose-700' }}">
                            {{ $tahfizh['completed'] ? 'Tuntas' : 'Tidak Tuntas' }}
                        </td>
                        @if ($isUmmi)
                            {{-- "89 / Mumtaz": istilah Arab predikat (Pengaturan Adab); kurung dibuang untuk simpanan terkunci lama. --}}
                            <td class="p-1.5 align-middle font-black text-black text-sm">
                                {{ $tahfizh['final_score'] }}@if (! empty($tahfizh['final_predicate'])) / {{ preg_replace('/\s*\(.*\)$/', '', $tahfizh['final_predicate']) }}@endif
                            </td>
                        @endif
                    </tr>
                    <tr class="border-b border-black">
                        {{-- No + 2x kolom posisi + Status + (Nilai untuk Ummi | Baris untuk 11/12) --}}
                        <td colspan="{{ 3 + 2 * count($positionCols) }}" class="p-2 text-left text-gray-700 leading-relaxed">
                            <span class="font-bold text-black">Deskripsi:</span> {{ $tahfizhDescription }}
                        </td>
                    </tr>
                </tbody>
            </table>
        @endif
    </div>

    @endif

    <!-- II. PENILAIAN ADAB -->
    @if ($showModule('adab'))
    <div class="mb-4 space-y-2" {!! $moduleToggle('showAdab') !!}>
        <h3 class="text-xs font-black uppercase text-black">II. PENILAIAN ADAB</h3>

        <table class="w-full border border-black text-xs text-left">
            <thead>
                <tr class="bg-gray-100 border-b border-black text-center font-bold">
                    <th class="p-1 border-r border-black w-10">No.</th>
                    <th class="p-1 border-r border-black w-56">KOMPONEN ADAB</th>
                    <th class="p-1 border-r border-black w-24">Nilai</th>
                    <th class="p-1">Deskripsi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sheet['adab']['categories'] as $catIdx => $catTitle)
                    <tr class="border-b border-black">
                        <td class="p-2 border-r border-black text-center align-middle">{{ $catIdx + 1 }}</td>
                        <td class="p-2 border-r border-black font-semibold align-middle uppercase">{{ $catTitle }}</td>
                        @if ($catIdx === 0)
                            <td rowspan="{{ $adabCount }}" class="p-2 border-r border-black text-center align-middle font-bold text-sm text-black">
                                <span class="text-base font-black">{{ $sheet['adab']['grade'] }}</span>
                                <span class="text-[9px] font-bold text-gray-700 block mt-1 uppercase">{{ $sheet['adab']['score'] }}/100</span>
                            </td>
                            <td rowspan="{{ $adabCount }}" class="p-3 text-gray-700 leading-relaxed align-middle">
                                <div class="font-bold text-black mb-1">{{ $sheet['adab']['grade_label'] }}</div>
                                {{ $sheet['adab']['description'] }}
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @endif

    <!-- III. LAPORAN TANSE -->
    @if ($showModule('tanse'))
    <div class="mb-4 space-y-2" {!! $moduleToggle('showTanse') !!}>
        <h3 class="text-xs font-black uppercase text-black">III. LAPORAN TANSE</h3>

        <table class="w-full border border-black text-xs text-left">
            <thead>
                <tr class="bg-gray-100 border-b border-black text-center font-bold">
                    <th class="p-1 border-r border-black w-10">No.</th>
                    <th class="p-1 border-r border-black w-48">JENIS PERILAKU</th>
                    <th class="p-1 border-r border-black w-24">POIN</th>
                    <th class="p-1">Deskripsi</th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-black">
                    <td class="p-2 border-r border-black text-center">1</td>
                    <td class="p-2 border-r border-black font-bold">Penghargaan</td>
                    <td class="p-2 border-r border-black text-center font-bold text-emerald-700">{{ $sheet['tanse']['reward_points'] }}</td>
                    {{-- Satu deskripsi untuk seluruh Tanse, berdasarkan predikat triwulan. --}}
                    <td rowspan="2" class="p-2 text-gray-900 align-top">
                        <p class="font-black">Predikat {{ $sheet['tanse']['grade'] }}</p>
                        <p class="mt-1 leading-relaxed">{{ $sheet['tanse']['notes'] }}</p>
                    </td>
                </tr>
                <tr class="border-b border-black">
                    <td class="p-2 border-r border-black text-center">2</td>
                    <td class="p-2 border-r border-black font-bold">Pelanggaran</td>
                    <td class="p-2 border-r border-black text-center font-bold text-rose-700">{{ $sheet['tanse']['violation_points'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @endif

    <!-- Signature Area: tiga koordinator berjajar di atas, Kepala Sekolah di tengah bawah -->
    {{-- Titimangsa = tanggal BLP periode; belum diatur = titik-titik untuk diisi tangan. --}}
    @php $titimangsa = $sheet['letterhead']['city'].', '.($sheet['letterhead']['date'] ?? '........................'); @endphp
    <div class="signature-block w-full text-xs text-black mt-5">
        @foreach ([['coord_tahfizh', 'coord_keagamaan', 'coord_tanse'], ['headmaster']] as $rowIdx => $keys)
            <div class="{{ $rowIdx === 0 ? 'grid grid-cols-3 gap-6' : 'mt-3 mx-auto w-1/2' }} text-center">
                @foreach ($keys as $colIdx => $key)
                    @php $official = $sheet['signatories'][$key]; @endphp
                    <div>
                        {{-- Baris atas: titimangsa di atas koordinator paling kanan (lainnya penyeimbang tak terlihat);
                             baris bawah: "Mengetahui," di atas Kepala Sekolah. --}}
                        @if ($rowIdx === 0)
                            <p class="{{ $colIdx < 2 ? 'invisible select-none' : '' }}" {!! $bind("reportCity + ', ' + reportDate") !!}>{{ $titimangsa }}</p>
                        @else
                            <p>Mengetahui,</p>
                        @endif
                        @php [$liveName, $liveNik, $liveTitle] = $liveOfficials[$key]; @endphp
                        <p class="font-semibold" {!! $liveTitle ? $bind($liveTitle) : '' !!}>{{ $official['title'] }}</p>
                        @if ($live)
                            {{-- Pratinjau: tanda tangan tersimpan / yang baru dipilih di form (state Alpine `sig`). --}}
                            <div class="h-12 flex items-center justify-center">
                                <template x-if="sig['{{ $key }}']"><img :src="sig['{{ $key }}']" alt="Tanda tangan" class="max-h-full max-w-[160px] object-contain"></template>
                            </div>
                        @else
                            @include('reports.partials.signature-slot', ['uri' => $signature($key)])
                        @endif
                        <p class="font-bold underline text-black" {!! $bind($liveName) !!}>{{ $official['name'] }}</p>
                        <p class="text-[10px] text-gray-600">NIK. <span {!! $bind($liveNik) !!}>{{ $official['nik'] }}</span></p>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
    </div>{{-- .rapor-content --}}
</div>
