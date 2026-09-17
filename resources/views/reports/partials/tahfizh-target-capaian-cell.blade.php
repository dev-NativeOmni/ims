{{--
    Isi sel kolom Target/Capaian tahfizh di rapor cetak.
    $target: baris HafalanTarget yang sedang dirender.
    $mode: 'target' atau 'capaian'.
    UMMI dan Reguler khusus digunakan pada target yang dibuat lewat alur
    UMMI (target->ummi_jilid terisi) -- target Reguler murni tetap
    memakai format satu baris "QS. Surah (Ayat X)" seperti sebelumnya.
--}}
@php
    $isUmmiTarget = ! empty($target->ummi_jilid);
@endphp
@if ($isUmmiTarget)
    @if ($mode === 'target')
        {{-- ummi_jilid sudah berupa label lengkap ("Jilid 2", "Gharib", dst), tidak perlu prefix "Jilid" lagi. --}}
        <div>Ummi : {{ $target->ummi_jilid }}{{ ($target->halaman_buku ?: $target->halaman_peraga) ? ' Hal '.($target->halaman_buku ?: $target->halaman_peraga) : '' }}</div>
        @if ($target->surah)
            <div>Tahfizh : Surah {{ $target->surah->name_latin }}{{ $target->ayah ? ' Ayat '.$target->ayah : '' }}</div>
        @endif
    @else
        <div>Ummi : {{ $latestUmmiJilid ?: '-' }}{{ $latestUmmiHalaman ? ' Hal '.$latestUmmiHalaman : '' }}</div>
        @php
            $tahfizhCapaian = $target->matching_record ?: ($latestJuz30Hafalan ?? null);
        @endphp
        @if ($tahfizhCapaian && $tahfizhCapaian->surah)
            <div>Tahfizh : Surah {{ $tahfizhCapaian->surah->name_latin }} Ayat {{ $tahfizhCapaian->ayah_start }}-{{ $tahfizhCapaian->ayah_end }}</div>
        @endif
    @endif
@elseif ($mode === 'target')
    QS. {{ $target->surah?->name_latin ?? '-' }} (Ayat {{ $target->ayah_range }})
@else
    @if ($target->matching_record)
        QS. {{ $target->matching_record->surah?->name_latin ?? '-' }} (Ayat {{ $target->matching_record->ayah_start }}-{{ $target->matching_record->ayah_end }})
    @else
        {{ $latestCapaianText ?: '-' }}
    @endif
@endif
