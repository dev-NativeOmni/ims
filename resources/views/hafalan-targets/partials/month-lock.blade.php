{{--
    Status & tombol kunci target satu bulan untuk satu kelas (dipakai di kepala kolom bulan Target
    Triwulan & Target Ummi). Tombolnya mengirim form tersembunyi dari partials/month-lock-forms
    (atribut form=), karena kepala kolom berada di dalam form simpan target.

    Variabel: $lock (TargetLock|null), $classId, $monthKey ("Y-m"), $canLock, $canUnlock
--}}
@if ($lock)
    <p class="mt-1 inline-flex items-center gap-1 rounded-md bg-gray-800 px-1.5 py-0.5 text-[10px] font-bold normal-case text-white dark:bg-zinc-700"
       title="Dikunci {{ $lock->locker?->name ?? 'pengguna terhapus' }}, {{ $lock->locked_at?->locale('id')->translatedFormat('d M Y H:i') }}">
        <x-heroicon-s-lock-closed class="h-3 w-3" /> Terkunci
    </p>
    <p class="text-[10px] font-normal normal-case text-gray-400">{{ $lock->locker?->name ?? '–' }} · {{ $lock->locked_at?->locale('id')->translatedFormat('d M Y') }}</p>
    @if ($canUnlock && $classId)
        <button type="submit" form="unlock-{{ $classId }}-{{ $monthKey }}"
                onclick="return confirm('Buka kunci target bulan ini? Target bisa diubah lagi.')"
                class="mt-1 text-[10px] font-bold normal-case text-rose-600 hover:underline cursor-pointer">Buka kunci</button>
    @endif
@elseif ($canLock && $classId)
    <button type="submit" form="lock-{{ $classId }}-{{ $monthKey }}"
            onclick="return confirm('Kunci target bulan ini? Isian target tidak bisa diubah sampai Super Admin membuka kuncinya. Simpan dulu perubahan yang belum disimpan.')"
            class="mt-1 inline-flex items-center gap-1 rounded-md border border-gray-300 px-1.5 py-0.5 text-[10px] font-bold normal-case text-gray-600 hover:bg-gray-100 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800 cursor-pointer">
        <x-heroicon-o-lock-closed class="h-3 w-3" /> Kunci bulan ini
    </button>
@endif
