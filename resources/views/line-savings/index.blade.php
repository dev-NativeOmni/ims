<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-zinc-900 dark:text-white leading-tight flex items-center gap-2">
                <x-heroicon-o-banknotes class="w-6 h-6 text-teal-600 dark:text-teal-400" />
                <span>Tabungan &amp; Hutang Baris</span>
            </h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Kelas 11 &amp; 12: baris ayat baru dibanding target tiap bulan dan triwulan. Kelebihan ditabung, kekurangan ditutup tabungan bila cukup; sisanya menjadi hutang.
            </p>
        </div>
    </x-slot>

    @php
        // Angka ringkas: 12 / 12,5; selisih bertanda.
        $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 1, ',', ''), '0'), ',');
        $signed = fn ($v) => ($v > 0 ? '+' : ($v < 0 ? '−' : '')).$num(abs($v));
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5">
            <!-- Filter -->
            <form method="GET" action="{{ route('line-savings.index') }}"
                  class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4 shadow-sm grid grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400">Tahun Ajaran
                    <select name="academic_year" onchange="this.form.submit()" class="mt-1 w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                        @foreach ($years as $option)
                            <option value="{{ $option }}" @selected($option === $year)>{{ $option }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400">Triwulan
                    <select name="term" onchange="this.form.submit()" class="mt-1 w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                        @foreach ([1 => 'Triwulan 1 (Jul-Sep)', 2 => 'Triwulan 2 (Okt-Des)', 3 => 'Triwulan 3 (Jan-Mar)', 4 => 'Triwulan 4 (Apr-Jun)'] as $value => $label)
                            <option value="{{ $value }}" @selected($value === $term)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400">Kelas
                    <select name="class_room_id" onchange="this.form.submit()" class="mt-1 w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                        <option value="">Semua kelas</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" @selected($selectedClass?->id === $class->id)>{{ $class->name }} ({{ $class->program?->name ?? '-' }})</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-xs font-semibold text-zinc-600 dark:text-zinc-400">Halaqoh (Musyrif)
                    <select name="teacher_id" onchange="this.form.submit()" class="mt-1 w-full rounded-xl border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm">
                        <option value="">Semua halaqoh</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected($teacherId === $teacher->id)>{{ $teacher->user?->name ?? 'Guru #'.$teacher->id }}</option>
                        @endforeach
                    </select>
                </label>
                <a href="{{ route('line-savings.index') }}" class="col-span-2 lg:col-span-1 inline-flex justify-center items-center rounded-xl border border-zinc-300 dark:border-zinc-700 px-4 py-2.5 text-sm font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800">Reset</a>
            </form>

            <!-- Ringkasan -->
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
                @foreach ([
                    ['Murid', $summary['students'], 'text-zinc-900 dark:text-white'],
                    ['Berhutang', $summary['in_debt'].' murid', 'text-rose-600 dark:text-rose-400'],
                    ['Total hutang', $num($summary['debt']).' baris', 'text-rose-600 dark:text-rose-400'],
                    ['Ditolong tabungan', $summary['helped'].' murid', 'text-amber-600 dark:text-amber-400'],
                    ['Total saldo tabungan', $num($summary['savings']).' baris', 'text-emerald-600 dark:text-emerald-400'],
                ] as [$label, $value, $color])
                    <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4 shadow-sm">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
                        <p class="mt-1 text-xl font-black {{ $color }}">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ $termLabel }} {{ $year }}. Baris = ayat baru saja (ulangan tidak dihitung), sama dengan rapor.
                <span class="text-emerald-600 dark:text-emerald-400 font-semibold">+ lebih</span> /
                <span class="text-rose-600 dark:text-rose-400 font-semibold">− kurang</span> dari target bulan itu.
                Tabungan dipakai hanya bila cukup menutup seluruh kekurangan triwulan; saldo maksimal satu target triwulan dan terbawa sampai lulus.
            </p>

            <!-- Tabel -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-zinc-200 dark:border-zinc-800 shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/60 text-[11px] font-black uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        <tr>
                            <th rowspan="2" class="sticky left-0 z-10 bg-zinc-50 dark:bg-zinc-800 px-4 py-3 text-left min-w-[200px]">Murid</th>
                            @foreach ($months as $month)
                                <th class="px-3 py-2 text-center border-l border-zinc-200 dark:border-zinc-700">{{ $month['label'] }}</th>
                            @endforeach
                            <th colspan="6" class="px-3 py-2 text-center border-l border-zinc-200 dark:border-zinc-700">Triwulan</th>
                        </tr>
                        <tr class="text-[10px]">
                            @foreach ($months as $month)
                                <th class="px-3 py-2 text-center border-l border-zinc-200 dark:border-zinc-700 font-semibold normal-case">Capaian / Target</th>
                            @endforeach
                            <th class="px-3 py-2 text-center border-l border-zinc-200 dark:border-zinc-700 font-semibold normal-case">Capaian / Target</th>
                            <th class="px-3 py-2 text-center font-semibold normal-case">Saldo awal</th>
                            <th class="px-3 py-2 text-center font-semibold normal-case">Dipakai</th>
                            <th class="px-3 py-2 text-center font-semibold normal-case">Ditabung</th>
                            <th class="px-3 py-2 text-center font-semibold normal-case">Hutang</th>
                            <th class="px-3 py-2 text-center font-semibold normal-case">Saldo akhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($rows as $row)
                            <tr>
                                <td class="sticky left-0 z-10 bg-white dark:bg-zinc-900 px-4 py-3">
                                    <p class="font-bold text-zinc-900 dark:text-white">{{ $row['name'] }}</p>
                                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ $row['class'] }} · {{ $row['program'] }} · {{ $row['level'] }}</p>
                                    <p class="text-[11px] text-zinc-400 dark:text-zinc-500">Musyrif: {{ $row['musyrif'] }}</p>
                                </td>
                                @foreach ($row['months'] as $i => $month)
                                    <td class="px-3 py-3 text-center border-l border-zinc-100 dark:border-zinc-800 whitespace-nowrap">
                                        @if ($months[$i]['future'] ?? false)
                                            <span class="text-zinc-300 dark:text-zinc-600">—</span>
                                        @else
                                            <p class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $num($month['achieved']) }} <span class="text-zinc-400">/ {{ $num($month['target']) }}</span></p>
                                            <p class="text-[11px] font-bold {{ $month['diff'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : ($month['diff'] < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-zinc-400') }}">{{ $month['diff'] == 0 ? 'pas' : $signed($month['diff']) }}</p>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-3 py-3 text-center border-l border-zinc-200 dark:border-zinc-700 whitespace-nowrap">
                                    <p class="font-bold text-zinc-900 dark:text-white">{{ $num($row['achieved']) }} <span class="text-zinc-400 font-semibold">/ {{ $num($row['target']) }}</span></p>
                                    <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase {{ $row['reached'] ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-400' }}">{{ $row['reached'] ? 'Tuntas' : 'Tidak Tuntas' }}</span>
                                </td>
                                <td class="px-3 py-3 text-center text-zinc-600 dark:text-zinc-300">{{ $row['balance_before'] > 0 ? $num($row['balance_before']) : '-' }}</td>
                                <td class="px-3 py-3 text-center font-semibold text-amber-600 dark:text-amber-400">{{ $row['used'] > 0 ? '−'.$num($row['used']) : '-' }}</td>
                                <td class="px-3 py-3 text-center font-semibold text-emerald-600 dark:text-emerald-400">{{ $row['deposit'] > 0 ? '+'.$num($row['deposit']) : '-' }}</td>
                                <td class="px-3 py-3 text-center font-bold text-rose-600 dark:text-rose-400">{{ $row['debt'] > 0 ? $num($row['debt']) : '-' }}</td>
                                <td class="px-3 py-3 text-center font-black {{ $row['balance_after'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-400' }}">{{ $num($row['balance_after']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 7 + count($months) }}" class="px-4 py-12 text-center text-zinc-400">Tidak ada murid Kelas 11/12 berbaris untuk filter ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
