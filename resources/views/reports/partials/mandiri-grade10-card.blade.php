{{--
    Kartu Ziyadah Mandiri Kelas 10 E1 (Grafik Tahfizh): setoran hafalan di luar buku Ummi per murid pada
    periode filter. Baris = ayat baru saja (ulangan tidak dihitung), sama dengan capaian Kelas 11/12.
    Murid khatam 30 juz berbaris emas. Gaya sama dengan ummi-grade10-card (unduh PNG/cetak).
--}}
@php
    $circleImgPath = public_path('images/logo-alazhar7-circle.png');
    $gemilangImgPath = public_path('images/logo-gemilang-banner.png');
    $circleLogoBase64 = file_exists($circleImgPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($circleImgPath)) : asset('images/logo-alazhar7-circle.png');
    $gemilangLogoBase64 = file_exists($gemilangImgPath) ? 'data:image/png;base64,'.base64_encode(file_get_contents($gemilangImgPath)) : asset('images/logo-gemilang-banner.png');
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, ',', ''), '0'), ',');
    $rows = collect($studentReports);
@endphp

<div id="ziyadahMandiriReportCard" class="mx-auto max-w-5xl rounded-[28px] p-5 sm:p-8 shadow-2xl relative font-sans border-[5px] border-sky-500" style="background-color: #ffffff !important; color: #0f172a !important; box-sizing: border-box !important;">
    <div class="flex items-center justify-center gap-3 sm:gap-5 border-b-2 border-sky-300 pb-3 mb-3 text-center">
        <img src="{{ $circleLogoBase64 }}" alt="Logo Al Azhar 7" class="object-contain shrink-0" style="height: 48px !important; width: 48px !important;">
        <img src="{{ $gemilangLogoBase64 }}" alt="SMA Islam Al Azhar 7 GEMILANG" class="object-contain shrink-0" style="height: 44px !important; max-width: 320px !important;">
    </div>

    <div class="text-center my-3">
        <h1 class="text-xl sm:text-3xl font-black tracking-wide uppercase leading-none" style="color: #0f172a !important;">LAPORAN CAPAIAN TAHFIZH</h1>
        <div class="inline-flex items-center gap-2 text-xs font-black px-5 py-1 rounded-xl shadow-sm mt-2 border uppercase tracking-widest" style="background-color: #0c4a6e !important; color: #7dd3fc !important; border-color: #38bdf8 !important;">
            <span>ZIYADAH MANDIRI</span>
        </div>
        <div class="flex justify-center items-center gap-2 mt-2.5 flex-wrap">
            <div class="font-extrabold text-xs px-4 py-1 rounded-lg shadow-sm uppercase" style="background-color: #1e40af !important; color: #ffffff !important;">KELAS {{ $selectedClass?->name ?? '10' }}</div>
            <div class="font-extrabold text-xs px-4 py-1 rounded-lg shadow-sm uppercase" style="background-color: #0284c7 !important; color: #ffffff !important;">{{ $periodLabel ?? '' }}</div>
        </div>
    </div>

    <div class="mt-4 overflow-hidden rounded-xl border-2 shadow-sm" style="border-color: #0369a1 !important; background-color: #ffffff !important;">
        <table class="w-full text-left border-collapse text-xs" style="background-color: #ffffff !important; color: #0f172a !important;">
            <thead>
                <tr class="text-center font-bold tracking-wide" style="background-color: #0369a1 !important; color: #ffffff !important;">
                    <th class="py-2 px-2 w-10" style="border-right: 1px solid #0284c7;">No</th>
                    <th class="py-2 px-3 text-left" style="border-right: 1px solid #0284c7;">Nama Murid</th>
                    <th class="py-2 px-3 text-left" style="border-right: 1px solid #0284c7;">Capaian Ziyadah</th>
                    <th class="py-2 px-2 w-20" style="border-right: 1px solid #0284c7;">Setoran</th>
                    <th class="py-2 px-2 w-24">Jumlah Baris</th>
                </tr>
            </thead>
            <tbody style="color: #0f172a !important;">
                @forelse ($rows as $idx => $row)
                    <tr style="background-color: {{ ! empty($row['khatam']) ? '#fef3c7' : ($idx % 2 === 0 ? '#ffffff' : '#f0f9ff') }} !important; border-top: 1px solid #e2e8f0;">
                        <td class="py-1.5 px-2 text-center font-bold" style="color: #334155 !important; border-right: 1px solid #e2e8f0;">{{ $idx + 1 }}</td>
                        <td class="py-1.5 px-3 font-bold" style="color: #0f172a !important; border-right: 1px solid #e2e8f0;">
                            {{ $row['student']->name }}
                            @if (! empty($row['khatam']))
                                <span class="ml-1 inline-block rounded px-1.5 py-0.5 text-[9px] font-black uppercase" style="background-color: #d97706 !important; color: #ffffff !important;">Khatam 30 Juz</span>
                            @endif
                        </td>
                        <td class="py-1.5 px-3 font-semibold" style="color: #0369a1 !important; border-right: 1px solid #e2e8f0;">{{ $row['ziyadah'] ?: '-' }}</td>
                        <td class="py-1.5 px-2 text-center font-semibold" style="color: #1e293b !important; border-right: 1px solid #e2e8f0;">{{ ($row['mandiri_setoran'] ?? 0) ? $row['mandiri_setoran'].' kali' : '-' }}</td>
                        <td class="py-1.5 px-2 text-center font-black" style="color: #0c4a6e !important;">{{ $num($row['mandiri_baris'] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-6 text-center text-xs font-semibold" style="color: #64748b !important;">Belum ada setoran ziyadah mandiri untuk kelas ini.</td></tr>
                @endforelse
                @if ($rows->isNotEmpty())
                    <tr style="background-color: #e0f2fe !important; border-top: 2px solid #0369a1;">
                        <td colspan="3" class="py-1.5 px-3 text-right font-black" style="color: #0c4a6e !important; border-right: 1px solid #bae6fd;">Jumlah kelas</td>
                        <td class="py-1.5 px-2 text-center font-black" style="color: #0c4a6e !important; border-right: 1px solid #bae6fd;">{{ $rows->sum('mandiri_setoran') }} kali</td>
                        <td class="py-1.5 px-2 text-center font-black" style="color: #0c4a6e !important;">{{ $num($rows->sum('mandiri_baris')) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <p class="mt-2 text-[10px] font-semibold" style="color: #475569 !important;">
        Jumlah baris = baris ayat baru yang disetor pada periode ini (setoran ulang ayat yang sudah lulus tidak dihitung).
        @if ($rows->contains(fn ($row) => ! empty($row['khatam'])))
            <span style="color: #92400e !important;">Baris emas = murid yang sudah khatam 30 juz.</span>
        @endif
    </p>
</div>
