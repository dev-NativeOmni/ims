<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-bold text-2xl text-gray-900 dark:text-white leading-tight flex items-center gap-2">
                <x-heroicon-o-flag class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                <span>Target Triwulan</span>
            </h2>
            <p class="text-sm text-gray-600 dark:text-zinc-400">
                Dihitung otomatis: setoran pertama triwulan + (pertemuan aktif × baris per level), mengikuti urutan hafalan
                Juz 30 → 29 → 28 … (tiap juz dari awal; Juz 30 fleksibel, dihitung dari surah yang belum disetor).
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('hafalan-targets.partials.period-tabs')

            {{-- Filter --}}
            <form method="GET" action="{{ route('hafalan-targets.term') }}" class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-4 shadow-sm flex flex-col sm:flex-row gap-3">
                <select name="period" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm font-semibold">
                    @foreach ($periods as $value => $label)
                        <option value="{{ $value }}" @selected($value === $period)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="class_room_id" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm font-semibold">
                    @forelse ($classRooms as $class)
                        <option value="{{ $class->id }}" @selected($selectedClass?->id === $class->id)>{{ $class->name }} ({{ $class->program?->name }})</option>
                    @empty
                        <option value="">Tidak ada kelas 11/12</option>
                    @endforelse
                </select>
            </form>

            {{-- Ringkasan --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                @foreach ([
                    ['Pertemuan Aktif', $summary['meetings'].' pertemuan', 'text-gray-900 dark:text-white'],
                    ['Rata-rata Progres', $summary['avg_progress'].'%', 'text-indigo-600 dark:text-indigo-400'],
                    ['Target Tercapai', $summary['reached'].' / '.$summary['students'].' murid', 'text-emerald-600 dark:text-emerald-400'],
                    ['Belum Ada Setoran', $summary['no_start'].' murid', 'text-amber-600 dark:text-amber-400'],
                ] as [$label, $value, $color])
                    <div class="rounded-xl bg-white dark:bg-zinc-900 p-4 shadow-sm border border-gray-100 dark:border-zinc-800">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ $label }}</p>
                        <p class="mt-1 text-xl font-extrabold {{ $color }}">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Tabel --}}
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-zinc-800/60 text-[11px] font-black uppercase tracking-wider text-gray-500 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3 text-left">Murid</th>
                            <th class="px-4 py-3 text-left">Titik Awal</th>
                            <th class="px-4 py-3 text-left">Target per Bulan</th>
                            <th class="px-4 py-3 text-left">Target Triwulan</th>
                            <th class="px-4 py-3 text-left">Capaian</th>
                            <th class="px-4 py-3 text-left">Progres</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                        @forelse ($rows as $row)
                            @php
                                $plan = $row['plan'];
                                $pos = fn ($p) => $p ? $p['surah']->name_latin.' : '.$p['ayah_end'] : '–';
                            @endphp
                            <tr class="align-top">
                                <td class="px-4 py-3">
                                    <p class="font-bold text-gray-900 dark:text-white">{{ $row['student']->name }}</p>
                                    <p class="text-[11px] text-gray-500">{{ ucfirst($row['student']->tahfizh_level ?? 'reguler') }} · {{ $plan['level_baris'] }} baris/pertemuan</p>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($plan['start'])
                                        <p class="font-semibold text-gray-800 dark:text-zinc-200">{{ $plan['start']['surah']->name_latin }} : {{ $plan['start']['ayah'] }}</p>
                                        <p class="text-[11px] text-gray-500">{{ $plan['start']['date'] ? \Carbon\Carbon::parse($plan['start']['date'])->format('d/m/Y') : '' }}</p>
                                    @else
                                        <span class="text-xs text-amber-600">Belum ada setoran triwulan ini</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <ul class="space-y-0.5 text-xs">
                                        @foreach ($plan['months'] as $month)
                                            <li class="whitespace-nowrap">
                                                <span class="text-gray-500">{{ $month['label'] }}:</span>
                                                <span class="font-semibold text-gray-800 dark:text-zinc-200">{{ $pos($month['position']) }}</span>
                                                <span class="text-gray-400">({{ $month['meetings'] }} TM · {{ $month['cumulative_lines'] }} baris)</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <p class="font-bold text-indigo-700 dark:text-indigo-400">{{ $pos($plan['target']) }}</p>
                                    <p class="text-[11px] text-gray-500">{{ $plan['target_lines'] }} baris · {{ $plan['term_meetings'] }} pertemuan</p>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($plan['capaian'])
                                        <p class="font-semibold text-gray-800 dark:text-zinc-200">{{ $plan['capaian']['surah']->name_latin }} : {{ $plan['capaian']['ayah'] }}</p>
                                        <p class="text-[11px] text-gray-500">{{ $plan['achieved_lines'] }} baris</p>
                                    @else
                                        <span class="text-xs text-gray-400">–</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 min-w-[150px]">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 rounded-full bg-gray-100 dark:bg-zinc-800 overflow-hidden">
                                            <div class="h-full rounded-full {{ $plan['reached'] ? 'bg-emerald-500' : 'bg-indigo-500' }}" style="width: {{ $plan['progress'] }}%"></div>
                                        </div>
                                        <span class="text-xs font-bold text-gray-700 dark:text-zinc-300">{{ $plan['progress'] }}%</span>
                                    </div>
                                    @if ($plan['reached'])
                                        <span class="mt-1 inline-block text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Tercapai</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                                    Tidak ada murid kelas 11/12 yang dapat ditampilkan. Target Kelas 10 (Ummi) dibuat oleh guru di Target Bulanan.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
