{{--
    Satu lembar rapor cetak (dipakai cetak per santri & per kelas).
    $sheet: isi rapor siap tampil dari StudentReportController::buildSheet() -- dihitung
    langsung, atau simpanan yang dibekukan saat periode dikunci. Hanya baca dari $sheet
    supaya rapor terkunci tetap utuh walau data/pengaturan berubah.
    $signatureUris: path berkas tanda tangan => data URI.
    $pageBreak: true untuk lembar kedua dst. pada cetak per kelas.
--}}
@php
    $signature = fn ($key) => $signatureUris[$sheet['signatories'][$key]['signature'] ?? ''] ?? null;
    $adabCount = count($sheet['adab']['categories']);
@endphp
<div class="print-container mx-auto bg-white shadow-sm {{ ($pageBreak ?? false) ? 'page-break mt-8 print:mt-0' : '' }}" style="font-family: 'Times New Roman', serif;">
    {{-- Bingkai hias F4 (vektor, dibuat dari contoh sekolah); isi lembar ada di dalam garis dalamnya. --}}
    <img src="{{ asset('images/rapor-border-f4.svg') }}" class="rapor-border" alt="" aria-hidden="true">

    <!-- Kop Surat Terpadu -->
    <div class="grid grid-cols-[85px_1fr_85px] items-center border-b border-black pb-3 mb-4">
        <!-- Left Logo: SMA Islam Al Azhar 7 -->
        <div class="shrink-0 flex justify-start">
            <img src="{{ asset('images/logo_alazhar7.png') }}" class="h-20 w-auto object-contain" alt="Logo SMA Islam Al Azhar 7" />
        </div>

        <!-- Title & Basmalah -->
        <div class="flex-1 flex flex-col items-center px-2">
            <img src="{{ asset('images/image1.png') }}" class="h-6 object-contain mb-2" alt="Basmalah" />
            <h1 class="text-xs sm:text-sm font-black text-black uppercase tracking-wider text-center">{{ $sheet['letterhead']['main_title'] }}</h1>
            <h2 class="text-[10px] sm:text-xs font-bold text-black uppercase text-center mt-0.5">{{ $sheet['letterhead']['school_name'] }}</h2>

            <!-- Periode Rapor -->
            <div class="border border-black px-4 py-0.5 mt-2 bg-gray-50 text-[9px] font-bold text-black uppercase">
                {{ $sheet['period_label'] }}
            </div>

            <p class="text-[9px] font-bold text-black mt-1">Tahun Ajaran {{ $sheet['academic_year'] }}</p>
        </div>

        <!-- Right Spacer for Header Balance -->
        <div class="shrink-0 w-[85px]"></div>
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
            <td>{{ \App\Http\Controllers\StudentReportController::TERM_ROMAN[$sheet['term']] }}</td>
        </tr>
    </table>

    <!-- I. LAPORAN TAHFIZH -->
    <div class="mb-4 space-y-2">
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
                            {{-- "89 / Jayyid Jiddan": predikat tanpa terjemahan dalam kurung. --}}
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

    <!-- II. PENILAIAN ADAB -->
    <div class="mb-4 space-y-2">
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

    <!-- III. LAPORAN TANSE -->
    <div class="mb-4 space-y-2">
        <h3 class="text-xs font-black uppercase text-black">III. LAPORAN TANSE <span class="font-semibold normal-case">&mdash; {{ $sheet['tanse']['term_label'] }}</span></h3>

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
                            <p class="{{ $colIdx < 2 ? 'invisible select-none' : '' }}">{{ $titimangsa }}</p>
                        @else
                            <p>Mengetahui,</p>
                        @endif
                        <p class="font-semibold">{{ $official['title'] }}</p>
                        @include('reports.partials.signature-slot', ['uri' => $signature($key)])
                        <p class="font-bold underline text-black">{{ $official['name'] }}</p>
                        <p class="text-[10px] text-gray-600">NIK. {{ $official['nik'] }}</p>
                    </div>
                @endforeach
            </div>
        @endforeach
    </div>
</div>
