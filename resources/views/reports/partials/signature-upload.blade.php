{{--
    Tanda tangan satu pejabat di Pengaturan Rapor (data sama dengan Pengaturan Umum).
    Super Admin: unggah/hapus, langsung tampil di Live Preview lewat state Alpine `sig`.
    Admin: hanya melihat. Parameter: $key (kunci Signatures::OFFICIALS), $canEdit.
--}}
<div class="pt-2 border-t border-dashed border-gray-200 dark:border-zinc-700">
    <p class="block text-[11px] font-bold text-gray-600 dark:text-zinc-400 mb-1.5">Tanda Tangan</p>
    <div class="flex items-center gap-3">
        <div class="h-14 w-28 shrink-0 rounded-lg border border-gray-200 dark:border-zinc-700 bg-white flex items-center justify-center overflow-hidden">
            <template x-if="sig['{{ $key }}']">
                <img :src="sig['{{ $key }}']" alt="Tanda tangan" class="max-h-12 max-w-[104px] object-contain">
            </template>
            <template x-if="! sig['{{ $key }}']">
                <span class="text-[10px] text-gray-400">Belum ada</span>
            </template>
        </div>
        @if ($canEdit)
            <div class="min-w-0 space-y-1.5">
                <input type="file" name="signatures[{{ $key }}]" accept="image/png,image/jpeg,image/webp"
                       x-on:change="pickSignature('{{ $key }}', $event)"
                       class="block w-full text-[11px] text-gray-500 file:mr-2 file:py-1.5 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-indigo-50 file:text-indigo-700 file:cursor-pointer hover:file:bg-indigo-100 dark:file:bg-zinc-800 dark:file:text-zinc-200">
                <label x-show="sigSaved['{{ $key }}']" class="inline-flex items-center text-[11px] text-red-600 cursor-pointer">
                    <input type="checkbox" name="reset_signatures[]" value="{{ $key }}" x-on:change="resetSignature('{{ $key }}', $event.target.checked)"
                           class="rounded border-gray-300 text-red-600 focus:ring-red-500 mr-1.5">
                    Hapus tanda tangan
                </label>
            </div>
        @else
            <p class="text-[11px] text-gray-500 dark:text-zinc-400">Diatur oleh Super Admin.</p>
        @endif
    </div>
    @if ($canEdit)
        <p class="mt-1 text-[10px] text-gray-400">PNG latar transparan disarankan, maks 1 MB. Sama dengan Pengaturan Umum.</p>
    @endif
</div>
