<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-200 leading-tight">
                Input Spreadsheet Perkembangan Kelas
            </h2>
            <a href="{{ route('hafalan-records.index') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 rounded-lg text-xs font-semibold hover:bg-gray-200 transition">
                ← Kembali ke List
            </a>
        </div>
    </x-slot>

    <!-- Alpine.js Component Script Definition (Safe from HTML parsing errors) -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('spreadsheetData', () => ({
                tab: 'hafalan',
                selectedClass: '{{ $selectedClassId }}',
                selectedMonth: '{{ $selectedMonth }}',
                selectedMobileDate: '{{ $dates[0] ?? '' }}',
                students: @json($students),
                surahs: @json($surahs),
                dates: @json($dates),
                attendancesMap: @json($attendancesMap),
                hafalanRecordsMap: @json($hafalanRecordsMap),
                ummiRecordsMap: @json($ummiRecordsMap),
                gridData: {},
                surahDetails: {},
                init() {
                    // Index surah details for fast lookup
                    this.surahs.forEach(s => {
                        this.surahDetails[s.id] = { id: s.id, number: s.number, totalAyah: s.total_ayah, name: s.name_latin };
                    });

                    // Initialize reactive grid data
                    this.students.forEach(s => {
                        this.gridData[s.id] = { dates: {} };
                        this.dates.forEach(d => {
                            let att = (this.attendancesMap[s.id] && this.attendancesMap[s.id][d]) ? this.attendancesMap[s.id][d] : '';
                            
                            // Hafalan tab
                            let hList = [];
                            if (this.hafalanRecordsMap[s.id] && this.hafalanRecordsMap[s.id][d]) {
                                hList = JSON.parse(JSON.stringify(this.hafalanRecordsMap[s.id][d]));
                            }
                            if (hList.length === 0) {
                                hList.push({ id: null, surah_id: '', ayah_start: '', ayah_end: '', score: '', status: 'passed', submission_type: 'new' });
                            }

                            // UMMI tab
                            let uData = (this.ummiRecordsMap[s.id] && this.ummiRecordsMap[s.id][d]) ? this.ummiRecordsMap[s.id][d] : null;
                            let uHafalans = [];
                            if (uData && uData.hafalans) {
                                uHafalans = JSON.parse(JSON.stringify(uData.hafalans));
                            }
                            if (uHafalans.length === 0) {
                                uHafalans.push({ id: null, surah_id: '', ayah: '' });
                            }

                            this.gridData[s.id].dates[d] = {
                                attendance: att,
                                hafalans: hList,
                                ummi_jilid: uData ? uData.ummi_jilid || '' : '',
                                ummi_halaman: uData ? uData.ummi_halaman || '' : '',
                                materi: uData ? uData.materi || '' : '',
                                nilai: uData ? uData.nilai || '' : '',
                                tatap_muka: uData ? uData.tatap_muka || 1 : 1,
                                ummiHafalans: uHafalans
                            };
                        });
                    });
                },
                syncAyahLimits(hafalan) {
                    if (!hafalan.surah_id) return;
                    const details = this.surahDetails[hafalan.surah_id];
                    if (details) {
                        hafalan.ayah_start = 1;
                        hafalan.ayah_end = details.totalAyah;
                    }
                },
                syncUmmiAyahLimits(hafalan) {
                    if (!hafalan.surah_id) return;
                    const details = this.surahDetails[hafalan.surah_id];
                    if (details) {
                        hafalan.ayah = '1-' + details.totalAyah;
                    }
                },
                handleAttendanceChange(studentId, date, value) {
                    let cell = this.gridData[studentId].dates[date];
                    cell.attendance = value;
                    if (value !== 'hadir') {
                        // Clear fields if absent
                        cell.hafalans.forEach(h => {
                            h.surah_id = '';
                            h.ayah_start = '';
                            h.ayah_end = '';
                            h.score = '';
                        });
                        cell.ummi_jilid = '';
                        cell.ummi_halaman = '';
                        cell.materi = '';
                        cell.nilai = '';
                        cell.ummiHafalans.forEach(uh => {
                            uh.surah_id = '';
                            uh.ayah = '';
                        });
                    }
                }
            }));
        });
    </script>

    <div class="py-8" x-data="spreadsheetData">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- FILTER PANEL -->
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm rounded-xl p-5">
                <form method="GET" action="{{ route('spreadsheet-input.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label for="class_room_id" class="block text-sm font-medium text-gray-750 dark:text-zinc-300">
                            Kelas Halaqoh
                        </label>
                        <select id="class_room_id" name="class_room_id" x-model="selectedClass" onchange="this.form.submit()" class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                            @foreach ($classRooms as $class)
                                <option value="{{ $class->id }}" class="dark:bg-zinc-900">{{ $class->name }} ({{ $class->program?->name }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="month" class="block text-sm font-medium text-gray-750 dark:text-zinc-300">
                            Pilih Bulan & Tahun
                        </label>
                        <input type="month" id="month" name="month" x-model="selectedMonth" onchange="this.form.submit()" class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                    </div>
                    <div>
                        <label for="week" class="block text-sm font-medium text-gray-750 dark:text-zinc-300">
                            Pilih Pekan
                        </label>
                        <select id="week" name="week" onchange="this.form.submit()" class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                            <option value="all" {{ $selectedWeek === 'all' ? 'selected' : '' }} class="dark:bg-zinc-900">Semua Pekan (Scroll)</option>
                            @foreach ($weeksList as $index => $w)
                                <option value="{{ $index }}" {{ $selectedWeek == $index ? 'selected' : '' }} class="dark:bg-zinc-900">
                                    {{ $w['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-indigo-650 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition cursor-pointer">
                            🔍 Tampilkan Kelas
                        </button>
                    </div>
                </form>
            </div>

            <!-- TABS & SAVE ACTION -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 p-3 rounded-xl shadow-sm">
                <!-- Worksheet Tabs -->
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="button" @click="tab = 'hafalan'" :class="tab === 'hafalan' ? 'bg-indigo-600 text-white shadow' : 'bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 hover:bg-gray-200'" class="flex-1 sm:flex-none px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wider transition cursor-pointer">
                        📖 Lembar Setoran Al-Qur'an
                    </button>
                    <button type="button" @click="tab = 'ummi'" :class="tab === 'ummi' ? 'bg-emerald-600 text-white shadow' : 'bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 hover:bg-gray-200'" class="flex-1 sm:flex-none px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wider transition cursor-pointer">
                        🌱 Lembar Progres UMMI
                    </button>
                </div>

                <!-- Submit Button -->
                <div class="w-full sm:w-auto">
                    <button type="button" @click="document.getElementById('spreadsheet-form').submit()" class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-semibold shadow transition cursor-pointer">
                        💾 Simpan Perubahan Kelas
                    </button>
                </div>
            </div>

            @if ($students->isEmpty())
                <div class="p-8 text-center bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl">
                    <p class="text-sm text-gray-500 dark:text-zinc-400">Tidak ada santri aktif di kelas halaqoh terpilih.</p>
                </div>
            @else
                <!-- FORM UTAMA -->
                <form id="spreadsheet-form" method="POST" action="{{ route('spreadsheet-input.save') }}">
                    @csrf
                    <input type="hidden" name="class_room_id" :value="selectedClass">
                    <input type="hidden" name="month" :value="selectedMonth">
                    <input type="hidden" name="type" :value="tab">
                    <input type="hidden" name="week" value="{{ $selectedWeek }}">

                    <!-- ========================================== -->
                    <!-- DESKTOP SPREADSHEET VIEW (Laptop/PC)       -->
                    <!-- ========================================== -->
                    <div class="hidden md:block bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl overflow-x-auto shadow-sm">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 table-fixed border-collapse">
                            <thead class="bg-gray-50 dark:bg-zinc-850">
                                <tr>
                                    <th class="sticky left-0 z-20 bg-gray-50 dark:bg-zinc-850 px-4 py-3 text-left text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider w-48 border-r dark:border-zinc-800">
                                        Nama Santri
                                    </th>
                                    <template x-for="date in dates" :key="date">
                                        <th class="px-4 py-3 text-center text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider w-64 border-r dark:border-zinc-800">
                                            <span x-text="new Date(date).toLocaleDateString('id-ID', { weekday: 'short', day: 'numeric', month: 'short' })"></span>
                                        </th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-zinc-800 bg-white dark:bg-zinc-900">
                                <template x-for="student in students" :key="student.id">
                                    <tr class="hover:bg-gray-50/30 dark:hover:bg-zinc-850/10">
                                        <!-- Sticky Name Column -->
                                        <td class="sticky left-0 z-10 bg-white dark:bg-zinc-900 px-4 py-3 border-r dark:border-zinc-800 font-bold text-xs text-gray-900 dark:text-zinc-200 shadow-[2px_0_5px_rgba(0,0,0,0.05)]">
                                            <span x-text="student.name"></span>
                                            <span class="block text-[10px] text-gray-400 font-medium mt-0.5" x-text="student.tahfizh_level === 'ummi' ? 'Level: UMMI' : 'Level: ' + student.tahfizh_level"></span>
                                        </td>

                                        <!-- Date Columns -->
                                        <template x-for="date in dates" :key="date">
                                            <td class="p-3 border-r dark:border-zinc-800 align-top">
                                                <div class="space-y-2" x-data="{ cell: gridData[student.id].dates[date] }">
                                                    <!-- PRESENSI PILLS (ATAS) -->
                                                    <div class="flex items-center justify-between border-b dark:border-zinc-800 pb-2">
                                                        <div class="flex items-center gap-1 w-full justify-between">
                                                            <button type="button" @click="handleAttendanceChange(student.id, date, 'hadir')" :class="cell.attendance === 'hadir' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-transparent text-gray-400 border-gray-300 dark:border-zinc-700'" class="px-1.5 py-0.5 text-[10px] font-extrabold border rounded cursor-pointer transition-colors w-8 text-center">H</button>
                                                            <button type="button" @click="handleAttendanceChange(student.id, date, 'sakit')" :class="cell.attendance === 'sakit' ? 'bg-amber-500 text-white border-amber-500' : 'bg-transparent text-gray-400 border-gray-300 dark:border-zinc-700'" class="px-1.5 py-0.5 text-[10px] font-extrabold border rounded cursor-pointer transition-colors w-8 text-center">S</button>
                                                            <button type="button" @click="handleAttendanceChange(student.id, date, 'izin')" :class="cell.attendance === 'izin' ? 'bg-blue-500 text-white border-blue-500' : 'bg-transparent text-gray-400 border-gray-300 dark:border-zinc-700'" class="px-1.5 py-0.5 text-[10px] font-extrabold border rounded cursor-pointer transition-colors w-8 text-center">I</button>
                                                            <button type="button" @click="handleAttendanceChange(student.id, date, 'alpa')" :class="cell.attendance === 'alpa' ? 'bg-rose-600 text-white border-rose-600' : 'bg-transparent text-gray-400 border-gray-300 dark:border-zinc-700'" class="px-1.5 py-0.5 text-[10px] font-extrabold border rounded cursor-pointer transition-colors w-8 text-center">A</button>
                                                        </div>
                                                        <input type="hidden" :name="'records[' + student.id + '][dates][' + date + '][attendance]'" :value="cell.attendance">
                                                    </div>

                                                    <!-- INPUT FIELDS (DENGAN LOGIKA ACTIVE/DISABLED) -->
                                                    <div :class="cell.attendance !== 'hadir' ? 'opacity-30 pointer-events-none' : ''" class="transition-opacity space-y-2">
                                                        
                                                        <!-- TAB 1: SETORAN AL-QUR'AN (Sama untuk semua murid) -->
                                                        <div x-show="tab === 'hafalan'" class="space-y-2">
                                                            <template x-for="(h, hIndex) in cell.hafalans" :key="hIndex">
                                                                <div class="p-2 bg-gray-50/50 dark:bg-zinc-800/40 border border-gray-255 dark:border-zinc-800 rounded-lg relative space-y-1.5">
                                                                    <!-- Surah select -->
                                                                    <select :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][surah_id]'" x-model="h.surah_id" @change="syncAyahLimits(h)" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-2 py-1 dark:text-white">
                                                                        <option value="" class="dark:bg-zinc-900">Pilih Surah</option>
                                                                        <template x-for="surah in surahs" :key="surah.id">
                                                                            <option :value="surah.id" x-text="surah.number + '. ' + surah.name_latin" class="dark:bg-zinc-900"></option>
                                                                        </template>
                                                                    </select>
                                                                    <!-- Ayat range -->
                                                                    <div class="grid grid-cols-2 gap-1">
                                                                        <input type="number" :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][ayah_start]'" x-model.number="h.ayah_start" placeholder="Awal" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-2 py-0.5 dark:text-white">
                                                                        <input type="number" :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][ayah_end]'" x-model.number="h.ayah_end" placeholder="Akhir" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-2 py-0.5 dark:text-white">
                                                                    </div>
                                                                    <!-- Score & Status -->
                                                                    <div class="grid grid-cols-2 gap-1">
                                                                        <select :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][score]'" x-model="h.score" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-1 py-0.5 dark:text-white">
                                                                            <option value="" class="dark:bg-zinc-900">Nilai</option>
                                                                            <option value="95" class="dark:bg-zinc-900">A</option>
                                                                            <option value="85" class="dark:bg-zinc-900">B</option>
                                                                            <option value="75" class="dark:bg-zinc-900">C</option>
                                                                            <option value="65" class="dark:bg-zinc-900">D</option>
                                                                        </select>
                                                                        <select :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][status]'" x-model="h.status" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-1 py-0.5 dark:text-white">
                                                                            <option value="passed" class="dark:bg-zinc-900">Lulus</option>
                                                                            <option value="repeat" class="dark:bg-zinc-900">Ulang</option>
                                                                            <option value="needs_improvement" class="dark:bg-zinc-900">Revisi</option>
                                                                        </select>
                                                                    </div>
                                                                    <!-- Hidden tracking fields -->
                                                                    <input type="hidden" :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][id]'" :value="h.id">
                                                                    <input type="hidden" :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][submission_type]'" :value="h.submission_type">
                                                                    <!-- Remove button -->
                                                                    <template x-if="cell.hafalans.length > 1">
                                                                        <button type="button" @click="cell.hafalans.splice(hIndex, 1)" class="absolute -top-1.5 -right-1.5 bg-red-100 hover:bg-red-200 dark:bg-zinc-800 text-red-650 rounded-full w-4 h-4 flex items-center justify-center text-[10px] font-bold border border-red-200 dark:border-zinc-700 cursor-pointer">×</button>
                                                                    </template>
                                                                </div>
                                                            </template>
                                                            <!-- Add Surah Button -->
                                                            <button type="button" @click="cell.hafalans.push({ id: null, surah_id: '', ayah_start: '', ayah_end: '', score: '', status: 'passed', submission_type: 'new' })" :disabled="cell.attendance !== 'hadir'" class="w-full inline-flex items-center justify-center py-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-zinc-800 dark:hover:bg-zinc-750 text-indigo-650 dark:text-indigo-400 rounded-md text-[10px] font-bold border border-indigo-200 dark:border-zinc-800 transition cursor-pointer">
                                                                + Tambah Surat
                                                            </button>
                                                        </div>

                                                        <!-- TAB 2: PROGRES UMMI LENGKAP (Kondisional berdasarkan level murid) -->
                                                        <div x-show="tab === 'ummi'" class="space-y-2">
                                                            <!-- JIKA MURID ADALAH LEVEL UMMI -->
                                                            <template x-if="student.tahfizh_level === 'ummi'">
                                                                <div class="space-y-2">
                                                                    <!-- Jilid & Halaman -->
                                                                    <div class="grid grid-cols-2 gap-1">
                                                                        <select :name="'records[' + student.id + '][dates][' + date + '][ummi_jilid]'" x-model="cell.ummi_jilid" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-1 py-1 dark:text-white">
                                                                            <option value="" class="dark:bg-zinc-900">Buku/Jilid</option>
                                                                            <option value="Jilid 1" class="dark:bg-zinc-900">Jilid 1</option>
                                                                            <option value="Jilid 2" class="dark:bg-zinc-900">Jilid 2</option>
                                                                            <option value="Jilid 3" class="dark:bg-zinc-900">Jilid 3</option>
                                                                            <option value="Jilid 4" class="dark:bg-zinc-900">Jilid 4</option>
                                                                            <option value="Jilid 5" class="dark:bg-zinc-900">Jilid 5</option>
                                                                            <option value="Jilid 6" class="dark:bg-zinc-900">Jilid 6</option>
                                                                            <option value="Al-Qur'an" class="dark:bg-zinc-900">Al-Qur'an</option>
                                                                            <option value="Ghoroib" class="dark:bg-zinc-900">Ghoroib</option>
                                                                            <option value="Tajwid" class="dark:bg-zinc-900">Tajwid</option>
                                                                        </select>
                                                                        <input type="text" :name="'records[' + student.id + '][dates][' + date + '][ummi_halaman]'" x-model="cell.ummi_halaman" placeholder="Halaman" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-2 py-1 dark:text-white">
                                                                    </div>
                                                                    <!-- Materi & Nilai -->
                                                                    <div class="grid grid-cols-2 gap-1">
                                                                        <input type="text" :name="'records[' + student.id + '][dates][' + date + '][materi]'" x-model="cell.materi" placeholder="Materi" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-2 py-1 dark:text-white">
                                                                        <select :name="'records[' + student.id + '][dates][' + date + '][nilai]'" x-model="cell.nilai" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-1 py-1 dark:text-white">
                                                                            <option value="" class="dark:bg-zinc-900">Nilai</option>
                                                                            <option value="A+" class="dark:bg-zinc-900">A+</option>
                                                                            <option value="A" class="dark:bg-zinc-900">A</option>
                                                                            <option value="B+" class="dark:bg-zinc-900">B+</option>
                                                                            <option value="B" class="dark:bg-zinc-900">B</option>
                                                                            <option value="B-" class="dark:bg-zinc-900">B-</option>
                                                                            <option value="C+" class="dark:bg-zinc-900">C+</option>
                                                                            <option value="C" class="dark:bg-zinc-900">C</option>
                                                                            <option value="D" class="dark:bg-zinc-900">D</option>
                                                                        </select>
                                                                    </div>
                                                                    <!-- Hafalan list in UMMI cell -->
                                                                    <div class="border-t border-gray-150 dark:border-zinc-800 pt-2 space-y-1.5">
                                                                        <span class="text-[9px] font-bold text-gray-400 dark:text-zinc-500 block">SETORAN HAFALAN UMMI:</span>
                                                                        <template x-for="(h, hIndex) in cell.ummiHafalans" :key="hIndex">
                                                                            <div class="p-1.5 bg-gray-50/50 dark:bg-zinc-850 border border-gray-200 dark:border-zinc-800 rounded relative space-y-1">
                                                                                <select :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][surah_id]'" x-model="h.surah_id" @change="syncUmmiAyahLimits(h)" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[10px] px-1 py-0.5 dark:text-white">
                                                                                    <option value="" class="dark:bg-zinc-900">Pilih Surah</option>
                                                                                    <template x-for="surah in surahs" :key="surah.id">
                                                                                        <option :value="surah.id" x-text="surah.number + '. ' + surah.name_latin" class="dark:bg-zinc-900"></option>
                                                                                    </template>
                                                                                </select>
                                                                                <input type="text" :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][ayah]'" x-model="h.ayah" placeholder="Cth: 1-5" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[10px] px-2 py-0.5 dark:text-white">
                                                                                <input type="hidden" :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][id]'" :value="h.id">
                                                                                <!-- Remove button -->
                                                                                <template x-if="cell.ummiHafalans.length > 1">
                                                                                    <button type="button" @click="cell.ummiHafalans.splice(hIndex, 1)" class="absolute -top-1.5 -right-1.5 bg-red-150 text-red-650 rounded-full w-3.5 h-3.5 flex items-center justify-center text-[9px] font-bold border border-red-200 dark:border-zinc-800 cursor-pointer">×</button>
                                                                                </template>
                                                                            </div>
                                                                        </template>
                                                                        <button type="button" @click="cell.ummiHafalans.push({ id: null, surah_id: '', ayah: '' })" :disabled="cell.attendance !== 'hadir'" class="w-full py-0.5 bg-emerald-50 hover:bg-emerald-100 dark:bg-zinc-800 dark:hover:bg-zinc-750 text-emerald-650 dark:text-emerald-450 border border-emerald-200 dark:border-zinc-800 rounded text-[9px] font-extrabold cursor-pointer transition">
                                                                            + Hafalan
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </template>

                                                            <!-- JIKA MURID BUKAN LEVEL UMMI (HANYA HAFALAN BIASA) -->
                                                            <template x-if="student.tahfizh_level !== 'ummi'">
                                                                <div class="space-y-2">
                                                                    <template x-for="(h, hIndex) in cell.hafalans" :key="hIndex">
                                                                        <div class="p-2 bg-gray-50/50 dark:bg-zinc-800/40 border border-gray-255 dark:border-zinc-800 rounded-lg relative space-y-1.5">
                                                                            <!-- Surah select -->
                                                                            <select :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][surah_id]'" x-model="h.surah_id" @change="syncAyahLimits(h)" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-2 py-1 dark:text-white">
                                                                                <option value="" class="dark:bg-zinc-900">Pilih Surah</option>
                                                                                <template x-for="surah in surahs" :key="surah.id">
                                                                                    <option :value="surah.id" x-text="surah.number + '. ' + surah.name_latin" class="dark:bg-zinc-900"></option>
                                                                                </template>
                                                                            </select>
                                                                            <!-- Ayat range -->
                                                                            <div class="grid grid-cols-2 gap-1">
                                                                                <input type="number" :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][ayah_start]'" x-model.number="h.ayah_start" placeholder="Awal" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-2 py-0.5 dark:text-white">
                                                                                <input type="number" :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][ayah_end]'" x-model.number="h.ayah_end" placeholder="Akhir" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-2 py-0.5 dark:text-white">
                                                                            </div>
                                                                            <!-- Score & Status -->
                                                                            <div class="grid grid-cols-2 gap-1">
                                                                                <select :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][score]'" x-model="h.score" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-1 py-0.5 dark:text-white">
                                                                                    <option value="" class="dark:bg-zinc-900">Nilai</option>
                                                                                    <option value="95" class="dark:bg-zinc-900">A</option>
                                                                                    <option value="85" class="dark:bg-zinc-900">B</option>
                                                                                    <option value="75" class="dark:bg-zinc-900">C</option>
                                                                                    <option value="65" class="dark:bg-zinc-900">D</option>
                                                                                </select>
                                                                                <select :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][status]'" x-model="h.status" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] px-1 py-0.5 dark:text-white">
                                                                                    <option value="passed" class="dark:bg-zinc-900">Lulus</option>
                                                                                    <option value="repeat" class="dark:bg-zinc-900">Ulang</option>
                                                                                    <option value="needs_improvement" class="dark:bg-zinc-900">Revisi</option>
                                                                                </select>
                                                                            </div>
                                                                            <input type="hidden" :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][id]'" :value="h.id">
                                                                            <input type="hidden" :name="'records[' + student.id + '][dates][' + date + '][hafalans][' + hIndex + '][submission_type]'" :value="h.submission_type">
                                                                            <!-- Remove button -->
                                                                            <template x-if="cell.hafalans.length > 1">
                                                                                <button type="button" @click="cell.hafalans.splice(hIndex, 1)" class="absolute -top-1.5 -right-1.5 bg-red-100 hover:bg-red-200 dark:bg-zinc-800 text-red-650 rounded-full w-4 h-4 flex items-center justify-center text-[10px] font-bold border border-red-200 dark:border-zinc-700 cursor-pointer">×</button>
                                                                            </template>
                                                                        </div>
                                                                    </template>
                                                                    <button type="button" @click="cell.hafalans.push({ id: null, surah_id: '', ayah_start: '', ayah_end: '', score: '', status: 'passed', submission_type: 'new' })" :disabled="cell.attendance !== 'hadir'" class="w-full inline-flex items-center justify-center py-1 bg-indigo-50 hover:bg-indigo-100 dark:bg-zinc-800 dark:hover:bg-zinc-750 text-indigo-650 dark:text-indigo-400 rounded-md text-[10px] font-bold border border-indigo-200 dark:border-zinc-800 transition cursor-pointer">
                                                                        + Tambah Surat
                                                                    </button>
                                                                </div>
                                                            </template>
                                                        </div>

                                                    </div>
                                                </div>
                                            </td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- ========================================== -->
                    <!-- MOBILE VIEW (Layar HP / Tegak)             -->
                    <!-- ========================================== -->
                    <div class="md:hidden space-y-4">
                        <!-- Date Selector for Mobile -->
                        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 p-4 rounded-xl shadow-sm space-y-2">
                            <label for="mobile_date" class="block text-xs font-bold text-gray-500 uppercase tracking-wider">
                                📅 Pilih Tanggal Input:
                            </label>
                            <select id="mobile_date" x-model="selectedMobileDate" class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:indigo-500 dark:text-white">
                                <template x-for="date in dates" :key="date">
                                    <option :value="date" x-text="new Date(date).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })" class="dark:bg-zinc-900"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Cards per Student -->
                        <div class="space-y-4">
                            <template x-for="student in students" :key="student.id">
                                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-xl p-4 shadow-sm space-y-4" x-data="{ cell: gridData[student.id].dates[selectedMobileDate] }">
                                    <!-- Name Header -->
                                    <div class="flex items-center justify-between border-b dark:border-zinc-800 pb-2.5">
                                        <div>
                                            <h4 class="font-extrabold text-sm text-gray-900 dark:text-white" x-text="student.name"></h4>
                                            <p class="text-[10px] text-gray-400 mt-0.5" x-text="student.className ? student.className : '-'"></p>
                                        </div>
                                        <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 font-bold text-[10px]" x-text="student.tahfizh_level === 'ummi' ? 'Level: UMMI' : 'Level: ' + student.tahfizh_level"></span>
                                    </div>

                                    <!-- Attendance Selection -->
                                    <div class="space-y-1.5">
                                        <span class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider">Status Kehadiran</span>
                                        <div class="grid grid-cols-4 gap-2">
                                            <button type="button" @click="handleAttendanceChange(student.id, selectedMobileDate, 'hadir')" :class="cell.attendance === 'hadir' ? 'bg-emerald-600 text-white border-emerald-600 font-extrabold' : 'bg-transparent text-gray-500 border-gray-300 dark:border-zinc-700'" class="py-2 text-xs border rounded-lg cursor-pointer text-center transition">Hadir</button>
                                            <button type="button" @click="handleAttendanceChange(student.id, selectedMobileDate, 'sakit')" :class="cell.attendance === 'sakit' ? 'bg-amber-500 text-white border-amber-500 font-extrabold' : 'bg-transparent text-gray-500 border-gray-300 dark:border-zinc-700'" class="py-2 text-xs border rounded-lg cursor-pointer text-center transition">Sakit</button>
                                            <button type="button" @click="handleAttendanceChange(student.id, selectedMobileDate, 'izin')" :class="cell.attendance === 'izin' ? 'bg-blue-500 text-white border-blue-500 font-extrabold' : 'bg-transparent text-gray-500 border-gray-300 dark:border-zinc-700'" class="py-2 text-xs border rounded-lg cursor-pointer text-center transition">Izin</button>
                                            <button type="button" @click="handleAttendanceChange(student.id, selectedMobileDate, 'alpa')" :class="cell.attendance === 'alpa' ? 'bg-rose-600 text-white border-rose-600 font-extrabold' : 'bg-transparent text-gray-500 border-gray-300 dark:border-zinc-700'" class="py-2 text-xs border rounded-lg cursor-pointer text-center transition">Alpa</button>
                                        </div>
                                    </div>

                                    <!-- Form Inputs (Locked if absent) -->
                                    <div :class="cell.attendance !== 'hadir' ? 'opacity-30 pointer-events-none' : ''" class="transition-opacity space-y-4">
                                        
                                        <!-- MOBILE TAB 1: SETORAN AL-QUR'AN -->
                                        <div x-show="tab === 'hafalan'" class="space-y-3">
                                            <template x-for="(h, hIndex) in cell.hafalans" :key="hIndex">
                                                <div class="bg-gray-50/50 dark:bg-zinc-800/40 p-3 rounded-lg border border-gray-255 dark:border-zinc-800 relative space-y-3">
                                                    <div>
                                                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Surah</label>
                                                        <select :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][surah_id]'" x-model="h.surah_id" @change="syncAyahLimits(h)" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1.5 dark:text-white">
                                                            <option value="" class="dark:bg-zinc-900">Pilih Surah</option>
                                                            <template x-for="surah in surahs" :key="surah.id">
                                                                <option :value="surah.id" x-text="surah.number + '. ' + surah.name_latin" class="dark:bg-zinc-900"></option>
                                                            </template>
                                                        </select>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Ayat Mulai</label>
                                                            <input type="number" :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][ayah_start]'" x-model.number="h.ayah_start" placeholder="Awal" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Ayat Akhir</label>
                                                            <input type="number" :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][ayah_end]'" x-model.number="h.ayah_end" placeholder="Akhir" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                        </div>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Nilai</label>
                                                            <select :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][score]'" x-model="h.score" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                                <option value="" class="dark:bg-zinc-900">Pilih Nilai</option>
                                                                <option value="95" class="dark:bg-zinc-900">A</option>
                                                                <option value="85" class="dark:bg-zinc-900">B</option>
                                                                <option value="75" class="dark:bg-zinc-900">C</option>
                                                                <option value="65" class="dark:bg-zinc-900">D</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Status</label>
                                                            <select :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][status]'" x-model="h.status" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                                <option value="passed" class="dark:bg-zinc-900">Lulus</option>
                                                                <option value="repeat" class="dark:bg-zinc-900">Ulang</option>
                                                                <option value="needs_improvement" class="dark:bg-zinc-900">Revisi</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <!-- Hidden variables -->
                                                    <input type="hidden" :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][id]'" :value="h.id">
                                                    <!-- Delete button -->
                                                    <template x-if="cell.hafalans.length > 1">
                                                        <button type="button" @click="cell.hafalans.splice(hIndex, 1)" class="absolute top-2 right-2 text-rose-650 text-xs font-bold bg-white dark:bg-zinc-800 border border-gray-255 dark:border-zinc-700 rounded-full w-5 h-5 flex items-center justify-center cursor-pointer shadow-sm">×</button>
                                                    </template>
                                                </div>
                                            </template>
                                            <button type="button" @click="cell.hafalans.push({ id: null, surah_id: '', ayah_start: '', ayah_end: '', score: '', status: 'passed', submission_type: 'new' })" :disabled="cell.attendance !== 'hadir'" class="w-full py-2 bg-indigo-50 dark:bg-zinc-800 text-indigo-650 dark:text-indigo-400 border border-indigo-200 dark:border-zinc-700 rounded-lg text-xs font-bold transition cursor-pointer">
                                                + Tambah Surat Setoran
                                            </button>
                                        </div>

                                        <!-- MOBILE TAB 2: PROGRES UMMI LENGKAP -->
                                        <div x-show="tab === 'ummi'" class="space-y-3">
                                            <!-- JIKA LEVEL UMMI -->
                                            <template x-if="student.tahfizh_level === 'ummi'">
                                                <div class="space-y-3">
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Buku/Jilid</label>
                                                            <select :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][ummi_jilid]'" x-model="cell.ummi_jilid" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1.5 dark:text-white">
                                                                <option value="" class="dark:bg-zinc-900">Buku/Jilid</option>
                                                                <option value="Jilid 1" class="dark:bg-zinc-900">Jilid 1</option>
                                                                <option value="Jilid 2" class="dark:bg-zinc-900">Jilid 2</option>
                                                                <option value="Jilid 3" class="dark:bg-zinc-900">Jilid 3</option>
                                                                <option value="Jilid 4" class="dark:bg-zinc-900">Jilid 4</option>
                                                                <option value="Jilid 5" class="dark:bg-zinc-900">Jilid 5</option>
                                                                <option value="Jilid 6" class="dark:bg-zinc-900">Jilid 6</option>
                                                                <option value="Al-Qur'an" class="dark:bg-zinc-900">Al-Qur'an</option>
                                                                <option value="Ghoroib" class="dark:bg-zinc-900">Ghoroib</option>
                                                                <option value="Tajwid" class="dark:bg-zinc-900">Tajwid</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Halaman</label>
                                                            <input type="text" :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][ummi_halaman]'" x-model="cell.ummi_halaman" placeholder="Hal" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1.5 dark:text-white">
                                                        </div>
                                                    </div>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Materi</label>
                                                            <input type="text" :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][materi]'" x-model="cell.materi" placeholder="Materi" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1.5 dark:text-white">
                                                        </div>
                                                        <div>
                                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Nilai</label>
                                                            <select :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][nilai]'" x-model="cell.nilai" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1.5 dark:text-white">
                                                                <option value="" class="dark:bg-zinc-900">Pilih Nilai</option>
                                                                <option value="A+" class="dark:bg-zinc-900">A+</option>
                                                                <option value="A" class="dark:bg-zinc-900">A</option>
                                                                <option value="B+" class="dark:bg-zinc-900">B+</option>
                                                                <option value="B" class="dark:bg-zinc-900">B</option>
                                                                <option value="B-" class="dark:bg-zinc-900">B-</option>
                                                                <option value="C+" class="dark:bg-zinc-900">C+</option>
                                                                <option value="C" class="dark:bg-zinc-900">C</option>
                                                                <option value="D" class="dark:bg-zinc-900">D</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <!-- UMMI hafalan on mobile -->
                                                    <div class="border-t border-gray-150 dark:border-zinc-800 pt-3.5 space-y-2">
                                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Setoran Hafalan UMMI</span>
                                                        <template x-for="(h, hIndex) in cell.ummiHafalans" :key="hIndex">
                                                            <div class="bg-gray-50/50 dark:bg-zinc-855 p-3 rounded-lg border border-gray-200 dark:border-zinc-800 relative space-y-2">
                                                                <select :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][surah_id]'" x-model="h.surah_id" @change="syncUmmiAyahLimits(h)" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                                    <option value="" class="dark:bg-zinc-900">Pilih Surah</option>
                                                                    <template x-for="surah in surahs" :key="surah.id">
                                                                        <option :value="surah.id" x-text="surah.number + '. ' + surah.name_latin" class="dark:bg-zinc-900"></option>
                                                                    </template>
                                                                </select>
                                                                <input type="text" :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][ayah]'" x-model="h.ayah" placeholder="Ayat (cth: 1-5)" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                                <input type="hidden" :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][id]'" :value="h.id">
                                                                <!-- Remove button -->
                                                                <template x-if="cell.ummiHafalans.length > 1">
                                                                    <button type="button" @click="cell.ummiHafalans.splice(hIndex, 1)" class="absolute top-2 right-2 text-rose-655 text-xs font-bold bg-white dark:bg-zinc-800 border border-gray-250 dark:border-zinc-700 rounded-full w-5 h-5 flex items-center justify-center cursor-pointer shadow-sm">×</button>
                                                                </template>
                                                            </div>
                                                        </template>
                                                        <button type="button" @click="cell.ummiHafalans.push({ id: null, surah_id: '', ayah: '' })" :disabled="cell.attendance !== 'hadir'" class="w-full py-1.5 bg-emerald-50 hover:bg-emerald-100 dark:bg-zinc-800 text-emerald-650 dark:text-emerald-455 border border-emerald-200 dark:border-zinc-700 rounded-lg text-xs font-bold transition cursor-pointer">
                                                            + Tambah Hafalan
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>

                                            <!-- JIKA BUKAN LEVEL UMMI -->
                                            <template x-if="student.tahfizh_level !== 'ummi'">
                                                <div class="space-y-3">
                                                    <template x-for="(h, hIndex) in cell.hafalans" :key="hIndex">
                                                        <div class="bg-gray-50/50 dark:bg-zinc-800/40 p-3 rounded-lg border border-gray-255 dark:border-zinc-800 relative space-y-3">
                                                            <div>
                                                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Surah</label>
                                                                <select :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][surah_id]'" x-model="h.surah_id" @change="syncAyahLimits(h)" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1.5 dark:text-white">
                                                                    <option value="" class="dark:bg-zinc-900">Pilih Surah</option>
                                                                    <template x-for="surah in surahs" :key="surah.id">
                                                                        <option :value="surah.id" x-text="surah.number + '. ' + surah.name_latin" class="dark:bg-zinc-900"></option>
                                                                    </template>
                                                                </select>
                                                            </div>
                                                            <div class="grid grid-cols-2 gap-2">
                                                                <div>
                                                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Ayat Mulai</label>
                                                                    <input type="number" :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][ayah_start]'" x-model.number="h.ayah_start" placeholder="Awal" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                                </div>
                                                                <div>
                                                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Ayat Akhir</label>
                                                                    <input type="number" :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][ayah_end]'" x-model.number="h.ayah_end" placeholder="Akhir" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                                </div>
                                                            </div>
                                                            <div class="grid grid-cols-2 gap-2">
                                                                <div>
                                                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Nilai</label>
                                                                    <select :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][score]'" x-model="h.score" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                                        <option value="" class="dark:bg-zinc-900">Pilih Nilai</option>
                                                                        <option value="95" class="dark:bg-zinc-900">A</option>
                                                                        <option value="85" class="dark:bg-zinc-900">B</option>
                                                                        <option value="75" class="dark:bg-zinc-900">C</option>
                                                                        <option value="65" class="dark:bg-zinc-900">D</option>
                                                                    </select>
                                                                </div>
                                                                <div>
                                                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">Status</label>
                                                                    <select :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][status]'" x-model="h.status" :disabled="cell.attendance !== 'hadir'" class="block w-full rounded border-gray-300 dark:border-zinc-700 bg-transparent text-xs py-1 dark:text-white">
                                                                        <option value="passed" class="dark:bg-zinc-900">Lulus</option>
                                                                        <option value="repeat" class="dark:bg-zinc-900">Ulang</option>
                                                                        <option value="needs_improvement" class="dark:bg-zinc-900">Revisi</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <input type="hidden" :name="'records[' + student.id + '][dates][' + selectedMobileDate + '][hafalans][' + hIndex + '][id]'" :value="h.id">
                                                            <!-- Delete button -->
                                                            <template x-if="cell.hafalans.length > 1">
                                                                <button type="button" @click="cell.hafalans.splice(hIndex, 1)" class="absolute top-2 right-2 text-rose-650 text-xs font-bold bg-white dark:bg-zinc-800 border border-gray-255 dark:border-zinc-700 rounded-full w-5 h-5 flex items-center justify-center cursor-pointer shadow-sm">×</button>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <button type="button" @click="cell.hafalans.push({ id: null, surah_id: '', ayah_start: '', ayah_end: '', score: '', status: 'passed', submission_type: 'new' })" :disabled="cell.attendance !== 'hadir'" class="w-full py-2 bg-indigo-50 dark:bg-zinc-800 text-indigo-650 dark:text-indigo-400 border border-indigo-200 dark:border-zinc-700 rounded-lg text-xs font-bold transition cursor-pointer">
                                                        + Tambah Surat Setoran
                                                    </button>
                                                </div>
                                            </template>
                                        </div>

                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </form>
            @endif

        </div>
    </div>
</x-app-layout>
