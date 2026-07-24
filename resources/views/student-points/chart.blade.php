<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-zinc-900 dark:text-zinc-100 leading-tight">
                    Grafik Perkembangan Tanse (Ketahanan Sekolah)
                </h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                    Pemantauan tren pelanggaran kedisiplinan murid per kelas (Tata Tertib, Keterlambatan, dan Atribut/Seragam).
                </p>
            </div>

            {{-- Month & Year Filter Form --}}
            <form method="GET" action="{{ route('student-points.chart') }}" class="flex flex-wrap items-center gap-2">
                <select name="class_room_id" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white">
                    <option value="">Semua Kelas</option>
                    @foreach ($classRooms as $cRoom)
                        <option value="{{ $cRoom->id }}" @selected((string) $classRoomId === (string) $cRoom->id)>
                            {{ $cRoom->name }}
                        </option>
                    @endforeach
                </select>

                <select name="month" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white">
                    @foreach ($monthsList as $mNum => $mName)
                        <option value="{{ $mNum }}" @selected($mNum === $month)>{{ $mName }}</option>
                    @endforeach
                </select>

                <select name="year" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-sm py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white">
                    @for ($y = date('Y') - 2; $y <= date('Y') + 1; $y++)
                        <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                    @endfor
                </select>

                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-sm transition">
                    Filter
                </button>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

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
                            <p class="text-[11px] text-gray-400 mt-0.5">{{ $monthsList[$month] }} {{ $year }}</p>
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

                {{-- Kelas Terbanyak Pelanggaran --}}
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 rounded-xl">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Kelas Terbanyak</p>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mt-0.5 truncate max-w-[150px]">
                                {{ $classReport->first()['class_room']->name ?? '-' }}
                            </h3>
                            <p class="text-[11px] text-rose-500 font-semibold mt-0.5">{{ $classReport->first()['violation_count'] ?? 0 }} Pelanggaran</p>
                        </div>
                    </div>
                </div>

                {{-- Rincian Tipe Pelanggaran --}}
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex flex-col justify-center h-full">
                        <p class="text-xs font-semibold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-2">Sebaran Tipe Pelanggaran</p>
                        <div class="flex items-center justify-between text-xs space-x-1">
                            <span class="px-2 py-1 bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 rounded-lg font-bold">⏰ Keterlambatan: {{ $typeBreakdown['lateness'] }}</span>
                            <span class="px-2 py-1 bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 rounded-lg font-bold">👔 Atribut: {{ $typeBreakdown['attribute'] }}</span>
                            <span class="px-2 py-1 bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 rounded-lg font-bold">📜 Tatib: {{ $typeBreakdown['violation'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

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

            {{-- Visual Bar Chart per Kelas (Diurutkan Terbanyak ke Tersedikit) --}}
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
                                class="w-full px-5 py-4 flex items-center justify-between bg-gray-50 dark:bg-zinc-800/40 hover:bg-gray-100 dark:hover:bg-zinc-800 transition text-left"
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
                                <div class="flex items-center gap-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold {{ $item['violation_count'] > 0 ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' }}">
                                        {{ $item['violation_points'] }} Poin Total
                                    </span>
                                    <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': openClass === {{ $cIndex }} }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </div>
                            </button>

                            <div x-show="openClass === {{ $cIndex }}" x-transition class="p-4 overflow-x-auto border-t border-gray-200 dark:border-zinc-800">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-sm">
                                    <thead>
                                        <tr class="text-xs font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider text-left">
                                            <th class="py-3 px-3">Nama Murid</th>
                                            <th class="py-3 px-3 text-center">Total Kasus</th>
                                            <th class="py-3 px-3 text-center">Keterlambatan</th>
                                            <th class="py-3 px-3 text-center">Atribut/Seragam</th>
                                            <th class="py-3 px-3 text-center">Tata Tertib</th>
                                            <th class="py-3 px-3 text-center">Total Poin</th>
                                            <th class="py-3 px-3 text-left">Catatan / Sanksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-zinc-800">
                                        @forelse ($stDetails as $stData)
                                            @php
                                                $st = $stData['student'];
                                                $vCount = $stData['violation_count'];
                                                $vPoints = $stData['violation_points'];
                                            @endphp
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/20">
                                                <td class="py-3 px-3 font-semibold text-gray-900 dark:text-white">
                                                    {{ $st->name }}
                                                    <span class="block text-xs text-gray-400 font-normal">NIS: {{ $st->student_number ?: '-' }}</span>
                                                </td>
                                                <td class="py-3 px-3 text-center font-bold text-gray-700 dark:text-zinc-300">
                                                    {{ $vCount }}
                                                </td>
                                                <td class="py-3 px-3 text-center text-amber-600 dark:text-amber-400 font-bold">
                                                    {{ $stData['lateness_count'] }}
                                                </td>
                                                <td class="py-3 px-3 text-center text-blue-600 dark:text-blue-400 font-bold">
                                                    {{ $stData['attribute_count'] }}
                                                </td>
                                                <td class="py-3 px-3 text-center text-rose-600 dark:text-rose-400 font-bold">
                                                    {{ $stData['tatib_count'] }}
                                                </td>
                                                <td class="py-3 px-3 text-center">
                                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-black {{ $vPoints > 0 ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' }}">
                                                        {{ $vPoints }} Poin
                                                    </span>
                                                </td>
                                                <td class="py-3 px-3 text-xs text-gray-500">
                                                    @if (count($stData['recent_sanctions']) > 0)
                                                        {{ implode('; ', array_slice($stData['recent_sanctions'], 0, 2)) }}
                                                    @else
                                                        <span class="text-gray-300 dark:text-zinc-600">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="py-4 text-center text-gray-400">Tidak ada murid aktif di kelas ini.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
