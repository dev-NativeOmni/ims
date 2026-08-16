@props(['milestones' => []])

@php
    $firstRecord = data_get($milestones, 'first_record');
    $journey = data_get($milestones, 'journey', []);
@endphp

<div class="rounded-2xl bg-white dark:bg-zinc-900 p-6 shadow-sm border border-zinc-200 dark:border-zinc-800 space-y-5">
    {{-- ─── HEADER & SETORAN PERTAMA BANNER ─── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800 pb-4">
        <div>
            <h3 class="text-lg font-black text-zinc-900 dark:text-white flex items-center gap-2">
                <span>🗺️ Peta Perjalanan Hafalan Murid</span>
            </h3>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                Tabel rekam jejak capaian & tanggal setoran hafalan pertama yang tercatat di sistem per-Term (Kelas 10, 11, & 12).
            </p>
        </div>

        @if ($firstRecord)
            <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/80 text-emerald-900 dark:text-emerald-200 shadow-sm">
                <span class="text-xl">🌱</span>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Setoran Hafalan Pertama Sistem</p>
                    <p class="text-xs font-extrabold text-zinc-900 dark:text-white">{{ data_get($firstRecord, 'title') }}</p>
                    <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium">Tanggal: {{ data_get($firstRecord, 'date') }}</p>
                </div>
            </div>
        @else
            <div class="px-3.5 py-1.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-xs text-zinc-500 font-medium">
                Belum ada setoran hafalan pertama.
            </div>
        @endif
    </div>

    {{-- ─── TABEL PETA HAFALAN (KELAS 10, 11, 12 X TERM 1, 2, 3, 4) ─── --}}
    <div class="overflow-x-auto rounded-2xl border border-zinc-300 dark:border-zinc-700 shadow-xs">
        <table class="w-full border-collapse text-center text-xs">
            <thead>
                <tr class="bg-zinc-100 dark:bg-zinc-800/90 text-zinc-900 dark:text-zinc-100 font-black uppercase tracking-wider border-b border-zinc-300 dark:border-zinc-700">
                    <th class="py-3.5 px-4 border-r border-zinc-300 dark:border-zinc-700 w-28">KELAS</th>
                    <th class="py-3.5 px-4 border-r border-zinc-300 dark:border-zinc-700">TERM 1</th>
                    <th class="py-3.5 px-4 border-r border-zinc-300 dark:border-zinc-700">TERM 2</th>
                    <th class="py-3.5 px-4 border-r border-zinc-300 dark:border-zinc-700">TERM 3</th>
                    <th class="py-3.5 px-4">TERM 4</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-300 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
                @forelse ($journey as $g)
                    @php
                        $gNum = data_get($g, 'grade_num');
                        $isCurrentGrade = data_get($g, 'is_current_grade');
                        $terms = data_get($g, 'terms', []);
                    @endphp
                    <tr class="hover:bg-zinc-50/70 dark:hover:bg-zinc-850/50 transition">
                        <!-- KELAS Column -->
                        <td class="py-4 px-3 font-black text-sm border-r border-zinc-300 dark:border-zinc-700 text-zinc-900 dark:text-white bg-zinc-50/80 dark:bg-zinc-850/50 align-middle">
                            <div class="flex flex-col items-center justify-center gap-1">
                                <span class="text-base font-black">{{ $gNum }}</span>
                                @if ($isCurrentGrade)
                                    <span class="px-2 py-0.5 text-[9px] rounded-md bg-emerald-500 text-white font-extrabold uppercase tracking-wider shadow-xs">Aktif</span>
                                @endif
                            </div>
                        </td>

                        <!-- TERM 1 to 4 Columns -->
                        @foreach ([1, 2, 3, 4] as $tNum)
                            @php
                                $t = data_get($terms, $tNum, []);
                                $hasData = data_get($t, 'has_data', false);
                                $firstSetoran = data_get($t, 'first_setoran');
                                $isCurrent = data_get($t, 'is_current', false);
                            @endphp
                            <td class="p-3.5 border-r last:border-r-0 border-zinc-300 dark:border-zinc-700 text-center align-middle {{ $isCurrent ? 'bg-emerald-50/30 dark:bg-emerald-950/20' : '' }}">
                                @if ($hasData && $firstSetoran)
                                    <div class="space-y-1">
                                        <!-- Top Line: Surah (ayat awal-ayat akhir) -->
                                        <p class="font-black text-zinc-900 dark:text-zinc-100 text-xs">
                                            {{ data_get($firstSetoran, 'full_text') }}
                                        </p>
                                        <!-- Bottom Line: Tanggal Setoran -->
                                        <p class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 flex items-center justify-center gap-1">
                                            <span>📅</span> {{ data_get($firstSetoran, 'date') }}
                                        </p>
                                    </div>
                                @else
                                    <div class="py-2 text-zinc-400 dark:text-zinc-600 text-xs italic font-medium">
                                        -
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-6 text-center text-xs text-zinc-400">
                            Belum ada data peta hafalan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
