<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between w-full">
            <div>
                <h2 class="font-bold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ __('Laporan Perkembangan Triwulan (Term)') }}
                </h2>
                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                    Format cetak dan rekapan triwulan otomatis berdasarkan program kelas & pengelompokan halaqoh Musyrif.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-3 py-1 bg-indigo-500/10 text-indigo-500 rounded-full text-xs font-bold border border-indigo-500/20">
                    Program: {{ $selectedClass?->program?->name ?? 'Tahfizh' }}
                </span>
                <span class="px-3 py-1 bg-amber-500/10 text-amber-500 rounded-full text-xs font-bold border border-amber-500/20">
                    🔒 Eksklusif Admin
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Filter Panel -->
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm sm:rounded-xl p-5">
                <form method="GET" action="{{ route('reports.quarterly') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                    <div>
                        <label for="class_room_id" class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400 mb-1">
                            Pilih Kelas Halaqoh
                        </label>
                        <select name="class_room_id" id="class_room_id" class="block w-full rounded-lg border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white" onchange="this.form.submit()">
                            @foreach ($classRooms as $class)
                                <option value="{{ $class->id }}" @selected($selectedClass?->id == $class->id) class="dark:bg-zinc-900">
                                    {{ $class->name }} ({{ $class->program?->name }})
                                </option>
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
                        <label for="month" class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-zinc-400 mb-1">
                            Pilih Bulan Laporan
                        </label>
                        <select name="month" id="month" class="block w-full rounded-lg border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white" onchange="this.form.submit()">
                            @foreach ($monthsMap as $mCode => $mName)
                                <option value="{{ $mCode }}" @selected($selectedMonth == $mCode) class="dark:bg-zinc-900">{{ $mName }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            <!-- Export Bar -->
            <div class="flex items-center justify-between gap-4 bg-emerald-500/10 border border-emerald-500/20 shadow-sm rounded-xl p-4">
                <span class="text-xs font-semibold text-emerald-800 dark:text-emerald-400">
                    💡 <strong>Informasi:</strong> Data di bawah disinkronkan langsung dari data absensi, setoran hafalan, dan pelanggaran asli yang di-input oleh guru-guru di sistem selama bulan terpilih.
                </span>
                <button type="button" onclick="alert('Mencetak Laporan Kelas: {{ $selectedClass?->name }}')" class="shrink-0 inline-flex items-center justify-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-sm transition gap-2 cursor-pointer">
                    📥 Ekspor Seluruh Kelas ke Excel (.xlsx)
                </button>
            </div>

            <!-- HALAQOH GROUPINGS -->
            @forelse ($halaqahData as $hIdx => $halaqah)
                <div class="bg-white dark:bg-zinc-900 border border-gray-250 dark:border-zinc-800 shadow-md rounded-2xl overflow-hidden p-6 space-y-6" x-data="{ activeTab: 'presensi', pekanTab: 1 }">
                    
                    <!-- Halaqoh Header -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b dark:border-zinc-800 pb-4">
                        <div>
                            <h3 class="text-lg font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                                🕌 Halaqoh: <span class="text-indigo-650 dark:text-indigo-400">{{ $halaqah['musyrif'] }}</span>
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                                Kelompok bimbingan di kelas <span class="font-bold">{{ $selectedClass?->name }}</span> · Total: {{ $halaqah['total_students'] }} Santri aktif.
                            </p>
                        </div>

                        <!-- Mini Statistics for this Halaqoh -->
                        <div class="flex items-center gap-3">
                            <div class="px-3 py-1.5 bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 rounded-lg text-xs font-bold border border-emerald-200 dark:border-emerald-900/30">
                                Tuntas: {{ $halaqah['tuntas_count'] }}
                            </div>
                            <div class="px-3 py-1.5 bg-rose-50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-455 rounded-lg text-xs font-bold border border-rose-250 dark:border-rose-900/30">
                                Belum: {{ $halaqah['total_students'] - $halaqah['tuntas_count'] }}
                            </div>
                            <div class="px-3 py-1.5 bg-blue-50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-400 rounded-lg text-xs font-bold border border-blue-200 dark:border-blue-900/30">
                                Rasio: {{ $halaqah['total_students'] > 0 ? round(($halaqah['tuntas_count'] / $halaqah['total_students']) * 100, 1) : 0 }}%
                            </div>
                        </div>
                    </div>

                    <!-- Inner Navigation Tabs -->
                    <div class="flex flex-wrap items-center gap-1 border-b dark:border-zinc-800 pb-1">
                        <button type="button" @click="activeTab = 'presensi'" :class="activeTab === 'presensi' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-zinc-350'" class="px-4 py-2 text-xs font-bold uppercase tracking-wider border-b-2 transition duration-150 cursor-pointer">
                            📅 Presensi
                        </button>
                        <button type="button" @click="activeTab = 'jurnal'" :class="activeTab === 'jurnal' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-zinc-350'" class="px-4 py-2 text-xs font-bold uppercase tracking-wider border-b-2 transition duration-150 cursor-pointer">
                            📝 Jurnal Pembelajaran
                        </button>
                        <button type="button" @click="activeTab = 'setoran'" :class="activeTab === 'setoran' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-zinc-350'" class="px-4 py-2 text-xs font-bold uppercase tracking-wider border-b-2 transition duration-150 cursor-pointer">
                            📖 Capaian Hafalan (Setoran)
                        </button>
                        <button type="button" @click="activeTab = 'grafik'" :class="activeTab === 'grafik' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-zinc-350'" class="px-4 py-2 text-xs font-bold uppercase tracking-wider border-b-2 transition duration-150 cursor-pointer">
                            📈 Grafik Akhir Bulan
                        </button>
                        <button type="button" @click="activeTab = 'term'" :class="activeTab === 'term' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-zinc-350'" class="px-4 py-2 text-xs font-bold uppercase tracking-wider border-b-2 transition duration-150 cursor-pointer">
                            🎓 Term / Indeks (DNS)
                        </button>
                    </div>

                    <!-- TAB CONTENTS -->

                    <!-- 1. PRESENSI -->
                    <div x-show="activeTab === 'presensi'" class="space-y-4">
                        @if ($isTahfizhProgram)
                            <!-- PRESENSI FORMAT TAHFIZH (July, Aug, Sept grid) -->
                            <div class="overflow-x-auto border dark:border-zinc-800 rounded-xl bg-gray-50/50 dark:bg-zinc-900/50">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-[10px] text-center">
                                    <thead class="bg-gray-150 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 font-bold">
                                        <tr>
                                            <th rowspan="2" class="px-3 py-3 text-left w-10 border-b border-r dark:border-zinc-700">No</th>
                                            <th rowspan="2" class="px-4 py-3 text-left min-w-[150px] border-b border-r dark:border-zinc-700">Nama Murid</th>
                                            @foreach ($halaqah['months'] as $mName)
                                                <th colspan="15" class="px-3 py-2 border-b border-r dark:border-zinc-700 uppercase tracking-wider">{{ $mName }}</th>
                                            @endforeach
                                        </tr>
                                        <tr class="bg-gray-100 dark:bg-zinc-850 border-b dark:border-zinc-750">
                                            @foreach ($halaqah['months'] as $mName)
                                                @for ($day = 1; $day <= 12; $day++)
                                                    <th class="px-1 py-1.5 border-r dark:border-zinc-700 w-5 text-center font-normal">{{ $day }}</th>
                                                @endfor
                                                <th class="px-1.5 py-1.5 border-r dark:border-zinc-700 w-6 font-bold text-blue-500">S</th>
                                                <th class="px-1.5 py-1.5 border-r dark:border-zinc-700 w-6 font-bold text-amber-500">I</th>
                                                <th class="px-1.5 py-1.5 border-r dark:border-zinc-700 w-6 font-bold text-rose-500">A</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-800">
                                        @foreach ($halaqah['students'] as $idx => $student)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-850/20">
                                                <td class="px-3 py-2 border-r dark:border-zinc-700 text-left text-gray-400 font-bold">{{ $idx + 1 }}</td>
                                                <td class="px-4 py-2 border-r dark:border-zinc-700 text-left font-bold text-gray-900 dark:text-zinc-200">{{ $student->name }}</td>
                                                @foreach ($halaqah['months'] as $mName)
                                                    @php $sPres = $halaqah['presensi'][$student->id][$mName]; @endphp
                                                    @for ($day = 1; $day <= 12; $day++)
                                                        @php $status = $sPres['days'][$day]; @endphp
                                                        <td class="px-1 py-2 border-r dark:border-zinc-700 w-5">
                                                            @if($status === 'H')
                                                                <span class="text-emerald-500 font-extrabold">·</span>
                                                            @elseif($status === 'S')
                                                                <span class="text-blue-500 font-extrabold text-[9px]">S</span>
                                                            @elseif($status === 'I')
                                                                <span class="text-amber-500 font-extrabold text-[9px]">I</span>
                                                            @else
                                                                <span class="text-rose-500 font-extrabold text-[9px]">A</span>
                                                            @endif
                                                        </td>
                                                    @endfor
                                                    <td class="px-1.5 py-2 border-r dark:border-zinc-700 font-bold {{ $sPres['sakit'] > 0 ? 'text-blue-500' : 'text-gray-300' }}">{{ $sPres['sakit'] ?: '-' }}</td>
                                                    <td class="px-1.5 py-2 border-r dark:border-zinc-700 font-bold {{ $sPres['izin'] > 0 ? 'text-amber-500' : 'text-gray-300' }}">{{ $sPres['izin'] ?: '-' }}</td>
                                                    <td class="px-1.5 py-2 border-r dark:border-zinc-700 font-bold {{ $sPres['alpa'] > 0 ? 'text-rose-500' : 'text-gray-300' }}">{{ $sPres['alpa'] ?: '-' }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <!-- PRESENSI FORMAT REGULER (Pekan 1-5 grid) -->
                            <div class="overflow-x-auto border dark:border-zinc-800 rounded-xl bg-gray-50/50 dark:bg-zinc-900/50">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-xs text-center">
                                    <thead class="bg-gray-150 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 font-bold">
                                        <tr>
                                            <th rowspan="2" class="px-4 py-3 text-left w-12 border-b border-r dark:border-zinc-700">No</th>
                                            <th rowspan="2" class="px-4 py-3 text-left min-w-[200px] border-b border-r dark:border-zinc-700">Nama Murid</th>
                                            <th colspan="5" class="px-3 py-2 border-b border-r dark:border-zinc-700 uppercase tracking-wider">Kehadiran Pekanan</th>
                                            <th colspan="4" class="px-3 py-2 border-b dark:border-zinc-700 uppercase tracking-wider">Rekap Kehadiran</th>
                                        </tr>
                                        <tr class="bg-gray-100 dark:bg-zinc-850 border-b dark:border-zinc-750">
                                            <th class="px-2 py-1.5 border-r dark:border-zinc-700">Pekan 1</th>
                                            <th class="px-2 py-1.5 border-r dark:border-zinc-700">Pekan 2</th>
                                            <th class="px-2 py-1.5 border-r dark:border-zinc-700">Pekan 3</th>
                                            <th class="px-2 py-1.5 border-r dark:border-zinc-700">Pekan 4</th>
                                            <th class="px-2 py-1.5 border-r dark:border-zinc-700">Pekan 5</th>
                                            <th class="px-2 py-1.5 border-r dark:border-zinc-700 text-emerald-600 font-bold">Hadir</th>
                                            <th class="px-2 py-1.5 border-r dark:border-zinc-700 text-amber-500 font-bold">Izin</th>
                                            <th class="px-2 py-1.5 border-r dark:border-zinc-700 text-blue-500 font-bold">Sakit</th>
                                            <th class="px-2 py-1.5 text-rose-500 font-bold">Alpa</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-800">
                                        @foreach ($halaqah['students'] as $idx => $student)
                                            @php $sPres = $halaqah['presensi'][$student->id]; @endphp
                                            <tr class="hover:bg-gray-55/50 dark:hover:bg-zinc-850/20">
                                                <td class="px-4 py-3 border-r dark:border-zinc-700 text-left text-gray-400 font-bold">{{ $idx + 1 }}</td>
                                                <td class="px-4 py-3 border-r dark:border-zinc-700 text-left font-bold text-gray-900 dark:text-zinc-200">{{ $student->name }}</td>
                                                @for ($p = 1; $p <= 5; $p++)
                                                    <td class="px-2 py-3 border-r dark:border-zinc-700">
                                                        @if ($sPres['pekan'][$p] === 'Hadir')
                                                            <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 font-semibold border border-emerald-200/50 dark:border-emerald-900/30">Hadir</span>
                                                        @elseif ($sPres['pekan'][$p] === 'Izin')
                                                            <span class="px-2 py-0.5 rounded text-[10px] bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 font-semibold border border-amber-250/50 dark:border-amber-900/30">Izin</span>
                                                        @elseif ($sPres['pekan'][$p] === 'Sakit')
                                                            <span class="px-2 py-0.5 rounded text-[10px] bg-blue-50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-400 font-semibold border border-blue-200/50 dark:border-blue-900/30">Sakit</span>
                                                        @else
                                                            <span class="px-2 py-0.5 rounded text-[10px] bg-rose-50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-455 font-semibold border border-rose-250/50 dark:border-rose-900/30">Alpa</span>
                                                        @endif
                                                    </td>
                                                @endfor
                                                <td class="px-2 py-3 border-r dark:border-zinc-700 font-bold text-emerald-600">{{ $sPres['hadir'] }}</td>
                                                <td class="px-2 py-3 border-r dark:border-zinc-700 font-bold {{ $sPres['izin'] > 0 ? 'text-amber-500' : 'text-gray-300' }}">{{ $sPres['izin'] ?: '-' }}</td>
                                                <td class="px-2 py-3 border-r dark:border-zinc-700 font-bold {{ $sPres['sakit'] > 0 ? 'text-blue-500' : 'text-gray-300' }}">{{ $sPres['sakit'] ?: '-' }}</td>
                                                <td class="px-2 py-3 font-bold {{ $sPres['alpa'] > 0 ? 'text-rose-500' : 'text-gray-300' }}">{{ $sPres['alpa'] ?: '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <!-- 2. JURNAL PEMBELAJARAN -->
                    <div x-show="activeTab === 'jurnal'" class="space-y-4" style="display: none;">
                        <div class="overflow-x-auto border dark:border-zinc-800 rounded-xl bg-gray-50/50 dark:bg-zinc-900/50">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-xs">
                                <thead class="bg-gray-150 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 font-bold">
                                    <tr>
                                        <th class="px-4 py-3 text-left w-12 border-b border-r dark:border-zinc-700">No</th>
                                        <th class="px-4 py-3 text-left border-b border-r dark:border-zinc-700 w-48">Hari/ Tanggal</th>
                                        <th class="px-4 py-3 text-left border-b border-r dark:border-zinc-700">Materi</th>
                                        <th class="px-4 py-3 text-center border-b border-r dark:border-zinc-700 w-36">Jumlah Murid</th>
                                        <th class="px-4 py-3 text-center border-b dark:border-zinc-700 w-24">Paraf</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-800">
                                    @foreach ($halaqah['jurnal'] as $jIdx => $jurnal)
                                        <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-850/20">
                                            <td class="px-4 py-3 border-r dark:border-zinc-700 text-left text-gray-400 font-bold">{{ $jIdx + 1 }}</td>
                                            <td class="px-4 py-3 border-r dark:border-zinc-700 text-left font-bold text-gray-700 dark:text-zinc-300">{{ $jurnal['tanggal'] }}</td>
                                            <td class="px-4 py-3 border-r dark:border-zinc-700 text-left text-gray-900 dark:text-white">{{ $jurnal['materi'] }}</td>
                                            <td class="px-4 py-3 border-r dark:border-zinc-700 text-center font-semibold text-gray-600 dark:text-zinc-300">{{ $jurnal['jumlah_murid'] }} Santri</td>
                                            <td class="px-4 py-3 text-center text-teal-650 font-extrabold text-lg">{{ $jurnal['paraf'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 3. CAPAIAN HAFALAN (SETORAN) -->
                    <div x-show="activeTab === 'setoran'" class="space-y-4" style="display: none;">
                        @if ($isTahfizhProgram)
                            <!-- TAHFIZH SETORAN: DAILY TABS (Senin - Jumat) -->
                            <div class="flex items-center justify-between gap-3 bg-gray-50 dark:bg-zinc-950 p-2.5 rounded-xl border dark:border-zinc-800">
                                <div class="flex items-center gap-1.5">
                                    @for ($p = 1; $p <= 5; $p++)
                                        <button type="button" @click="pekanTab = {{ $p }}" :class="pekanTab === {{ $p }} ? 'bg-indigo-600 text-white shadow' : 'bg-white dark:bg-zinc-900 text-gray-600 dark:text-zinc-400 hover:bg-gray-150'" class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition duration-150 cursor-pointer">
                                            Pekan {{ $p }}
                                        </button>
                                    @endfor
                                </div>
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider pr-2">Jurnal Setoran Harian</span>
                            </div>

                            <!-- Render Pekan Tables -->
                            @for ($p = 1; $p <= 5; $p++)
                                <div x-show="pekanTab === {{ $p }}" class="overflow-x-auto border dark:border-zinc-800 rounded-xl bg-gray-50/50 dark:bg-zinc-900/50">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-[10px] text-center">
                                        <thead class="bg-gray-150 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 font-bold">
                                            <tr>
                                                <th rowspan="2" class="px-3 py-3 text-left w-10 border-b border-r dark:border-zinc-700">No</th>
                                                <th rowspan="2" class="px-4 py-3 text-left min-w-[150px] border-b border-r dark:border-zinc-700">Nama Murid</th>
                                                <th rowspan="2" class="px-2 py-3 border-b border-r dark:border-zinc-700">Halaqoh</th>
                                                <th colspan="2" class="px-2 py-1.5 border-b border-r dark:border-zinc-700">SENIN</th>
                                                <th colspan="2" class="px-2 py-1.5 border-b border-r dark:border-zinc-700">SELASA</th>
                                                <th colspan="2" class="px-2 py-1.5 border-b border-r dark:border-zinc-700">RABU</th>
                                                <th colspan="2" class="px-2 py-1.5 border-b border-r dark:border-zinc-700">KAMIS</th>
                                                <th colspan="2" class="px-2 py-1.5 border-b border-r dark:border-zinc-700">JUM'AT</th>
                                                <th colspan="2" class="px-3 py-2 border-b dark:border-zinc-700 uppercase tracking-wider">Rekap Pekan</th>
                                            </tr>
                                            <tr class="bg-gray-100 dark:bg-zinc-850 border-b dark:border-zinc-750">
                                                <!-- Senin -->
                                                <th class="px-2 py-1 border-r dark:border-zinc-700 font-normal">Setoran</th>
                                                <th class="px-1.5 py-1 border-r dark:border-zinc-700 w-8 font-normal">Nilai</th>
                                                <!-- Selasa -->
                                                <th class="px-2 py-1 border-r dark:border-zinc-700 font-normal">Setoran</th>
                                                <th class="px-1.5 py-1 border-r dark:border-zinc-700 w-8 font-normal">Nilai</th>
                                                <!-- Rabu -->
                                                <th class="px-2 py-1 border-r dark:border-zinc-700 font-normal">Setoran</th>
                                                <th class="px-1.5 py-1 border-r dark:border-zinc-700 w-8 font-normal">Nilai</th>
                                                <!-- Kamis -->
                                                <th class="px-2 py-1 border-r dark:border-zinc-700 font-normal">Setoran</th>
                                                <th class="px-1.5 py-1 border-r dark:border-zinc-700 w-8 font-normal">Nilai</th>
                                                <!-- Jumat -->
                                                <th class="px-2 py-1 border-r dark:border-zinc-700 font-normal">Setoran</th>
                                                <th class="px-1.5 py-1 border-r dark:border-zinc-700 w-8 font-normal">Nilai</th>
                                                <!-- Rekap -->
                                                <th class="px-2.5 py-1 border-r dark:border-zinc-700 w-16">Baris</th>
                                                <th class="px-2 py-1">Nilai</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-800">
                                            @foreach ($halaqah['tahfizh_records'] as $idx => $row)
                                                @php $wRecord = $row['pekan'][$p]; @endphp
                                                <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-850/20">
                                                    <td class="px-3 py-2.5 border-r dark:border-zinc-700 text-left text-gray-400 font-bold">{{ $idx + 1 }}</td>
                                                    <td class="px-4 py-2.5 border-r dark:border-zinc-700 text-left font-bold text-gray-900 dark:text-zinc-200">{{ $row['name'] }}</td>
                                                    <td class="px-2 py-2.5 border-r dark:border-zinc-700 font-medium text-gray-600 dark:text-zinc-400">{{ $row['level'] }}</td>
                                                    
                                                    <!-- Days -->
                                                    @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'] as $dayName)
                                                        @php $dayLog = $wRecord['days'][$dayName]; @endphp
                                                        @if ($dayLog['surah'] === 'Tidak Masuk')
                                                            <td colspan="2" class="px-2 py-2.5 border-r dark:border-zinc-700 bg-amber-500/5 text-amber-500 font-bold uppercase tracking-wider text-[9px] text-center">Sakit</td>
                                                        @else
                                                            <td class="px-2 py-2.5 border-r dark:border-zinc-700 text-left">
                                                                <span class="block font-medium text-gray-800 dark:text-zinc-350">{{ $dayLog['surah'] }}</span>
                                                                <span class="block text-[8px] text-gray-400 mt-0.5">{{ $dayLog['ayat_start'] }}-{{ $dayLog['ayat_end'] }} ({{ $dayLog['baris'] }} Brs)</span>
                                                            </td>
                                                            <td class="px-1.5 py-2.5 border-r dark:border-zinc-700 font-bold text-gray-700 dark:text-zinc-300 text-center">{{ $dayLog['nilai'] }}</td>
                                                        @endif
                                                    @endforeach

                                                    <td class="px-2.5 py-2.5 border-r dark:border-zinc-700 font-extrabold text-teal-600 dark:text-teal-400 text-center">{{ $wRecord['week_lines'] }} Brs</td>
                                                    <td class="px-2 py-2.5 font-bold text-center text-teal-650">A</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endfor
                        @else
                            <!-- REGULER SETORAN: SINGLE TABLE (Pekan 1-5 side by side) -->
                            <div class="overflow-x-auto border dark:border-zinc-800 rounded-xl bg-gray-50/50 dark:bg-zinc-900/50">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-[10px] text-center">
                                    <thead class="bg-gray-150 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 font-bold">
                                        <tr class="border-b dark:border-zinc-700">
                                            <th rowspan="3" class="px-2 py-3 text-left border-b border-r dark:border-zinc-750">No</th>
                                            <th rowspan="3" class="px-3 py-3 text-left min-w-[150px] border-b border-r dark:border-zinc-750">Nama Murid</th>
                                            <th rowspan="3" class="px-2 py-3 border-b border-r dark:border-zinc-750">Halaqah</th>
                                            <th colspan="5" class="px-2 py-2 border-b border-r dark:border-zinc-750">Jurnal & Setoran Pekanan</th>
                                            <th colspan="5" class="px-2 py-2 border-b dark:border-zinc-750">Rekap Bulanan</th>
                                        </tr>
                                        <tr class="border-b dark:border-zinc-700 bg-gray-100 dark:bg-zinc-850">
                                            <th class="px-3 py-1.5 border-r dark:border-zinc-750">Pekan 1</th>
                                            <th class="px-3 py-1.5 border-r dark:border-zinc-750">Pekan 2</th>
                                            <th class="px-3 py-1.5 border-r dark:border-zinc-750">Pekan 3</th>
                                            <th class="px-3 py-1.5 border-r dark:border-zinc-750">Pekan 4</th>
                                            <th class="px-3 py-1.5 border-r dark:border-zinc-750">Pekan 5</th>
                                            <th rowspan="2" class="px-2.5 py-2 border-r dark:border-zinc-750 font-bold">Total Baris</th>
                                            <th colspan="4" class="px-2 py-1.5 border-b dark:border-zinc-750">Kehadiran</th>
                                        </tr>
                                        <tr class="bg-gray-50 dark:bg-zinc-850 text-gray-500 dark:text-zinc-450 border-b dark:border-zinc-700">
                                            <th class="px-2 py-1 border-r dark:border-zinc-750 font-normal">Setoran & Nilai</th>
                                            <th class="px-2 py-1 border-r dark:border-zinc-750 font-normal">Setoran & Nilai</th>
                                            <th class="px-2 py-1 border-r dark:border-zinc-750 font-normal">Setoran & Nilai</th>
                                            <th class="px-2 py-1 border-r dark:border-zinc-750 font-normal">Setoran & Nilai</th>
                                            <th class="px-2 py-1 border-r dark:border-zinc-750 font-normal">Setoran & Nilai</th>
                                            <th class="px-1.5 py-1 border-r dark:border-zinc-750 text-teal-600 font-bold">H</th>
                                            <th class="px-1.5 py-1 border-r dark:border-zinc-750 text-amber-500 font-bold">I</th>
                                            <th class="px-1.5 py-1 border-r dark:border-zinc-750 text-blue-500 font-bold">S</th>
                                            <th class="px-1.5 py-1 text-rose-600 font-bold">A</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-800">
                                        @foreach ($halaqah['reguler_records'] as $idx => $row)
                                            @php $sPres = $halaqah['presensi'][$row['student_id']]; @endphp
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-zinc-850/20">
                                                <td class="px-2 py-2.5 border-r dark:border-zinc-750 text-left text-gray-400 font-bold">{{ $idx + 1 }}</td>
                                                <td class="px-3 py-2.5 border-r dark:border-zinc-750 text-left font-bold text-gray-900 dark:text-zinc-200">{{ $row['name'] }}</td>
                                                <td class="px-2 py-2.5 border-r dark:border-zinc-750 font-semibold text-gray-700 dark:text-zinc-300">{{ $row['level'] }}</td>
                                                
                                                <!-- Pekan 1 - 5 -->
                                                @for ($p = 1; $p <= 5; $p++)
                                                    @php $pRec = $row['pekan'][$p]; @endphp
                                                    <td class="px-2 py-2.5 border-r dark:border-zinc-750 text-left">
                                                        @if ($pRec['kehadiran'] === 'Hadir')
                                                            <span class="block font-medium text-gray-800 dark:text-zinc-350">{{ $pRec['surah'] }} {{ $pRec['ayat'] }}</span>
                                                            <span class="block text-[8px] text-gray-400 mt-0.5">{{ $pRec['baris'] }} Brs · Nilai: {{ $pRec['nilai'] }}</span>
                                                        @else
                                                            <span class="text-amber-500 font-extrabold uppercase text-[8px] tracking-wider block text-center py-1 bg-amber-500/5 rounded">{{ $pRec['kehadiran'] }}</span>
                                                        @endif
                                                    </td>
                                                @endfor

                                                <!-- Rekap -->
                                                <td class="px-2.5 py-2.5 border-r dark:border-zinc-750 font-extrabold text-teal-650 text-xs">{{ $row['total_lines'] }} Baris</td>
                                                <td class="px-1.5 py-2.5 border-r dark:border-zinc-750 font-bold text-teal-650 text-center">{{ $sPres['hadir'] }}</td>
                                                <td class="px-1.5 py-2.5 border-r dark:border-zinc-750 font-bold {{ $sPres['izin'] > 0 ? 'text-amber-500' : 'text-gray-300' }} text-center">{{ $sPres['izin'] ?: '-' }}</td>
                                                <td class="px-1.5 py-2.5 border-r dark:border-zinc-750 font-bold {{ $sPres['sakit'] > 0 ? 'text-blue-500' : 'text-gray-300' }} text-center">{{ $sPres['sakit'] ?: '-' }}</td>
                                                <td class="px-1.5 py-2.5 font-bold {{ $sPres['alpa'] > 0 ? 'text-rose-500' : 'text-gray-300' }} text-center">{{ $sPres['alpa'] ?: '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <!-- 4. GRAFIK AKHIR BULAN -->
                    <div x-show="activeTab === 'grafik'" class="space-y-6" style="display: none;">
                        <!-- Completion Graph mockup with CSS bars -->
                        <div class="bg-gray-50/50 dark:bg-zinc-950 p-5 rounded-2xl border dark:border-zinc-800 space-y-4">
                            <h4 class="text-xs font-extrabold uppercase tracking-wider text-gray-500 dark:text-zinc-400">
                                Visualisasi Ketuntasan Capaian Baris (Akhir Bulan)
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                                <!-- Bar Graph -->
                                <div class="space-y-3">
                                    @php
                                        $tCount = $halaqah['tuntas_count'];
                                        $total = $halaqah['total_students'];
                                        $tPercent = $total > 0 ? round(($tCount / $total) * 100) : 0;
                                        $btPercent = 100 - $tPercent;
                                    @endphp
                                    <div>
                                        <div class="flex justify-between text-xs font-semibold mb-1">
                                            <span class="text-emerald-600">✅ Tuntas (>= Target Baris)</span>
                                            <span>{{ $tCount }} Murid ({{ $tPercent }}%)</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-zinc-800 rounded-full h-3">
                                            <div class="bg-emerald-500 h-3 rounded-full transition-all duration-500" style="width: {{ $tPercent }}%"></div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between text-xs font-semibold mb-1">
                                            <span class="text-rose-600">❌ Tidak Tuntas (< Target Baris)</span>
                                            <span>{{ $total - $tCount }} Murid ({{ $btPercent }}%)</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-zinc-800 rounded-full h-3">
                                            <div class="bg-rose-500 h-3 rounded-full transition-all duration-500" style="width: {{ $btPercent }}%"></div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Legend and Stats -->
                                <div class="p-4 bg-white dark:bg-zinc-900 border dark:border-zinc-800 rounded-xl space-y-2 text-xs">
                                    <div class="flex justify-between border-b dark:border-zinc-800 pb-2">
                                        <span class="text-gray-500">Target Ketuntasan</span>
                                        <span class="font-bold text-gray-800 dark:text-zinc-200">{{ $isTahfizhProgram ? '120 - 150 Baris/Bulan' : '40 Baris/Bulan' }}</span>
                                    </div>
                                    <div class="flex justify-between pt-1">
                                        <span class="text-gray-500">Halaqoh Ratio</span>
                                        <span class="font-extrabold text-indigo-650 dark:text-indigo-400">{{ $tPercent }}% Ketuntasan Kelas</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Completion Details Table -->
                        <div class="overflow-x-auto border dark:border-zinc-800 rounded-xl bg-gray-50/50 dark:bg-zinc-900/50">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-xs text-center">
                                <thead class="bg-gray-150 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 font-bold">
                                    <tr>
                                        <th class="px-4 py-3 text-left w-12 border-b border-r dark:border-zinc-700">No</th>
                                        <th class="px-4 py-3 text-left border-b border-r dark:border-zinc-700">Nama Murid</th>
                                        <th class="px-4 py-3 border-b border-r dark:border-zinc-700">Halaqoh Group</th>
                                        <th class="px-4 py-3 border-b border-r dark:border-zinc-700 w-36">Capaian Baris</th>
                                        <th class="px-4 py-3 border-b border-r dark:border-zinc-700 w-36">Target Baris</th>
                                        <th class="px-4 py-3 border-b dark:border-zinc-700 w-44">Ketuntasan</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-800">
                                    @php $records = $isTahfizhProgram ? $halaqah['tahfizh_records'] : $halaqah['reguler_records']; @endphp
                                    @foreach ($records as $idx => $row)
                                        <tr class="hover:bg-gray-55/50 dark:hover:bg-zinc-850/20">
                                            <td class="px-4 py-3 border-r dark:border-zinc-700 text-left text-gray-400 font-bold">{{ $idx + 1 }}</td>
                                            <td class="px-4 py-3 border-r dark:border-zinc-700 text-left font-bold text-gray-900 dark:text-zinc-200">{{ $row['name'] }}</td>
                                            <td class="px-4 py-3 border-r dark:border-zinc-700 text-gray-500 dark:text-zinc-400">{{ $row['level'] }}</td>
                                            <td class="px-4 py-3 border-r dark:border-zinc-700 font-extrabold text-teal-650">{{ $row['total_lines'] }} Baris</td>
                                            <td class="px-4 py-3 border-r dark:border-zinc-700 font-semibold text-gray-600 dark:text-zinc-300">{{ $row['target_lines'] }} Baris</td>
                                            <td class="px-4 py-3 font-bold">
                                                @if ($row['is_tuntas'])
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-extrabold bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 border border-emerald-250 dark:border-emerald-900/30">
                                                        ✅ Tuntas
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-extrabold bg-rose-50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-455 border border-rose-250 dark:border-rose-900/30">
                                                        ❌ Tidak Tuntas
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- 5. TERM / INDEKS (DNS) -->
                    <div x-show="activeTab === 'term'" class="space-y-4" style="display: none;">
                        <div class="overflow-x-auto border dark:border-zinc-800 rounded-xl bg-gray-50/50 dark:bg-zinc-900/50">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-xs text-center">
                                <thead class="bg-gray-150 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 font-bold">
                                    <tr>
                                        <th rowspan="2" class="px-4 py-3 text-left w-12 border-b border-r dark:border-zinc-700">No</th>
                                        <th rowspan="2" class="px-4 py-3 text-left min-w-[180px] border-b border-r dark:border-zinc-700">Nama Murid</th>
                                        <th colspan="2" class="px-3 py-2 border-b border-r dark:border-zinc-700">Target Semester</th>
                                        <th colspan="2" class="px-3 py-2 border-b border-r dark:border-zinc-700">Capaian Akhir</th>
                                        <th rowspan="2" class="px-4 py-3 border-b border-r dark:border-zinc-700">Capaian Baris</th>
                                        <th rowspan="2" class="px-4 py-3 border-b border-r dark:border-zinc-700">Ketercapaian</th>
                                        <th colspan="3" class="px-3 py-2 border-b border-r dark:border-zinc-700">Absensi</th>
                                        <th rowspan="2" class="px-4 py-3 border-b dark:border-zinc-700">Pelanggaran</th>
                                    </tr>
                                    <tr class="bg-gray-100 dark:bg-zinc-850 border-b dark:border-zinc-750">
                                        <th class="px-3 py-1.5 border-r dark:border-zinc-700 font-normal">Surah</th>
                                        <th class="px-3 py-1.5 border-r dark:border-zinc-700 w-16 font-normal">Ayat</th>
                                        <th class="px-3 py-1.5 border-r dark:border-zinc-700 font-normal">Surah</th>
                                        <th class="px-3 py-1.5 border-r dark:border-zinc-700 w-16 font-normal">Ayat</th>
                                        <th class="px-1.5 py-1.5 border-r dark:border-zinc-700 text-rose-500 font-bold">A</th>
                                        <th class="px-1.5 py-1.5 border-r dark:border-zinc-700 text-amber-500 font-bold">I</th>
                                        <th class="px-1.5 py-1.5 border-r dark:border-zinc-700 text-blue-500 font-bold">S</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-800">
                                    @php $records = $isTahfizhProgram ? $halaqah['tahfizh_records'] : $halaqah['reguler_records']; @endphp
                                    @foreach ($records as $idx => $row)
                                        @php
                                            $sPres = $halaqah['presensi'][$row['student_id']];
                                            $aSum = $isTahfizhProgram ? (collect($sPres)->sum('alpa')) : $sPres['alpa'];
                                            $iSum = $isTahfizhProgram ? (collect($sPres)->sum('izin')) : $sPres['izin'];
                                            $sSum = $isTahfizhProgram ? (collect($sPres)->sum('sakit')) : $sPres['sakit'];
                                        @endphp
                                        <tr class="hover:bg-gray-55/50 dark:hover:bg-zinc-850/20">
                                            <td class="px-4 py-3 border-r dark:border-zinc-700 text-left text-gray-400 font-bold">{{ $idx + 1 }}</td>
                                            <td class="px-4 py-3 border-r dark:border-zinc-700 text-left font-bold text-gray-900 dark:text-zinc-200">{{ $row['name'] }}</td>
                                            <td class="px-3 py-3 border-r dark:border-zinc-700 font-semibold text-gray-700 dark:text-zinc-300">Juz 30</td>
                                            <td class="px-3 py-3 border-r dark:border-zinc-700 font-bold text-gray-900 dark:text-white">1-30</td>
                                            <td class="px-3 py-3 border-r dark:border-zinc-700 text-teal-650 font-semibold">Juz 30</td>
                                            <td class="px-3 py-3 border-r dark:border-zinc-700 font-bold text-teal-650">1-30</td>
                                            <td class="px-4 py-3 border-r dark:border-zinc-700 font-extrabold text-teal-650">{{ $row['total_lines'] }} Baris</td>
                                            <td class="px-4 py-3 border-r dark:border-zinc-700">
                                                @if ($row['is_tuntas'])
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold bg-emerald-50 dark:bg-emerald-950/20 text-emerald-700 dark:text-emerald-400 border border-emerald-250 dark:border-emerald-900/30 uppercase">Tuntas</span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold bg-rose-50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-455 border border-rose-250 dark:border-rose-900/30 uppercase">Tidak Tuntas</span>
                                                @endif
                                            </td>
                                            <td class="px-1.5 py-3 border-r dark:border-zinc-700 font-bold {{ $aSum > 0 ? 'text-rose-500' : 'text-gray-300' }}">{{ $aSum ?: '-' }}</td>
                                            <td class="px-1.5 py-3 border-r dark:border-zinc-700 font-bold {{ $iSum > 0 ? 'text-amber-500' : 'text-gray-300' }}">{{ $iSum ?: '-' }}</td>
                                            <td class="px-1.5 py-3 border-r dark:border-zinc-700 font-bold {{ $sSum > 0 ? 'text-blue-500' : 'text-gray-300' }}">{{ $sSum ?: '-' }}</td>
                                            <td class="px-4 py-3 font-bold {{ $row['pelanggaran'] > 0 ? 'text-rose-500' : 'text-gray-300' }}">{{ $row['pelanggaran'] ?: '0' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            @empty
                <div class="bg-white dark:bg-zinc-900 border border-gray-250 dark:border-zinc-800 shadow-sm rounded-xl p-10 text-center text-gray-500 dark:text-zinc-500">
                    Tidak ada santri aktif atau kelompok halaqoh di kelas yang dipilih.
                </div>
            @endforelse

        </div>
    </div>
</x-app-layout>
