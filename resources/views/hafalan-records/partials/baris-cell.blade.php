{{--
    Kolom Baris riwayat setoran (tab Reguler): baris yang DIHITUNG capaian (ayat baru saja), sama dengan
    rapor & Laporan Triwulan (HafalanProgressService::countedLinesForHeaders). Bila berbeda dari baris
    yang dicatat guru, baris catatan & alasannya ditampilkan kecil di bawahnya.
    $record: HafalanRecord · $counted: ['counted', 'raw', 'kinds'] atau null · $suffix: teks setelah angka.
--}}
@php
    $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, ',', ''), '0'), ',');
    $labels = ['partial' => 'sebagian ulang', 'repeat' => 'ulang', 'duplicate' => 'ganda', 'not_passed' => 'belum lulus'];
    $differs = $counted && abs($counted['counted'] - $counted['raw']) >= 0.05;
@endphp
<span title="Baris ayat baru yang dihitung capaian (ulangan tidak dihitung)">{{ $fmt($counted['counted'] ?? $record->lines_count) }}{{ $suffix ?? '' }}</span>
@if ($differs)
    <span class="block text-[10px] font-semibold text-amber-600 dark:text-amber-400 whitespace-nowrap"
          title="Dicatat guru {{ $fmt($counted['raw']) }} baris; yang dihitung hanya ayat baru">
        dari {{ $fmt($counted['raw']) }} · {{ collect($counted['kinds'])->map(fn ($k) => $labels[$k] ?? $k)->implode(', ') ?: 'ulang' }}
    </span>
@endif
