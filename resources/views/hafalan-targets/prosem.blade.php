<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-zinc-900 dark:text-white leading-tight flex items-center gap-2">
                <x-heroicon-o-table-cells class="w-6 h-6 text-teal-600 dark:text-teal-400" />
                <span>Prosem Ummi</span>
            </h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Program Semester metode Ummi Kelas 10: materi tiap baris dibagi rata ke tatap mukanya (target per pertemuan),
                dan posisi akhir tiap bulan otomatis menjadi Target Ummi bulanan (guru tetap bisa mengubah di tab Target Ummi).
            </p>
        </div>
    </x-slot>

    @php
        $surahName = fn ($id) => $surahs->firstWhere('id', $id)?->name_latin;
        $initialRows = $rows->map(fn ($r) => [
            'jilid' => $r->jilid ?? '',
            'peraga_start' => $r->peraga_start, 'peraga_end' => $r->peraga_end,
            'buku_start' => $r->buku_start, 'buku_end' => $r->buku_end,
            'hafalan_start_surah_id' => $r->hafalan_start_surah_id, 'hafalan_end_surah_id' => $r->hafalan_end_surah_id,
            'drill_start_surah_id' => $r->drill_start_surah_id, 'drill_end_surah_id' => $r->drill_end_surah_id,
            'weeks' => collect(array_keys($months))->mapWithKeys(fn ($m) => [$m => collect(range(1, 5))->mapWithKeys(fn ($w) => [$w => $r->weeks[(string) $m][(string) $w] ?? ''])->all()])->all(),
            'notes' => $r->notes ?? '',
        ])->values();
        $emptyWeeks = collect(array_keys($months))->mapWithKeys(fn ($m) => [$m => collect(range(1, 5))->mapWithKeys(fn ($w) => [$w => ''])->all()])->all();
        $input = 'w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs px-1.5 py-1';
        $pageInput = 'w-14 shrink-0 rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-xs text-center px-1 py-1';
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('hafalan-targets.partials.period-tabs')

            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-500/10 dark:border-emerald-500/30 px-4 py-3 text-sm font-semibold text-emerald-800 dark:text-emerald-300">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 dark:bg-rose-500/10 px-4 py-3 text-sm text-rose-800 dark:text-rose-300">Prosem belum disimpan: periksa isian (Jilid, halaman 1-200, jumlah TM 0-10).</div>
            @endif

            <form method="GET" action="{{ route('prosem-ummi.index') }}" class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4 shadow-sm flex flex-wrap gap-3 items-end">
                <label class="text-xs font-semibold text-zinc-600 dark:text-zinc-400">Tahun Ajaran
                    <select name="academic_year" onchange="this.form.submit()" class="mt-1 block rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                        @foreach ($years as $option)
                            <option value="{{ $option }}" @selected($option === $year)>{{ $option }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs font-semibold text-zinc-600 dark:text-zinc-400">Semester
                    <select name="semester" onchange="this.form.submit()" class="mt-1 block rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                        <option value="1" @selected($semester === 1)>Semester 1 (Juli–Desember)</option>
                        <option value="2" @selected($semester === 2)>Semester 2 (Januari–Juni)</option>
                    </select>
                </label>
                <label class="text-xs font-semibold text-zinc-600 dark:text-zinc-400">Kelas
                    <select name="class_room_id" onchange="this.form.teacher_id.value = ''; this.form.submit()" class="mt-1 block rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                        <option value="">Umum (semua kelas 10)</option>
                        @foreach ($classes as $option)
                            <option value="{{ $option->id }}" @selected($class?->id === $option->id)>{{ $option->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-xs font-semibold text-zinc-600 dark:text-zinc-400">Halaqoh (Musyrif)
                    <select name="teacher_id" onchange="this.form.submit()" class="mt-1 block rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm" @disabled(! $class)>
                        <option value="">Semua halaqoh{{ $class ? ' di '.$class->name : '' }}</option>
                        @foreach ($teachers as $option)
                            <option value="{{ $option->id }}" @selected($teacher?->id === $option->id)>{{ $option->user?->name ?? 'Guru #'.$option->id }}</option>
                        @endforeach
                    </select>
                </label>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ count($plan) }} tatap muka · {{ $rows->count() }} baris</p>
            </form>

            {{-- Prosem yang sudah ada. Pemakaian: kelas+halaqoh, lalu kelas (semua halaqoh), lalu umum. --}}
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="font-semibold text-zinc-500 dark:text-zinc-400">Prosem terisi:</span>
                @forelse ($existing as $scope)
                    @php $active = $scope['class_room_id'] === $class?->id && $scope['teacher_id'] === $teacher?->id; @endphp
                    <a href="{{ route('prosem-ummi.index', array_filter(['academic_year' => $year, 'semester' => $semester, 'class_room_id' => $scope['class_room_id'], 'teacher_id' => $scope['teacher_id']])) }}"
                       class="rounded-full px-3 py-1 font-semibold border {{ $active ? 'bg-teal-600 border-teal-600 text-white' : 'border-zinc-300 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:border-teal-500' }}">{{ $scope['label'] }}</a>
                @empty
                    <span class="text-zinc-400">belum ada</span>
                @endforelse
            </div>
            <p class="-mt-3 text-[11px] text-zinc-500 dark:text-zinc-400">Murid memakai Prosem halaqohnya; bila belum ada, Prosem kelasnya (semua halaqoh), lalu Prosem umum. Murid tanpa Prosem tidak diberi target otomatis.</p>

            {{-- 1. Tabel Prosem --}}
            <form method="POST" action="{{ route('prosem-ummi.update', array_filter(['academic_year' => $year, 'semester' => $semester, 'class_room_id' => $class?->id, 'teacher_id' => $teacher?->id])) }}"
                  x-data="{ rows: @js($initialRows), empty: @js(['jilid' => '', 'peraga_start' => null, 'peraga_end' => null, 'buku_start' => null, 'buku_end' => null, 'hafalan_start_surah_id' => null, 'hafalan_end_surah_id' => null, 'drill_start_surah_id' => null, 'drill_end_surah_id' => null, 'weeks' => $emptyWeeks, 'notes' => '']),
                            tm(row) { return Object.values(row.weeks).flatMap(w => Object.values(w)).reduce((a, v) => a + (parseInt(v) || 0), 0); } }"
                  class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                @csrf
                <div class="flex items-center justify-between gap-3 p-4 border-b border-zinc-200 dark:border-zinc-800">
                    <div>
                        <h3 class="font-bold text-zinc-900 dark:text-white">Tabel Prosem · {{ $class ? $class->name.' · '.($teacher?->user?->name ?? 'semua halaqoh') : 'Umum' }}</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Isi kolom pekan dengan jumlah tatap muka (TM) baris itu di pekan tersebut. Baris tanpa materi = TM pengulangan/evaluasi.</p>
                    </div>
                    @if ($canEdit)
                        <div class="flex gap-2 shrink-0">
                            <button type="button" @click="rows.push(JSON.parse(JSON.stringify(empty)))" class="rounded-xl border border-zinc-300 dark:border-zinc-700 px-3 py-2 text-xs font-bold text-zinc-700 dark:text-zinc-200">+ Baris</button>
                            <button type="submit" class="rounded-xl bg-teal-600 hover:bg-teal-700 px-4 py-2 text-xs font-bold text-white">Simpan Prosem</button>
                        </div>
                    @endif
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-xs">
                        <thead class="bg-zinc-50 dark:bg-zinc-800/60 text-[10px] font-black uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <tr>
                                <th rowspan="2" class="px-2 py-2">No</th>
                                <th rowspan="2" class="px-2 py-2 min-w-[90px]">Jilid</th>
                                <th rowspan="2" class="px-2 py-2 min-w-[140px]">Hlm Peraga</th>
                                <th rowspan="2" class="px-2 py-2 min-w-[140px]">Hlm Buku</th>
                                <th rowspan="2" class="px-2 py-2 min-w-[220px]">Hafalan (ziyadah)</th>
                                <th rowspan="2" class="px-2 py-2 min-w-[220px]">Drill (muraja'ah)</th>
                                <th rowspan="2" class="px-2 py-2">TM</th>
                                @foreach ($months as $label)
                                    <th colspan="5" class="px-2 py-1 border-l border-zinc-200 dark:border-zinc-700">{{ $label }}</th>
                                @endforeach
                                @if ($canEdit)<th rowspan="2"></th>@endif
                            </tr>
                            <tr>
                                @foreach ($months as $label)
                                    @foreach (range(1, 5) as $week)
                                        <th class="px-1 py-1 font-semibold {{ $week === 1 ? 'border-l border-zinc-200 dark:border-zinc-700' : '' }}">{{ $week }}</th>
                                    @endforeach
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            <template x-for="(row, i) in rows" :key="i">
                                <tr class="align-top">
                                    <td class="px-2 py-2 font-bold text-zinc-500">
                                        <span x-text="i + 1"></span>
                                        <input type="hidden" :name="`rows[${i}][notes]`" :value="row.notes">
                                        <span class="block text-[9px] font-semibold text-amber-600 dark:text-amber-400" x-show="row.notes" x-text="row.notes"></span>
                                    </td>
                                    <td class="px-1 py-1.5">
                                        <select :name="`rows[${i}][jilid]`" x-model="row.jilid" class="{{ $input }}" @disabled(! $canEdit)>
                                            <option value="">—</option>
                                            @foreach ($books as $book)
                                                <option value="{{ $book }}">{{ $book }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    @foreach (['peraga', 'buku'] as $kind)
                                        <td class="px-1 py-1.5">
                                            <div class="flex items-center gap-1">
                                                <input type="number" min="1" :name="`rows[${i}][{{ $kind }}_start]`" x-model="row.{{ $kind }}_start" class="{{ $pageInput }}" @disabled(! $canEdit)>
                                                <span class="text-zinc-400">–</span>
                                                <input type="number" min="1" :name="`rows[${i}][{{ $kind }}_end]`" x-model="row.{{ $kind }}_end" class="{{ $pageInput }}" @disabled(! $canEdit)>
                                            </div>
                                        </td>
                                    @endforeach
                                    @foreach (['hafalan', 'drill'] as $kind)
                                        <td class="px-1 py-1.5">
                                            <div class="flex items-center gap-1">
                                                @foreach (['start', 'end'] as $edge)
                                                    <select :name="`rows[${i}][{{ $kind }}_{{ $edge }}_surah_id]`" x-model.number="row.{{ $kind }}_{{ $edge }}_surah_id" class="{{ $input }}" @disabled(! $canEdit)>
                                                        <option value="">—</option>
                                                        @foreach ($surahs as $surah)
                                                            <option value="{{ $surah->id }}">{{ $surah->number }}. {{ $surah->name_latin }}</option>
                                                        @endforeach
                                                    </select>
                                                @endforeach
                                            </div>
                                        </td>
                                    @endforeach
                                    <td class="px-2 py-2 text-center font-black text-teal-700 dark:text-teal-400" x-text="tm(row)"></td>
                                    @foreach ($months as $month => $label)
                                        @foreach (range(1, 5) as $week)
                                            <td class="px-0.5 py-1.5 {{ $week === 1 ? 'border-l border-zinc-200 dark:border-zinc-700' : '' }}">
                                                <input type="number" min="0" max="10" :name="`rows[${i}][weeks][{{ $month }}][{{ $week }}]`" x-model="row.weeks[{{ $month }}][{{ $week }}]"
                                                       class="w-8 rounded border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-[11px] text-center px-0 py-1" @disabled(! $canEdit)>
                                            </td>
                                        @endforeach
                                    @endforeach
                                    @if ($canEdit)
                                        <td class="px-1 py-1.5">
                                            <button type="button" @click="rows.splice(i, 1)" class="text-rose-600 text-xs font-bold px-1" title="Hapus baris">×</button>
                                        </td>
                                    @endif
                                </tr>
                            </template>
                            <tr x-show="rows.length === 0">
                                <td colspan="{{ 8 + count($months) * 5 }}" class="px-4 py-8 text-center text-zinc-400">
                                    Prosem semester ini belum diisi.{{ $canEdit ? ' Tambah baris, atau impor dari Google Sheet dengan perintah tad:prosem-import.' : '' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </form>

            <div class="grid lg:grid-cols-3 gap-6">
                {{-- 2. Target per pertemuan --}}
                <div class="lg:col-span-2 bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                    <div class="p-4 border-b border-zinc-200 dark:border-zinc-800">
                        <h3 class="font-bold text-zinc-900 dark:text-white">Target per Pertemuan</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Urutan tatap muka semester. Ditampilkan juga di menu Alur Pembelajaran untuk pertemuan hari itu.</p>
                    </div>
                    <div class="overflow-x-auto max-h-[560px]">
                        <table class="min-w-full text-xs">
                            <thead class="sticky top-0 bg-zinc-50 dark:bg-zinc-800 text-[10px] font-black uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                <tr>
                                    <th class="px-3 py-2 text-left">TM</th>
                                    <th class="px-3 py-2 text-left">Pekan</th>
                                    <th class="px-3 py-2 text-left">Jilid · Buku · Peraga</th>
                                    <th class="px-3 py-2 text-left">Ziyadah</th>
                                    <th class="px-3 py-2 text-left">Muraja'ah</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800 text-zinc-700 dark:text-zinc-300">
                                @forelse ($plan as $entry)
                                    <tr>
                                        <td class="px-3 py-1.5 font-black text-zinc-900 dark:text-white">{{ $entry['no'] }}</td>
                                        <td class="px-3 py-1.5 whitespace-nowrap">{{ \App\Http\Controllers\UmmiProsemController::MONTH_LABELS[$entry['month']] }} pekan {{ $entry['week'] }}</td>
                                        <td class="px-3 py-1.5">
                                            @if ($entry['jilid'])
                                                <span class="font-semibold">{{ $entry['jilid'] }}</span> · buku hlm {{ \App\Services\UmmiProsemService::pagesLabel($entry['buku']) }} · {{ \App\Services\UmmiProsemService::peragaLabel($entry['jilid'], $entry['peraga']) }}
                                            @else
                                                <span class="text-amber-600 dark:text-amber-400 font-semibold">{{ $entry['row']->notes ?: 'Pengulangan / evaluasi' }}</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-1.5">{{ \App\Services\UmmiProsemService::rangeLabel($entry['hafalan']) }}</td>
                                        <td class="px-3 py-1.5">{{ \App\Services\UmmiProsemService::rangeLabel($entry['drill']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-400">Belum ada tatap muka terjadwal.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- 3. Target bulanan otomatis --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm">
                    <div class="p-4 border-b border-zinc-200 dark:border-zinc-800">
                        <h3 class="font-bold text-zinc-900 dark:text-white">Target Bulanan Otomatis</h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Posisi Prosem di akhir bulan; terisi ke Target Ummi murid Kelas 10. Target yang sudah diubah guru tidak ditimpa.</p>
                    </div>
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-800 text-sm">
                        @foreach ($monthTargets as $month => $target)
                            <li class="px-4 py-2.5 flex items-start justify-between gap-3">
                                <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ $months[$month] }}</span>
                                <span class="text-right text-zinc-900 dark:text-white">
                                    @if ($target)
                                        {{ $target['ummi_jilid'] }} hlm {{ $target['halaman_buku'] }}
                                        <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $target['surah_id'] ? 'Hafalan s.d. '.$surahName($target['surah_id']) : 'tanpa target hafalan' }}</span>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
