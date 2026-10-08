<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight flex items-center gap-2">
                <x-heroicon-o-book-open class="w-6 h-6 text-teal-600 dark:text-teal-400" />
                <span>Hafalan &amp; Arah Juz</span>
            </h2>
            <p class="text-sm text-gray-600 dark:text-zinc-400">
                Tandai juz yang sudah dihafal murid (sebelum aplikasi) dan arah hafalan di juz yang sedang dihafal. Dipakai untuk target, capaian &amp; rapor.
            </p>
        </div>
    </x-slot>

    {{-- Bola juz berisi "cairan": tinggi = persen hafal juz; kuning 0-99%, hijau 100%. --}}
    <style>
        .juz-ball { position: relative; width: 2.25rem; height: 2.25rem; border-radius: 9999px; overflow: hidden; border: 2px solid #d4d4d8; background: #fff; cursor: pointer; transition: border-color .2s, box-shadow .2s; }
        .dark .juz-ball { background: #18181b; border-color: #52525b; }
        .juz-ball.is-partial { border-color: #f59e0b; }
        .juz-ball.is-full { border-color: #059669; }
        .juz-ball.is-current { box-shadow: 0 0 0 3px rgba(13, 148, 136, .35); }
        .juz-ball.is-locked { cursor: default; }
        .juz-liquid { position: absolute; left: 0; right: 0; bottom: 0; transition: height .5s ease; }
        .juz-liquid.partial { background: #fcd34d; color: #fcd34d; }
        .juz-liquid.full { background: #10b981; color: #10b981; }
        .juz-wave { position: absolute; top: -5px; left: 0; width: 200%; height: 6px; animation: juz-wave 2.4s linear infinite; }
        @keyframes juz-wave { from { transform: translateX(0); } to { transform: translateX(-50%); } }
        .juz-label { position: relative; z-index: 1; display: flex; height: 100%; align-items: center; justify-content: center; font-size: .7rem; font-weight: 800; color: #3f3f46; }
        .dark .juz-label { color: #e4e4e7; }
        .juz-ball.is-partial .juz-label { color: #3f3f46; }
        .juz-ball.is-full .juz-label { color: #fff; }
        .juz-arrow { display: inline-flex; width: 1.1rem; height: 1.1rem; align-items: center; justify-content: center; border-radius: 9999px; border: 1px solid #d4d4d8; color: #71717a; transition: background .15s, color .15s; }
        .dark .juz-arrow { border-color: #52525b; color: #a1a1aa; }
        .juz-arrow.active { background: #0d9488; border-color: #0d9488; color: #fff; }
        .juz-arrow:disabled { opacity: .25; cursor: not-allowed; }
    </style>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-4 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <form method="GET" action="{{ route('juz-map.index') }}">
                    <select name="class_room_id" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm font-semibold">
                        @foreach ($classRooms as $class)
                            <option value="{{ $class->id }}" @selected($selectedClass?->id === $class->id)>{{ $class->name }} ({{ $class->program?->name ?? '-' }})</option>
                        @endforeach
                    </select>
                </form>
                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-xs text-gray-600 dark:text-zinc-400">
                    <span class="inline-flex items-center gap-1.5"><span class="inline-block w-3.5 h-3.5 rounded-full bg-emerald-500"></span> Hafal penuh (100%)</span>
                    <span class="inline-flex items-center gap-1.5"><span class="inline-block w-3.5 h-3.5 rounded-full bg-amber-300 border border-amber-500"></span> Sebagian (0–99%)</span>
                    <span class="inline-flex items-center gap-1.5"><span class="inline-block w-3.5 h-3.5 rounded-full ring-2 ring-teal-500/40 border border-gray-300"></span> Sedang dihafal</span>
                    <span class="inline-flex items-center gap-1"><span class="juz-arrow active"><x-heroicon-m-arrow-up class="w-3 h-3" /></span> maju (dari awal juz)</span>
                    <span class="inline-flex items-center gap-1"><span class="juz-arrow active"><x-heroicon-m-arrow-down class="w-3 h-3" /></span> mundur (dari akhir juz)</span>
                </div>
            </div>

            <p class="text-xs text-gray-500 dark:text-zinc-400">
                Klik bola juz untuk menandai juz itu <strong>sudah hafal sebelum aplikasi</strong> (jadi hijau); klik lagi untuk membatalkan.
                Juz yang hijau karena setoran tidak bisa dibatalkan di sini. Panah hanya aktif di juz yang sedang dihafal.
            </p>

            <div x-data="juzMap(@js($rows), @js(csrf_token()))" class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-zinc-800/60 text-[11px] font-black uppercase tracking-wider text-gray-500 dark:text-zinc-400">
                        <tr>
                            <th class="sticky left-0 z-10 bg-gray-50 dark:bg-zinc-800 px-4 py-3 text-left min-w-[220px]">Murid</th>
                            <th class="px-3 py-3 text-left">Juz 30 → 1</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                        <template x-for="(row, rowIndex) in rows" :key="row.id">
                            <tr class="align-top" :class="busy[row.id] ? 'opacity-60' : ''">
                                <td class="sticky left-0 z-10 bg-white dark:bg-zinc-900 px-4 py-3">
                                    <p class="font-bold text-gray-900 dark:text-white" x-text="row.name"></p>
                                    <p class="text-[11px] text-gray-500 capitalize" x-text="row.level"></p>
                                    <p class="text-[11px] mt-0.5" :class="row.current ? 'text-teal-700 dark:text-teal-400' : 'text-gray-400'"
                                       x-text="row.ummi ? 'Metode Ummi: arah tidak dipakai' : (row.current ? 'Sedang: Juz ' + row.current + ' · ' + (orderOf(row, row.current) === 'desc' ? 'mundur' : 'maju') : 'Semua juz sudah hafal')"></p>
                                </td>
                                <td class="px-3 py-3">
                                    <div class="flex gap-1.5">
                                        <template x-for="cell in row.juz" :key="cell.juz">
                                            <div class="flex flex-col items-center gap-1">
                                                <button type="button" @click="toggle(row, rowIndex, cell)"
                                                        class="juz-ball"
                                                        :class="{ 'is-full': cell.percent >= 100, 'is-partial': cell.percent > 0 && cell.percent < 100, 'is-current': row.current === cell.juz, 'is-locked': cell.percent >= 100 && ! cell.prior }"
                                                        :title="'Juz ' + cell.juz + ' · ' + cell.percent + '%' + (cell.prior ? ' · ditandai hafal sebelum aplikasi (klik untuk batal)' : (cell.percent >= 100 ? ' · hafal dari setoran' : ' · klik untuk tandai hafal'))">
                                                    <span class="juz-liquid" :class="cell.percent >= 100 ? 'full' : 'partial'" :style="'height: ' + cell.percent + '%'">
                                                        <svg x-show="cell.percent > 0 && cell.percent < 100" class="juz-wave" viewBox="0 0 120 6" preserveAspectRatio="none">
                                                            <path fill="currentColor" d="M0 3 Q 15 0 30 3 T 60 3 T 90 3 T 120 3 V 6 H 0 Z" />
                                                        </svg>
                                                    </span>
                                                    <span class="juz-label" x-text="cell.juz"></span>
                                                </button>
                                                <div class="flex gap-0.5">
                                                    <button type="button" class="juz-arrow" :class="row.current === cell.juz && cell.order === 'asc' ? 'active' : ''"
                                                            :disabled="row.current !== cell.juz" @click="setDirection(row, rowIndex, cell, 'asc')" title="Maju: dari awal juz">
                                                        <x-heroicon-m-arrow-up class="w-3 h-3" />
                                                    </button>
                                                    <button type="button" class="juz-arrow" :class="row.current === cell.juz && cell.order === 'desc' ? 'active' : ''"
                                                            :disabled="row.current !== cell.juz" @click="setDirection(row, rowIndex, cell, 'desc')" title="Mundur: dari akhir juz">
                                                        <x-heroicon-m-arrow-down class="w-3 h-3" />
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="rows.length === 0">
                            <td colspan="2" class="px-4 py-12 text-center text-gray-400">Tidak ada murid aktif di kelas ini.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function juzMap(rows, csrf) {
            return {
                rows,
                busy: {},
                orderOf(row, juz) {
                    return (row.juz.find((cell) => cell.juz === juz) || {}).order;
                },
                async send(row, rowIndex, url, body) {
                    if (this.busy[row.id]) return;
                    this.busy[row.id] = true;
                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: JSON.stringify(body),
                        });
                        if (! response.ok) throw new Error(response.status);
                        this.rows[rowIndex] = await response.json();
                    } catch (e) {
                        alert('Gagal menyimpan. Muat ulang halaman lalu coba lagi.');
                    } finally {
                        this.busy[row.id] = false;
                    }
                },
                toggle(row, rowIndex, cell) {
                    if (cell.percent >= 100 && ! cell.prior) return; // hafal dari setoran: tidak bisa dibatalkan di sini
                    if (cell.prior && ! confirm('Batalkan tanda hafal Juz ' + cell.juz + ' untuk ' + row.name + '?')) return;
                    this.send(row, rowIndex, row.toggle_url, { juz: cell.juz });
                },
                setDirection(row, rowIndex, cell, order) {
                    if (row.current !== cell.juz || cell.order === order) return;
                    this.send(row, rowIndex, row.direction_url, { juz: cell.juz, order });
                },
            };
        }
    </script>
</x-app-layout>
