<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-bold text-xl sm:text-2xl text-zinc-900 dark:text-zinc-100 leading-tight flex items-center gap-2">
                    <span>🛡️ Laporan & Rekapitulasi Ketahanan Sekolah (Tanse)</span>
                </h2>
                <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                    Pemantauan tren dan peringkat pelanggaran kedisiplinan murid (Tata Tertib, Keterlambatan, dan Atribut/Seragam).
                </p>
            </div>

            {{-- Advanced Filter Form --}}
            <form method="GET" action="{{ route('student-points.chart') }}" class="flex flex-wrap items-center gap-2">
                <select name="time_frame" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs font-semibold py-2 px-3 focus:ring-indigo-500 dark:text-white">
                    <option value="month" @selected($timeFrame === 'month')>Bulan Terpilih</option>
                    <option value="all" @selected($timeFrame === 'all')>Semua Waktu (Akumulasi)</option>
                </select>

                <select name="violation_type" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs font-semibold py-2 px-3 focus:ring-indigo-500 dark:text-white">
                    <option value="all" @selected($violationType === 'all')>Semua Pelanggaran</option>
                    <option value="lateness" @selected($violationType === 'lateness')>⏰ Keterlambatan</option>
                    <option value="attribute" @selected($violationType === 'attribute')>👔 Atribut/Seragam</option>
                    <option value="violation" @selected($violationType === 'violation')>📜 Tata Tertib</option>
                </select>

                <select name="sort_by" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs font-semibold py-2 px-3 focus:ring-indigo-500 dark:text-white">
                    <option value="count" @selected($sortBy === 'count')>Urutkan: Kasus Terbanyak</option>
                    <option value="points" @selected($sortBy === 'points')>Urutkan: Poin Terbanyak</option>
                </select>

                <select name="class_room_id" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs font-semibold py-2 px-3 focus:ring-indigo-500 dark:text-white">
                    <option value="">Semua Kelas</option>
                    @foreach ($classRooms as $cRoom)
                        <option value="{{ $cRoom->id }}" @selected((string) $classRoomId === (string) $cRoom->id)>
                            {{ $cRoom->name }}
                        </option>
                    @endforeach
                </select>

                @if ($timeFrame === 'month')
                    <select name="month" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs font-semibold py-2 px-3 focus:ring-indigo-500 dark:text-white">
                        @foreach ($monthsList as $mNum => $mName)
                            <option value="{{ $mNum }}" @selected($mNum === $month)>{{ $mName }}</option>
                        @endforeach
                    </select>

                    <select name="year" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs font-semibold py-2 px-3 focus:ring-indigo-500 dark:text-white">
                        @for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                            <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                        @endfor
                    </select>
                @endif

                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition cursor-pointer">
                    🔍 Filter
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8" x-data="{ viewMode: 'leaderboard' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- View Mode Tabs --}}
            <div class="flex items-center gap-2 border-b border-gray-200 dark:border-zinc-800 pb-3">
                <button
                    type="button"
                    @click="viewMode = 'leaderboard'"
                    :class="viewMode === 'leaderboard' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-1.5"
                >
                    <span>🏆 Peringkat Murid Terbanyak (<span x-text="{{ $studentLeaderboard->count() }}"></span>)</span>
                </button>

                <button
                    type="button"
                    @click="viewMode = 'class_report'"
                    :class="viewMode === 'class_report' ? 'bg-indigo-600 text-white shadow-md' : 'bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-700'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer flex items-center gap-1.5"
                >
                    <span>🏫 Rekapitulasi per Kelas</span>
                </button>
            </div>

            {{-- Summary Metric Cards --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Total Pelanggaran --}}
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-xl">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Total Pelanggaran</p>
                            <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ $monthViolationsCount }} <span class="text-xs font-normal text-gray-400">Kasus</span></h3>
                            <p class="text-[11px] text-gray-400 mt-0.5">{{ $timeFrame === 'all' ? 'Semua Waktu' : $monthsList[$month] . ' ' . $year }}</p>
                        </div>
                    </div>
                </div>

                {{-- Total Poin Pelanggaran --}}
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-xl">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Total Akumulasi Poin</p>
                            <h3 class="text-2xl font-black text-gray-900 dark:text-white mt-0.5">{{ $monthViolationsPoints }} <span class="text-xs font-normal text-gray-400">Poin</span></h3>
                            <p class="text-[11px] text-gray-400 mt-0.5">Dampak Kedisiplinan</p>
                        </div>
                    </div>
                </div>

                {{-- Murid Terbanyak Pelanggaran --}}
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 rounded-xl">
                            <span class="text-xl">🏆</span>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Murid Terbanyak</p>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white mt-0.5 truncate max-w-[150px]">
                                {{ $studentLeaderboard->first()['student']->name ?? '-' }}
                            </h3>
                            <p class="text-[11px] text-rose-500 font-semibold mt-0.5">
                                {{ $studentLeaderboard->first()['violation_count'] ?? 0 }} Kasus ({{ $studentLeaderboard->first()['violation_points'] ?? 0 }} Poin)
                            </p>
                        </div>
                    </div>
                </div>

                {{-- Rincian Tipe Pelanggaran --}}
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
                    <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-2">Sebaran Tipe Pelanggaran</p>
                    <div class="grid grid-cols-3 gap-1.5">
                        <div class="p-2 bg-amber-50/80 dark:bg-amber-950/40 border border-amber-200/60 dark:border-amber-900/30 rounded-xl text-center flex flex-col items-center justify-center">
                            <span class="text-[10px] font-bold text-amber-700 dark:text-amber-300 block truncate max-w-full" title="Keterlambatan">⏰ Telat</span>
                            <span class="text-base font-black text-amber-900 dark:text-amber-100 mt-0.5">{{ $typeBreakdown['lateness'] }}</span>
                        </div>
                        <div class="p-2 bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200/60 dark:border-blue-900/30 rounded-xl text-center flex flex-col items-center justify-center">
                            <span class="text-[10px] font-bold text-blue-700 dark:text-blue-300 block truncate max-w-full" title="Atribut">👔 Atribut</span>
                            <span class="text-base font-black text-blue-900 dark:text-blue-100 mt-0.5">{{ $typeBreakdown['attribute'] }}</span>
                        </div>
                        <div class="p-2 bg-rose-50/80 dark:bg-rose-950/40 border border-rose-200/60 dark:border-rose-900/30 rounded-xl text-center flex flex-col items-center justify-center">
                            <span class="text-[10px] font-bold text-rose-700 dark:text-rose-300 block truncate max-w-full" title="Tata Tertib">📜 Tatib</span>
                            <span class="text-base font-black text-rose-900 dark:text-rose-100 mt-0.5">{{ $typeBreakdown['violation'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- VIEW 1: REKAP & PERINGKAT MURID TERBANYAK (LEADERBOARD) -->
            <div x-show="viewMode === 'leaderboard'" class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-6 shadow-sm space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b pb-4 dark:border-zinc-800">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>🏆 Rekapitulasi Peringkat Murid dengan Pelanggaran Terbanyak</span>
                        </h3>
                        <p class="text-xs text-gray-500">
                            Diurutkan berdasarkan {{ $sortBy === 'points' ? 'Total Poin Terbanyak' : 'Jumlah Kasus Terbanyak' }} ({{ $timeFrame === 'all' ? 'Akumulasi Semua Waktu' : $monthsList[$month] . ' ' . $year }}).
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800">
                        <thead>
                            <tr class="bg-gray-50/80 dark:bg-zinc-800/50 text-left text-[11px] font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                                <th class="px-4 py-3 text-center w-16">Peringkat</th>
                                <th class="px-4 py-3">Nama Murid / NIS</th>
                                <th class="px-4 py-3">Kelas / Halaqah</th>
                                <th class="px-4 py-3 text-center">Total Kasus</th>
                                <th class="px-4 py-3 text-center">Total Poin</th>
                                <th class="px-4 py-3">Rincian Pelanggaran</th>
                                <th class="px-4 py-3">Catatan / Sanksi Terakhir</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-zinc-800 text-xs">
                            @forelse ($studentLeaderboard as $rank => $item)
                                @php
                                    $st = $item['student'];
                                    $rankNum = $rank + 1;
                                    $rankBadgeClass = match(true) {
                                        $rankNum === 1 => 'bg-rose-600 text-white font-black shadow-md scale-110',
                                        $rankNum === 2 => 'bg-amber-500 text-white font-bold',
                                        $rankNum === 3 => 'bg-orange-500 text-white font-bold',
                                        default => 'bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 font-bold'
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-zinc-800/40 transition">
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-xs {{ $rankBadgeClass }}">
                                            #{{ $rankNum }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-bold text-gray-900 dark:text-white text-sm">
                                            {{ $st->name }}
                                        </div>
                                        <div class="text-[10px] text-gray-400 font-mono">
                                            NIS: {{ $st->student_number ?? '-' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap font-semibold text-gray-700 dark:text-zinc-300">
                                        {{ $st->classRoom?->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300">
                                            {{ $item['violation_count'] }} Kasus
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-black bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                            {{ $item['violation_points'] }} Poin
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center gap-1.5">
                                            @if ($item['lateness_count'] > 0)
                                                <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold">⏰ Telat: {{ $item['lateness_count'] }}</span>
                                            @endif
                                            @if ($item['attribute_count'] > 0)
                                                <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold">👔 Atribut: {{ $item['attribute_count'] }}</span>
                                            @endif
                                            @if ($item['tatib_count'] > 0)
                                                <span class="px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-bold">📜 Tatib: {{ $item['tatib_count'] }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if (!empty($item['recent_sanctions']))
                                            <span class="text-xs font-semibold text-rose-600 dark:text-rose-400 block truncate max-w-[200px]" title="{{ implode(', ', $item['recent_sanctions']) }}">
                                                ⚠️ {{ implode(', ', $item['recent_sanctions']) }}
                                            </span>
                                        @else
                                            <span class="text-xs text-gray-400 italic">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right font-medium">
                                        <a href="{{ route('student-points.index', ['search' => $st->name]) }}" class="inline-flex items-center px-2.5 py-1 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 rounded-lg text-[11px] font-bold hover:bg-indigo-100 transition">
                                            🔍 Detail Log
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center text-gray-400 dark:text-zinc-500">
                                        Belum ada data pelanggaran murid pada filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- VIEW 2: REKAPITULASI PER KELAS -->
            <div x-show="viewMode === 'class_report'" class="space-y-6">

                {{-- 12-Month Historical Trend Chart --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-6 shadow-sm space-y-6">
                    <div class="flex items-center justify-between border-b pb-4 dark:border-zinc-800">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Grafik Tren Perkembangan Pelanggaran Sekolah (Januari – Desember {{ $year }})</h3>
                            <p class="text-xs text-gray-500">Jumlah kasus pelanggaran kedisiplinan murid di seluruh sekolah dari bulan ke bulan.</p>
                        </div>
                    </div>

                    @php
                        $maxCount = max(1, collect($monthlyTrends)->max('count'));
                    @endphp
                    <div class="grid grid-cols-12 gap-2 h-44 items-end pt-6 pb-2 px-2 border-b border-gray-100 dark:border-zinc-800">
                        @foreach ($monthlyTrends as $mNum => $tData)
                            @php
                                $tCount = $tData['count'];
                                $isSel = ($mNum === $month);
                                $heightPct = round(($tCount / $maxCount) * 100);
                                $barBg = $isSel ? 'bg-rose-600 dark:bg-rose-500' : 'bg-rose-200 dark:bg-rose-950/60 hover:bg-rose-300';
                            @endphp
                            <div class="flex flex-col items-center gap-1.5 h-full justify-end group">
                                <span class="text-[10px] font-extrabold text-rose-600 dark:text-rose-400 group-hover:scale-110 transition-transform">{{ $tCount }}</span>
                                <div class="w-full max-w-[28px] bg-gray-100 dark:bg-zinc-800 rounded-t-lg overflow-hidden flex items-end h-full">
                                    <div class="w-full rounded-t-lg {{ $barBg }} transition-all duration-300" style="height: {{ max(4, $heightPct) }}%"></div>
                                </div>
                                <span class="text-[10px] font-bold uppercase {{ $isSel ? 'text-rose-600 dark:text-rose-400 font-extrabold' : 'text-gray-400' }}">{{ $tData['month_name'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Visual Bar Chart per Kelas --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-6 shadow-sm space-y-6">
                    <div class="flex items-center justify-between border-b pb-4 dark:border-zinc-800">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Peringkat Pelanggaran per Kelas (Diurutkan dari Terbanyak)</h3>
                            <p class="text-xs text-gray-500">Perbandingan jumlah kasus dan poin pelanggaran antar kelas bulan {{ $monthsList[$month] }} {{ $year }}.</p>
                        </div>
                    </div>

                    @php
                        $maxClassCount = max(1, $classReport->max('violation_count'));
                    @endphp

                    <div class="space-y-4">
                        @forelse ($classReport as $index => $item)
                            @php
                                $cRoom = $item['class_room'];
                                $vCount = $item['violation_count'];
                                $vPoints = $item['violation_points'];
                                $pct = round(($vCount / $maxClassCount) * 100);
                                $badgeColor = $index === 0 && $vCount > 0 ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300' : 'bg-gray-100 text-gray-700 dark:bg-zinc-800 dark:text-zinc-300';
                            @endphp
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-full {{ $badgeColor }} flex items-center justify-center text-xs font-bold">#{{ $index + 1 }}</span>
                                        <span class="text-gray-900 dark:text-white font-bold">{{ $cRoom->name }}</span>
                                        <span class="text-xs text-gray-400">({{ $item['total_students'] }} Murid)</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="font-bold text-rose-600 dark:text-rose-400">{{ $vCount }} Kasus</span>
                                        <span class="text-xs text-gray-500">({{ $vPoints }} Poin)</span>
                                    </div>
                                </div>
                                <div class="w-full bg-gray-100 dark:bg-zinc-800 rounded-full h-3 overflow-hidden border border-gray-200/50 dark:border-zinc-700/50">
                                    <div class="bg-gradient-to-r from-amber-500 to-rose-600 h-full rounded-full transition-all duration-500" style="width: {{ max(2, $pct) }}%"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-center py-6 text-gray-400 text-sm">Belum ada data kelas terdaftar.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Rekapitulasi Detail per Murid dalam Kelas --}}
                <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 p-6 shadow-sm space-y-6">
                    <div class="border-b pb-4 dark:border-zinc-800">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Rekapitulasi Detail Pelanggaran Murid per Kelas</h3>
                        <p class="text-xs text-gray-500">Klik kelas untuk melihat rincian pelanggaran keterlambatan, atribut, tata tertib, dan sanksi per murid.</p>
                    </div>

                    <div class="space-y-4" x-data="{ openClass: null }">
                        @foreach ($classReport as $cIndex => $item)
                            @php
                                $cRoom = $item['class_room'];
                                $stDetails = $item['students_detail'];
                            @endphp
                            <div class="border border-gray-200 dark:border-zinc-800 rounded-xl overflow-hidden transition-colors">
                                <button
                                    type="button"
                                    @click="openClass = (openClass === {{ $cIndex }} ? null : {{ $cIndex }})"
                                    class="w-full px-5 py-4 flex items-center justify-between bg-gray-50 dark:bg-zinc-800/40 hover:bg-gray-100 dark:hover:bg-zinc-800 transition text-left cursor-pointer"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-bold text-xs flex items-center justify-center">
                                            {{ $cIndex + 1 }}
                                        </span>
                                        <div>
                                            <h4 class="font-bold text-gray-900 dark:text-white text-base">{{ $cRoom->name }}</h4>
                                            <p class="text-xs text-gray-500">Total Murid: {{ $item['total_students'] }} | Total Kasus: {{ $item['violation_count'] }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-xs font-bold text-rose-600 dark:text-rose-400">{{ $item['violation_points'] }} Poin</span>
                                        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="openClass === {{ $cIndex }} ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </button>

                                <div x-show="openClass === {{ $cIndex }}" x-cloak class="p-4 border-t border-gray-200 dark:border-zinc-800 bg-white dark:bg-zinc-900">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-xs">
                                            <thead>
                                                <tr class="text-gray-500 dark:text-zinc-400 uppercase font-bold text-[10px]">
                                                    <th class="py-2 px-3 text-left">Nama Murid</th>
                                                    <th class="py-2 px-3 text-center">⏰ Telat</th>
                                                    <th class="py-2 px-3 text-center">👔 Atribut</th>
                                                    <th class="py-2 px-3 text-center">📜 Tatib</th>
                                                    <th class="py-2 px-3 text-center">Total Kasus</th>
                                                    <th class="py-2 px-3 text-center">Total Poin</th>
                                                    <th class="py-2 px-3 text-left">Sanksi Terakhir</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100 dark:divide-zinc-800/80">
                                                @foreach ($stDetails as $sDet)
                                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/30">
                                                        <td class="py-2.5 px-3 font-bold text-gray-900 dark:text-white">
                                                            {{ $sDet['student']->name }}
                                                        </td>
                                                        <td class="py-2.5 px-3 text-center text-amber-600 font-bold">{{ $sDet['lateness_count'] }}</td>
                                                        <td class="py-2.5 px-3 text-center text-blue-600 font-bold">{{ $sDet['attribute_count'] }}</td>
                                                        <td class="py-2.5 px-3 text-center text-rose-600 font-bold">{{ $sDet['tatib_count'] }}</td>
                                                        <td class="py-2.5 px-3 text-center font-black text-gray-900 dark:text-white">{{ $sDet['violation_count'] }}</td>
                                                        <td class="py-2.5 px-3 text-center font-black text-rose-600 dark:text-rose-400">{{ $sDet['violation_points'] }}</td>
                                                        <td class="py-2.5 px-3 text-gray-500 italic">
                                                            {{ implode(', ', $sDet['recent_sanctions']) ?: '-' }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
