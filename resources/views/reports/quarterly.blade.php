<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between w-full">
            <div>
                <h2 class="font-bold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Laporan Perkembangan Triwulan (Term)') }}
                </h2>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                    Uji coba dashboard dan format ekspor laporan triwulan berdasarkan template excel sekolah.
                </p>
            </div>
            <span class="px-3 py-1 bg-amber-500/10 text-amber-500 rounded-full text-xs font-bold border border-amber-500/20">
                🔒 Eksklusif Admin
            </span>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ activeTab: 'tahfizh' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Filter Card -->
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm sm:rounded-xl p-5">
                <form method="GET" action="{{ route('reports.quarterly') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <div>
                        <label for="class_room_id" class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400 mb-1">
                            Pilih Kelas Halaqoh
                        </label>
                        <select name="class_room_id" id="class_room_id" class="block w-full rounded-lg border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white" onchange="this.form.submit()">
                            @foreach ($classRooms as $class)
                                <option value="{{ $class->id }}" @selected($selectedClass?->id == $class->id) class="dark:bg-zinc-900">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="academic_year" class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400 mb-1">
                            Tahun Ajaran
                        </label>
                        <select name="academic_year" id="academic_year" class="block w-full rounded-lg border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white" onchange="this.form.submit()">
                            <option value="2025/2026" @selected($academicYear == '2025/2026') class="dark:bg-zinc-900">2025/2026</option>
                            <option value="2026/2027" @selected($academicYear == '2026/2027') class="dark:bg-zinc-900">2026/2027</option>
                        </select>
                    </div>

                    <div>
                        <label for="term" class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400 mb-1">
                            Periode Triwulan (Term)
                        </label>
                        <select name="term" id="term" class="block w-full rounded-lg border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white" onchange="this.form.submit()">
                            <option value="1" @selected($selectedTerm == '1') class="dark:bg-zinc-900">Term 1 (Jul - Sep)</option>
                            <option value="2" @selected($selectedTerm == '2') class="dark:bg-zinc-900">Term 2 (Okt - Des)</option>
                            <option value="3" @selected($selectedTerm == '3') class="dark:bg-zinc-900">Term 3 (Jan - Mar)</option>
                            <option value="4" @selected($selectedTerm == '4') class="dark:bg-zinc-900">Term 4 (Apr - Jun)</option>
                        </select>
                    </div>

                    <div>
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-indigo-650 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm transition duration-150">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tab Switcher & Excel Download Buttons -->
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm rounded-xl p-3 flex flex-col md:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-2 w-full md:w-auto">
                    <button type="button" @click="activeTab = 'tahfizh'" :class="activeTab === 'tahfizh' ? 'bg-indigo-600 text-white shadow' : 'bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 hover:bg-gray-250'" class="flex-1 md:flex-none px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2 cursor-pointer">
                        📊 Format Kelas Tahfizh
                    </button>
                    <button type="button" @click="activeTab = 'reguler'" :class="activeTab === 'reguler' ? 'bg-indigo-600 text-white shadow' : 'bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 hover:bg-gray-250'" class="flex-1 md:flex-none px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2 cursor-pointer">
                        📖 Format Kelas Reguler
                    </button>
                </div>
                <div class="flex items-center gap-2 w-full md:w-auto justify-end">
                    <button type="button" onclick="alert('Ini adalah mockup ekspor Excel. Integrasi PhpSpreadsheet akan menyusul.')" class="w-full md:w-auto inline-flex items-center justify-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-sm transition gap-2 cursor-pointer">
                        📥 Ekspor Excel (Format Aktif)
                    </button>
                </div>
            </div>

            <!-- METRIC CARDS -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-zinc-900 border border-gray-250 dark:border-zinc-850 p-5 rounded-xl shadow-sm">
                    <span class="block text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider">Total Murid</span>
                    <span class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1 block">{{ count($tahfizhMockData) }} Murid</span>
                </div>
                <div class="bg-white dark:bg-zinc-900 border border-gray-250 dark:border-zinc-850 p-5 rounded-xl shadow-sm">
                    <span class="block text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider">Ketuntasan (Tahfizh)</span>
                    <span class="text-2xl font-extrabold text-blue-600 dark:text-blue-400 mt-1 block">
                        {{ count($tahfizhMockData) > 0 ? round(($tuntasCount / count($tahfizhMockData)) * 100, 1) : 0 }}%
                    </span>
                </div>
                <div class="bg-white dark:bg-zinc-900 border border-gray-250 dark:border-zinc-850 p-5 rounded-xl shadow-sm">
                    <span class="block text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider">Tuntas</span>
                    <span class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1 block">{{ $tuntasCount }} Santri</span>
                </div>
                <div class="bg-white dark:bg-zinc-900 border border-gray-250 dark:border-zinc-850 p-5 rounded-xl shadow-sm">
                    <span class="block text-[10px] font-bold text-gray-400 dark:text-zinc-500 uppercase tracking-wider">Belum Tuntas</span>
                    <span class="text-2xl font-extrabold text-rose-600 dark:text-rose-455 mt-1 block">{{ $tidakTuntasCount }} Santri</span>
                </div>
            </div>

            <!-- TAB 1: KELAS TAHFIZH -->
            <div x-show="activeTab === 'tahfizh'" class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm sm:rounded-xl overflow-hidden transition-all duration-300" style="display: none;">
                <div class="px-6 py-4 border-b dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-900/50">
                    <h3 class="font-bold text-gray-900 dark:text-white text-base">Format Laporan Triwulan Kelas Tahfizh</h3>
                    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">Struktur tabel term yang merujuk pada berkas <code class="bg-gray-200 dark:bg-zinc-800 px-1 py-0.5 rounded text-[10px]">Bulan September Kelas Tahfizh.xlsx</code>.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-xs">
                        <thead class="bg-gray-100 dark:bg-zinc-850 text-center font-bold text-gray-600 dark:text-zinc-400">
                            <tr>
                                <th rowspan="2" class="px-3 py-3 text-left w-12 border-b border-r dark:border-zinc-800">No</th>
                                <th rowspan="2" class="px-4 py-3 text-left min-w-[200px] border-b border-r dark:border-zinc-800">Nama Murid</th>
                                <th rowspan="2" class="px-3 py-3 border-b border-r dark:border-zinc-800">Halaqah</th>
                                <th rowspan="2" class="px-3 py-3 border-b border-r dark:border-zinc-800">Musyrif</th>
                                <th colspan="2" class="px-3 py-2 border-b border-r dark:border-zinc-800">Target</th>
                                <th colspan="2" class="px-3 py-2 border-b border-r dark:border-zinc-800">Capaian</th>
                                <th rowspan="2" class="px-3 py-3 border-b border-r dark:border-zinc-800">Ketercapaian</th>
                                <th colspan="3" class="px-3 py-2 border-b border-r dark:border-zinc-800">Kehadiran</th>
                                <th rowspan="2" class="px-4 py-3 border-b dark:border-zinc-800">Pelanggaran</th>
                            </tr>
                            <tr class="bg-gray-50 dark:bg-zinc-850 text-gray-500 dark:text-zinc-450 border-b dark:border-zinc-800">
                                <th class="px-3 py-1.5 border-r dark:border-zinc-800">Surah</th>
                                <th class="px-3 py-1.5 border-r dark:border-zinc-800 w-16">Ayat</th>
                                <th class="px-3 py-1.5 border-r dark:border-zinc-800">Surah</th>
                                <th class="px-3 py-1.5 border-r dark:border-zinc-800 w-16">Ayat</th>
                                <th class="px-2 py-1.5 border-r dark:border-zinc-800 text-rose-600">A</th>
                                <th class="px-2 py-1.5 border-r dark:border-zinc-800 text-amber-500">I</th>
                                <th class="px-2 py-1.5 border-r dark:border-zinc-800 text-blue-500">S</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-150 dark:divide-zinc-800 bg-white dark:bg-zinc-900 text-center">
                            @forelse ($tahfizhMockData as $row)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-850/20">
                                    <td class="px-3 py-3 border-r dark:border-zinc-800 text-left text-gray-400 font-bold">{{ $row['no'] }}</td>
                                    <td class="px-4 py-3 border-r dark:border-zinc-800 text-left font-bold text-gray-900 dark:text-zinc-200">
                                        <div>{{ $row['name'] }}</div>
                                        <div class="text-[9px] text-gray-400 font-normal">NIS: {{ $row['nis'] }}</div>
                                    </td>
                                    <td class="px-3 py-3 border-r dark:border-zinc-800 font-semibold text-gray-700 dark:text-zinc-300">{{ $row['halaqah'] }}</td>
                                    <td class="px-3 py-3 border-r dark:border-zinc-800 text-gray-500 dark:text-zinc-400">{{ $row['musyrif'] }}</td>
                                    <td class="px-3 py-3 border-r dark:border-zinc-800 text-gray-800 dark:text-zinc-300 font-medium">{{ $row['target_surah'] }}</td>
                                    <td class="px-3 py-3 border-r dark:border-zinc-800 font-bold text-gray-900 dark:text-white">{{ $row['target_ayat'] }}</td>
                                    <td class="px-3 py-3 border-r dark:border-zinc-800 text-teal-600 dark:text-teal-400 font-medium">{{ $row['capaian_surah'] }}</td>
                                    <td class="px-3 py-3 border-r dark:border-zinc-800 font-extrabold text-teal-700 dark:text-teal-400">{{ $row['capaian_ayat'] }}</td>
                                    <td class="px-3 py-3 border-r dark:border-zinc-800">
                                        @if ($row['is_tuntas'])
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 border border-emerald-250 dark:border-emerald-900/30 uppercase">Tuntas</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold bg-rose-50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-455 border border-rose-250 dark:border-rose-900/30 uppercase">Tidak Tuntas</span>
                                        @endif
                                    </td>
                                    <td class="px-2 py-3 border-r dark:border-zinc-800 font-bold {{ $row['kehadiran']['alpa'] > 0 ? 'text-rose-650' : 'text-gray-400' }}">{{ $row['kehadiran']['alpa'] ?: '-' }}</td>
                                    <td class="px-2 py-3 border-r dark:border-zinc-800 font-bold {{ $row['kehadiran']['izin'] > 0 ? 'text-amber-500' : 'text-gray-400' }}">{{ $row['kehadiran']['izin'] ?: '-' }}</td>
                                    <td class="px-2 py-3 border-r dark:border-zinc-800 font-bold {{ $row['kehadiran']['sakit'] > 0 ? 'text-blue-500' : 'text-gray-400' }}">{{ $row['kehadiran']['sakit'] ?: '-' }}</td>
                                    <td class="px-4 py-3 font-bold {{ $row['pelanggaran'] > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-400' }}">{{ $row['pelanggaran'] ?: '0' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="13" class="px-6 py-10 text-gray-500 dark:text-zinc-500 text-center">
                                        Tidak ada santri aktif di kelas ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: KELAS REGULER -->
            <div x-show="activeTab === 'reguler'" class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm sm:rounded-xl overflow-hidden transition-all duration-300" style="display: none;">
                <div class="px-6 py-4 border-b dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-900/50">
                    <h3 class="font-bold text-gray-900 dark:text-white text-base">Format Jurnal Pembelajaran & Capaian Kelas Reguler</h3>
                    <p class="text-xs text-gray-500 dark:text-zinc-400 mt-0.5">Tampilan data perkembangan pekanan (Pekan 1 - Pekan 5) merujuk pada berkas <code class="bg-gray-200 dark:bg-zinc-800 px-1 py-0.5 rounded text-[10px]">Bulan September Kelas Reguler.xlsx</code>.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-[10px]">
                        <thead class="bg-gray-100 dark:bg-zinc-850 text-center font-bold text-gray-600 dark:text-zinc-400">
                            <!-- Row 1 Header -->
                            <tr class="border-b dark:border-zinc-800">
                                <th rowspan="3" class="px-2 py-3 text-left border-b border-r dark:border-zinc-800">No</th>
                                <th rowspan="3" class="px-3 py-3 text-left min-w-[150px] border-b border-r dark:border-zinc-800">Nama Murid</th>
                                <th rowspan="3" class="px-2 py-3 border-b border-r dark:border-zinc-800">Halaqah</th>
                                <th rowspan="3" class="px-2 py-3 border-b border-r dark:border-zinc-800">Musyrif</th>
                                <th colspan="5" class="px-2 py-2 border-b border-r dark:border-zinc-800">Jurnal & Setoran Pekanan</th>
                                <th colspan="5" class="px-2 py-2 border-b dark:border-zinc-800">Rekap Bulanan</th>
                            </tr>
                            <!-- Row 2 Header -->
                            <tr class="border-b dark:border-zinc-800">
                                <th class="px-3 py-1.5 border-r dark:border-zinc-800">Pekan 1</th>
                                <th class="px-3 py-1.5 border-r dark:border-zinc-800">Pekan 2</th>
                                <th class="px-3 py-1.5 border-r dark:border-zinc-800">Pekan 3</th>
                                <th class="px-3 py-1.5 border-r dark:border-zinc-800">Pekan 4</th>
                                <th class="px-3 py-1.5 border-r dark:border-zinc-800">Pekan 5</th>
                                <th rowspan="2" class="px-2 py-2 border-r dark:border-zinc-800">Total Baris</th>
                                <th colspan="4" class="px-2 py-1.5 border-b dark:border-zinc-800">Kehadiran</th>
                            </tr>
                            <!-- Row 3 Header -->
                            <tr class="bg-gray-50 dark:bg-zinc-850 text-gray-500 dark:text-zinc-450 border-b dark:border-zinc-800">
                                <th class="px-2 py-1 border-r dark:border-zinc-800">Surah - Ayat - Baris - Nilai</th>
                                <th class="px-2 py-1 border-r dark:border-zinc-800">Surah - Ayat - Baris - Nilai</th>
                                <th class="px-2 py-1 border-r dark:border-zinc-800">Surah - Ayat - Baris - Nilai</th>
                                <th class="px-2 py-1 border-r dark:border-zinc-800">Surah - Ayat - Baris - Nilai</th>
                                <th class="px-2 py-1 border-r dark:border-zinc-800">Surah - Ayat - Baris - Nilai</th>
                                <th class="px-1 py-1 border-r dark:border-zinc-800 text-teal-600">H</th>
                                <th class="px-1 py-1 border-r dark:border-zinc-800 text-amber-500">I</th>
                                <th class="px-1 py-1 border-r dark:border-zinc-800 text-blue-500">S</th>
                                <th class="px-1 py-1 dark:border-zinc-800 text-rose-600">A</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-150 dark:divide-zinc-800 bg-white dark:bg-zinc-900 text-center">
                            @forelse ($regulerMockData as $row)
                                <tr class="hover:bg-gray-55/50 dark:hover:bg-zinc-850/20">
                                    <td class="px-2 py-2.5 border-r dark:border-zinc-800 text-left text-gray-400 font-bold">{{ $row['no'] }}</td>
                                    <td class="px-3 py-2.5 border-r dark:border-zinc-800 text-left font-bold text-gray-900 dark:text-zinc-200">
                                        <div>{{ $row['name'] }}</div>
                                        <div class="text-[8px] text-gray-400 font-normal">NIS: {{ $row['nis'] }}</div>
                                    </td>
                                    <td class="px-2 py-2.5 border-r dark:border-zinc-800 font-semibold text-gray-700 dark:text-zinc-300">{{ $row['halaqah'] }}</td>
                                    <td class="px-2 py-2.5 border-r dark:border-zinc-800 text-gray-500 dark:text-zinc-400">{{ $row['musyrif'] }}</td>
                                    
                                    <!-- Pekan 1 -->
                                    <td class="px-2 py-2.5 border-r dark:border-zinc-800 text-left">
                                        <span class="font-medium text-gray-800 dark:text-zinc-300 block">{{ $row['pekan1']['surah'] }} {{ $row['pekan1']['ayat'] }}</span>
                                        <span class="text-[9px] text-gray-400 block mt-0.5">{{ $row['pekan1']['baris'] }} Brs · Nilai: {{ $row['pekan1']['nilai'] }}</span>
                                    </td>
                                    <!-- Pekan 2 -->
                                    <td class="px-2 py-2.5 border-r dark:border-zinc-800 text-left">
                                        <span class="font-medium text-gray-800 dark:text-zinc-300 block">{{ $row['pekan2']['surah'] }} {{ $row['pekan2']['ayat'] }}</span>
                                        <span class="text-[9px] text-gray-400 block mt-0.5">{{ $row['pekan2']['baris'] }} Brs · Nilai: {{ $row['pekan2']['nilai'] }}</span>
                                    </td>
                                    <!-- Pekan 3 -->
                                    <td class="px-2 py-2.5 border-r dark:border-zinc-800 text-left">
                                        @if($row['pekan3']['kehadiran'] === 'Hadir')
                                            <span class="font-medium text-gray-800 dark:text-zinc-300 block">{{ $row['pekan3']['surah'] }} {{ $row['pekan3']['ayat'] }}</span>
                                            <span class="text-[9px] text-gray-400 block mt-0.5">{{ $row['pekan3']['baris'] }} Brs · Nilai: {{ $row['pekan3']['nilai'] }}</span>
                                        @else
                                            <span class="text-amber-500 font-bold uppercase tracking-wider block text-center py-1 bg-amber-500/5 rounded">{{ $row['pekan3']['kehadiran'] }}</span>
                                        @endif
                                    </td>
                                    <!-- Pekan 4 -->
                                    <td class="px-2 py-2.5 border-r dark:border-zinc-800 text-left">
                                        <span class="font-medium text-gray-800 dark:text-zinc-300 block">{{ $row['pekan4']['surah'] }} {{ $row['pekan4']['ayat'] }}</span>
                                        <span class="text-[9px] text-gray-400 block mt-0.5">{{ $row['pekan4']['baris'] }} Brs · Nilai: {{ $row['pekan4']['nilai'] }}</span>
                                    </td>
                                    <!-- Pekan 5 -->
                                    <td class="px-2 py-2.5 border-r dark:border-zinc-800 text-left">
                                        @if($row['pekan5']['kehadiran'] === 'Hadir')
                                            <span class="font-medium text-gray-800 dark:text-zinc-300 block">{{ $row['pekan5']['surah'] }} {{ $row['pekan5']['ayat'] }}</span>
                                            <span class="text-[9px] text-gray-400 block mt-0.5">{{ $row['pekan5']['baris'] }} Brs · Nilai: {{ $row['pekan5']['nilai'] }}</span>
                                        @else
                                            <span class="text-blue-500 font-bold uppercase tracking-wider block text-center py-1 bg-blue-500/5 rounded">{{ $row['pekan5']['kehadiran'] }}</span>
                                        @endif
                                    </td>

                                    <!-- Rekap -->
                                    <td class="px-2 py-2.5 border-r dark:border-zinc-800 font-extrabold text-teal-600 dark:text-teal-400 text-xs">{{ $row['total_baris'] }} Baris</td>
                                    
                                    <td class="px-1.5 py-2.5 border-r dark:border-zinc-800 font-bold text-teal-650">{{ $row['rekap_kehadiran']['hadir'] }}</td>
                                    <td class="px-1.5 py-2.5 border-r dark:border-zinc-800 font-bold {{ $row['rekap_kehadiran']['izin'] > 0 ? 'text-amber-500' : 'text-gray-400' }}">{{ $row['rekap_kehadiran']['izin'] ?: '-' }}</td>
                                    <td class="px-1.5 py-2.5 border-r dark:border-zinc-800 font-bold {{ $row['rekap_kehadiran']['sakit'] > 0 ? 'text-blue-500' : 'text-gray-400' }}">{{ $row['rekap_kehadiran']['sakit'] ?: '-' }}</td>
                                    <td class="px-1.5 py-2.5 font-bold {{ $row['rekap_kehadiran']['alpa'] > 0 ? 'text-rose-650' : 'text-gray-400' }}">{{ $row['rekap_kehadiran']['alpa'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="14" class="px-6 py-10 text-gray-500 dark:text-zinc-500 text-center">
                                        Tidak ada santri aktif di kelas ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
