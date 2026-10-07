{{-- Ruang tanda tangan pejabat di rapor cetak: gambar bila sudah diunggah (Pengaturan Umum), selain itu kosong. --}}
@if (! empty($uri))
    <div class="h-20 flex items-center justify-center">
        <img src="{{ $uri }}" alt="Tanda tangan" class="max-h-full max-w-[240px] object-contain">
    </div>
@else
    <div class="h-20"></div>
@endif
