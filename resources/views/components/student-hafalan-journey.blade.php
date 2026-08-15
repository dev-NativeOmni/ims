@props(['milestones' => []])

@php
    $firstRecord = data_get($milestones, 'first_record');
    $journey = data_get($milestones, 'journey', []);
@endphp

<div class="rounded-2xl bg-white dark:bg-zinc-900 p-6 shadow-sm border border-zinc-200 dark:border-zinc-800 space-y-6">
    {{-- ─── HEADER & HAFAALAN PERTAMA BANNER ─── --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800 pb-4">
        <div>
            <h3 class="text-lg font-extrabold text-zinc-900 dark:text-white flex items-center gap-2">
                <span>🗺️ Peta Perjalanan Hafalan Santri (Milestone 4 Term x 3 Kelas)</span>
            </h3>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                Rekam jejak hafalan dari setoran awal pertama hingga progres per-Term di Kelas 10, 11, dan 12.
            </p>
        </div>

        @if ($firstRecord)
            <div class="inline-flex items-center gap-2.5 px-4 py-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/80 text-emerald-900 dark:text-emerald-200 shadow-sm">
                <span class="text-xl">🌱</span>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Setoran Hafalan Pertama</p>
                    <p class="text-sm font-extrabold">{{ data_get($firstRecord, 'title') }}</p>
                    <p class="text-[11px] text-emerald-600 dark:text-emerald-400">Tanggal: {{ data_get($firstRecord, 'date') }}</p>
                </div>
            </div>
        @else
            <div class="px-3.5 py-1.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 text-xs text-zinc-500 font-medium">
                Belum ada record setoran pertama.
            </div>
        @endif
    </div>

    {{-- ─── GRID 3 KELAS (KELAS 10, 11, 12) ─── --}}
    <div class="space-y-6" x-data="{ activeGrade: 10 }">
        {{-- Grade Selector Tabs --}}
        <div class="flex items-center gap-2 border-b border-zinc-100 dark:border-zinc-800 pb-3 overflow-x-auto">
            @foreach ($journey as $g)
                @php
                    $gNum = data_get($g, 'grade_num');
                    $gName = data_get($g, 'grade_name');
                    $sy = data_get($g, 'school_year');
                    $isCurrentGrade = data_get($g, 'is_current_grade');
                @endphp
                <button 
                    type="button"
                    @click="activeGrade = {{ $gNum }}"
                    :class="activeGrade === {{ $gNum }} 
                        ? 'bg-emerald-600 text-white shadow-md' 
                        : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0"
                >
                    <span>🏫 {{ $gName }} ({{ $sy }})</span>
                    @if ($isCurrentGrade)
                        <span class="px-1.5 py-0.5 rounded-md text-[10px] uppercase tracking-wider bg-amber-400 text-amber-950 font-black">Kelas Saat Ini</span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- Grade ContentPanels --}}
        @foreach ($journey as $g)
            @php
                $gNum = data_get($g, 'grade_num');
                $terms = data_get($g, 'terms', []);
            @endphp
            <div x-show="activeGrade === {{ $gNum }}" x-cloak class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    @foreach ($terms as $tNum => $t)
                        @php
                            $tName = data_get($t, 'name');
                            $hasData = data_get($t, 'has_data');
                            $isCurrent = data_get($t, 'is_current');
                            $firstSetoran = data_get($t, 'first_setoran');
                            $lastSetoran = data_get($t, 'last_setoran');
                            $totalRecords = data_get($t, 'total_records', 0);
                            $totalLines = data_get($t, 'total_lines', 0);
                        @endphp
                        
                        <div class="rounded-xl border p-4 transition-all duration-200 flex flex-col justify-between {{ $isCurrent ? 'border-emerald-500 bg-emerald-50/20 dark:bg-emerald-950/10 ring-2 ring-emerald-500/20' : ($hasData ? 'border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900' : 'border-dashed border-zinc-200 dark:border-zinc-800/60 bg-zinc-50/50 dark:bg-zinc-900/40 opacity-75') }}">
                            <div>
                                <div class="flex items-center justify-between gap-2 border-b border-zinc-100 dark:border-zinc-800/80 pb-2 mb-3">
                                    <h4 class="text-xs font-black uppercase tracking-wider {{ $isCurrent ? 'text-emerald-700 dark:text-emerald-400' : 'text-zinc-800 dark:text-zinc-200' }}">
                                        {{ $tName }}
                                    </h4>
                                    @if ($isCurrent)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500 text-white">Aktif</span>
                                    @endif
                                </div>

                                @if ($hasData)
                                    <div class="space-y-3 text-xs">
                                        {{-- Awal Setoran Term --}}
                                        <div class="bg-zinc-50 dark:bg-zinc-800/60 p-2.5 rounded-lg border border-zinc-100 dark:border-zinc-800">
                                            <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider flex items-center gap-1">
                                                <span>🎯 Awal Setoran Term</span>
                                            </p>
                                            <p class="font-extrabold text-zinc-900 dark:text-white mt-0.5">
                                                {{ data_get($firstSetoran, 'full_text', '-') }}
                                            </p>
                                            <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium mt-0.5">
                                                📅 {{ data_get($firstSetoran, 'date', '-') }}
                                            </p>
                                        </div>

                                        {{-- Capaian Akhir Term --}}
                                        <div class="bg-zinc-50 dark:bg-zinc-800/60 p-2.5 rounded-lg border border-zinc-100 dark:border-zinc-800">
                                            <p class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider flex items-center gap-1">
                                                <span>🏁 Capaian Akhir Term</span>
                                            </p>
                                            <p class="font-extrabold text-zinc-900 dark:text-white mt-0.5">
                                                {{ data_get($lastSetoran, 'full_text', '-') }}
                                            </p>
                                            <p class="text-[10px] text-teal-600 dark:text-teal-400 font-medium mt-0.5">
                                                📅 {{ data_get($lastSetoran, 'date', '-') }}
                                            </p>
                                        </div>
                                    </div>
                                @else
                                    <div class="py-6 text-center text-xs text-zinc-400 dark:text-zinc-600">
                                        Belum ada setoran terdaftar pada term ini.
                                    </div>
                                @endif
                            </div>

                            @if ($hasData)
                                <div class="mt-4 pt-2 border-t border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between text-[11px] text-zinc-500 dark:text-zinc-400 font-semibold">
                                    <span>Total Setoran: {{ $totalRecords }}</span>
                                    @if ($totalLines > 0)
                                        <span class="text-emerald-600 dark:text-emerald-400">{{ $totalLines }} Baris</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
