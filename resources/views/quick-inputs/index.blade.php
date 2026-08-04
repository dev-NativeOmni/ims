<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Input Cepat
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Catat hafalan dan murajaah santri dalam satu halaman.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit"
                        form="hafalan-form"
                        style="background-color: #059669; color: #ffffff;"
                        class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm hover:opacity-90">
                Simpan Hafalan
                </button>

                <button type="submit"
                        form="murajaah-form"
                        style="background-color: #4f46e5; color: #ffffff;"
                        class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm hover:opacity-90">
                    Simpan Murajaah
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <p class="font-semibold mb-2">Ada input yang belum valid:</p>
                    <ul class="list-disc ps-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

    <script>
        window.latestTatapMukaPerClass = @json($latestTatapMukaPerClass ?? []);
    </script>

             <div x-data="{
                inputMode: '{{ old('input_mode', 'reguler') }}',
                selectedClass: '',
                selectedStudentId: '{{ old('student_id', request('student_id', '')) }}',
                selectedDate: '{{ now()->format('Y-m-d') }}',
                tatapMuka: {{ old('tatap_muka', 1) }},
                latestTatapMukaPerClass: window.latestTatapMukaPerClass || {},
                attendances: {},
                isLoadingAttendance: false,
                fetchAttendances() {
                    if (!this.selectedClass) {
                        this.attendances = {};
                        return;
                    }
                    this.isLoadingAttendance = true;
                    axios.get('/attendances/check', {
                        params: {
                            class_room_id: this.selectedClass,
                            date: this.selectedDate
                        }
                    })
                    .then(res => {
                        if (res.data && res.data.attendances) {
                            let map = {};
                            res.data.attendances.forEach(att => {
                                map[att.id] = att.status;
                            });
                            this.attendances = map;
                        }
                    })
                    .catch(err => console.error('Gagal mengambil presensi:', err))
                    .finally(() => {
                        this.isLoadingAttendance = false;
                    });
                },
                saveAttendance(studentId, status) {
                    this.attendances[studentId] = status;
                    axios.post('/attendances/save', {
                        student_id: studentId,
                        class_room_id: this.selectedClass,
                        tanggal: this.selectedDate,
                        status: status
                    })
                    .then(res => {
                        if (!res.data.success) {
                            alert('Gagal menyimpan presensi: ' + res.data.message);
                        }
                    })
                    .catch(err => {
                        console.error('Gagal menyimpan presensi:', err);
                        alert('Gagal menyimpan presensi. Pastikan koneksi internet aktif.');
                    });
                },
                surahStartHafalan: '{{ old('surah_id', '') }}',
                surahEndHafalan: '{{ old('surah_end_id', '') }}',
                surahStartMurajaah: '{{ old('surah_id', '') }}',
                surahEndMurajaah: '{{ old('surah_end_id', '') }}',
                ayahStart: '{{ old('ayah_start', '') }}',
                ayahEnd: '{{ old('ayah_end', '') }}',
                ummiHafalans: [{ surah_id: '', ayah: '' }],
                allStudents: [
                    @foreach($students as $student)
                        { id: {{ $student->id }}, name: '{{ addslashes($student->name) }}', classId: '{{ $student->class_room_id }}', className: '{{ $student->classRoom?->name ?? '' }}', level: '{{ $student->tahfizh_level }}' },
                    @endforeach
                ],
                surahDetails: {
                    @foreach ($surahs as $surah)
                        '{{ $surah->id }}': { number: {{ $surah->number }}, totalAyah: {{ $surah->total_ayah }}, name: '{{ addslashes($surah->name_latin) }}' },
                    @endforeach
                },
                calculateLines(surahId, startAyah, endAyah) {
                    if (!surahId || !startAyah || !endAyah) return 0;
                    const details = this.surahDetails[surahId];
                    if (!details) return 0;
                    const surahNumber = details.number;
                    const totalAyah = details.totalAyah;

                    const start = parseInt(startAyah);
                    const end = parseInt(endAyah);
                    if (isNaN(start) || isNaN(end) || start > end) return 0;

                    if (window.quranPageMapping) {
                        const keyStart = surahNumber + ':' + start;
                        const keyEnd = surahNumber + ':' + end;

                        const pageStart = window.quranPageMapping[keyStart];
                        const pageEnd = window.quranPageMapping[keyEnd];

                        if (pageStart !== undefined && pageEnd !== undefined) {
                            if (pageStart === pageEnd) {
                                let totalVersesOnPage = 0;
                                for (let k in window.quranPageMapping) {
                                    if (window.quranPageMapping[k] === pageStart) {
                                        totalVersesOnPage++;
                                    }
                                }
                                const versesInSetoran = end - start + 1;
                                const pageCapacity = (pageStart === 1 || pageStart === 2) ? 7.0 : 15.0;
                                const lines = (versesInSetoran / Math.max(1, totalVersesOnPage)) * pageCapacity;
                                return Math.round(lines * 10) / 10;
                            } else {
                                // Start Page
                                let totalVersesOnStartPage = 0;
                                let versesInSetoranStartPage = 0;
                                for (let k in window.quranPageMapping) {
                                    if (window.quranPageMapping[k] === pageStart) {
                                        totalVersesOnStartPage++;
                                        const parts = k.split(':');
                                        if (parseInt(parts[0]) === surahNumber && parseInt(parts[1]) >= start) {
                                            versesInSetoranStartPage++;
                                        }
                                    }
                                }
                                const startPageCapacity = (pageStart === 1 || pageStart === 2) ? 7.0 : 15.0;
                                const startPageLines = (versesInSetoranStartPage / Math.max(1, totalVersesOnStartPage)) * startPageCapacity;

                                // End Page
                                let totalVersesOnEndPage = 0;
                                let versesInSetoranEndPage = 0;
                                for (let k in window.quranPageMapping) {
                                    if (window.quranPageMapping[k] === pageEnd) {
                                        totalVersesOnEndPage++;
                                        const parts = k.split(':');
                                        if (parseInt(parts[0]) === surahNumber && parseInt(parts[1]) <= end) {
                                            versesInSetoranEndPage++;
                                        }
                                    }
                                }
                                const endPageCapacity = (pageEnd === 1 || pageEnd === 2) ? 7.0 : 15.0;
                                const endPageLines = (versesInSetoranEndPage / Math.max(1, totalVersesOnEndPage)) * endPageCapacity;

                                // Middle Pages
                                let middleLines = 0.0;
                                for (let p = pageStart + 1; p < pageEnd; p++) {
                                    const pageCapacity = (p === 1 || p === 2) ? 7.0 : 15.0;
                                    middleLines += pageCapacity;
                                }

                                return Math.round((startPageLines + middleLines + endPageLines) * 10) / 10;
                            }
                        }
                    }

                    const pages = {
                        1: 1.0, 2: 48.0, 3: 27.0, 4: 29.0, 5: 22.0, 6: 23.0, 7: 26.0, 8: 10.0, 9: 21.0, 10: 13.0,
                        11: 14.0, 12: 12.0, 13: 7.0, 14: 7.0, 15: 6.0, 16: 15.0, 17: 12.0, 18: 12.0, 19: 7.0, 20: 10.0,
                        21: 10.0, 22: 10.0, 23: 8.0, 24: 10.0, 25: 6.0, 26: 11.0, 27: 9.0, 28: 11.0, 29: 7.0, 30: 6.0,
                        31: 4.0, 32: 3.0, 33: 9.0, 34: 6.0, 35: 6.0, 36: 6.0, 37: 7.0, 38: 5.0, 39: 8.0, 40: 9.0,
                        41: 6.0, 42: 6.0, 43: 7.0, 44: 3.0, 45: 3.0, 46: 4.0, 47: 4.0, 48: 4.0, 49: 2.5, 50: 3.0,
                        51: 2.5, 52: 2.5, 53: 2.5, 54: 2.5, 55: 3.0, 56: 3.0, 57: 4.0, 58: 3.0, 59: 3.0, 60: 2.5,
                        61: 1.5, 62: 1.5, 63: 1.5, 64: 2.0, 65: 2.0, 66: 2.0, 67: 2.5, 68: 2.0, 69: 2.0, 70: 2.0,
                        71: 1.5, 72: 2.0, 73: 1.5, 74: 2.0, 75: 2.0, 76: 2.0, 77: 2.0, 78: 2.0, 79: 2.0, 80: 1.5,
                        81: 1.0, 82: 1.0, 83: 2.0, 84: 1.0, 85: 1.0, 86: 1.0, 87: 1.0, 88: 1.0, 89: 1.5, 90: 1.0,
                        91: 1.0, 92: 1.0, 93: 0.5, 94: 0.5, 95: 0.5, 96: 1.0, 97: 0.5, 98: 1.0, 99: 0.5, 100: 0.5,
                        101: 0.5, 102: 0.5, 103: 0.3, 104: 0.5, 105: 0.3, 106: 0.3, 107: 0.5, 108: 0.3, 109: 0.5, 110: 0.3,
                        111: 0.3, 112: 0.3, 113: 0.3, 114: 0.3
                    };
                    const pageCount = pages[surahNumber] || 1.0;
                    const totalLines = pageCount * 15.0;
                    const versesCount = Math.max(1, end - start + 1);
                    const ratio = Math.min(1.0, versesCount / totalAyah);
                    return Math.round(ratio * totalLines * 10) / 10;
                },
                parseAyahRange(ayahStr) {
                    if (!ayahStr) return null;
                    const clean = ayahStr.toString().replace(/\s+/g, '');
                    const matchRange = clean.match(/^(\d+)-(\d+)$/);
                    if (matchRange) {
                        return { start: parseInt(matchRange[1]), end: parseInt(matchRange[2]) };
                    }
                    const matchSingle = clean.match(/^(\d+)$/);
                    if (matchSingle) {
                        return { start: parseInt(matchSingle[1]), end: parseInt(matchSingle[1]) };
                    }
                    return null;
                },
                calculateUmmiLines(surahId, ayahStr) {
                    if (!surahId || !ayahStr) return 0;
                    const range = this.parseAyahRange(ayahStr);
                    if (!range) return 0;
                    return this.calculateLines(surahId, range.start, range.end);
                },
                 get filteredStudents() {
                    if (!this.selectedClass) return this.allStudents;
                    return this.allStudents.filter(s => {
                        if (s.classId != this.selectedClass) return false;
                        return this.attendances[s.id] === 'hadir';
                    });
                },
                get isUmmiSelected() {
                    return this.inputMode === 'ummi';
                }
             }" x-init="
                fetch('{{ asset('quran_page_mapping.json') }}')
                    .then(res => res.json())
                    .then(data => { window.quranPageMapping = data; })
                    .catch(err => console.error('Gagal memuat peta halaman Quran:', err));

                if (selectedStudentId) {
                    let s = allStudents.find(x => x.id == selectedStudentId);
                    if (s) {
                        selectedClass = s.classId;
                        if (s.level === 'ummi') {
                            inputMode = 'ummi';
                        }
                    }
                }

                if (selectedClass) {
                    tatapMuka = (latestTatapMukaPerClass[selectedClass] || 0) + 1;
                    fetchAttendances();
                }

                $watch('selectedClass', (val) => {
                    if (val) {
                        tatapMuka = (latestTatapMukaPerClass[val] || 0) + 1;
                    }
                    fetchAttendances();
                });

                $watch('selectedDate', (val) => {
                    fetchAttendances();
                });
            " class="space-y-6">

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-xl shadow-sm border p-5">
                        <p class="text-sm text-gray-500" x-text="inputMode === 'ummi' ? 'Santri UMMI Aktif' : 'Santri Aktif Bisa Diinput'"></p>
                        <p class="mt-2 text-3xl font-bold text-gray-900" x-text="inputMode === 'ummi' ? allStudents.filter(s => s.level === 'ummi').length : allStudents.length"></p>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border p-5">
                        <p class="text-sm text-gray-500" x-text="inputMode === 'ummi' ? 'Target Jilid' : 'Data Surah'"></p>
                        <p class="mt-2 text-3xl font-bold text-gray-900" x-text="inputMode === 'ummi' ? '6 Jilid + Quran' : '{{ $surahs->count() }}'"></p>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border p-5">
                        <p class="text-sm text-gray-500">Mode Input</p>
                        <p class="mt-2 text-xl font-bold text-gray-900" x-text="inputMode === 'ummi' ? 'Tahsin UMMI' : 'Hafalan + Murajaah'"></p>
                    </div>
                </div>

                <!-- Filter Kelas Global & Toggle Mode -->
                <div class="bg-white rounded-xl shadow-sm border p-5 flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <div class="w-full max-w-md">
                        <label for="global_class_filter" class="block text-sm font-semibold text-gray-700 mb-1">
                            Pilih Kelas untuk Menyaring Santri:
                        </label>
                        <select id="global_class_filter" 
                                x-model="selectedClass" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Semua Kelas (Tampilkan Semua Santri)</option>
                            @foreach ($classRooms as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Toggle Mode Input -->
                    <div class="flex items-center gap-1 p-1 bg-gray-100 rounded-lg w-full md:w-auto self-start md:self-auto">
                        <button type="button" 
                                @click="inputMode = 'reguler'" 
                                :class="inputMode === 'reguler' ? 'bg-white text-gray-900 shadow-sm font-semibold' : 'text-gray-500 hover:text-gray-900'"
                                class="flex-1 md:flex-initial px-4 py-2 text-xs rounded-md transition-all duration-150 whitespace-nowrap">
                            Tahfidz & Murajaah (Per Santri)
                        </button>
                        <button type="button" 
                                @click="inputMode = 'ummi'" 
                                :class="inputMode === 'ummi' ? 'bg-white text-gray-900 shadow-sm font-semibold' : 'text-gray-500 hover:text-gray-900'"
                                class="flex-1 md:flex-initial px-4 py-2 text-xs rounded-md transition-all duration-150 whitespace-nowrap">
                            Tahsin UMMI (Per Kelas Halaqoh)
                        </button>
                    </div>
                </div>

                <!-- TABEL PRESENSI HARIAN -->
                <div x-show="selectedClass" class="bg-white border rounded-xl p-5 shadow-sm space-y-4" style="display: none;">
                    <div class="border-b pb-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div>
                            <h3 class="font-bold text-gray-900 text-sm">Tabel Presensi Kelas</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Isi kehadiran santri terlebih dahulu untuk hari ini. Hanya murid yang ditandai <strong>Hadir</strong> yang dapat dipilih untuk setoran hafalan.</p>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span>Tanggal Presensi:</span>
                            <span class="font-bold text-gray-800" x-text="selectedDate"></span>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div x-show="isLoadingAttendance" class="py-8 text-center text-sm text-gray-500">
                        <svg class="animate-spin h-5 w-5 text-indigo-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Memuat data presensi...
                    </div>

                    <!-- Attendance Table -->
                    <div x-show="!isLoadingAttendance" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-2.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-12">No</th>
                                    <th scope="col" class="px-4 py-2.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nama Santri</th>
                                    <th scope="col" class="px-4 py-2.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-20">Hadir</th>
                                    <th scope="col" class="px-4 py-2.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-20">Sakit</th>
                                    <th scope="col" class="px-4 py-2.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-20">Izin</th>
                                    <th scope="col" class="px-4 py-2.5 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-20">Alpa</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-150">
                                <template x-for="(student, index) in allStudents.filter(s => s.classId == selectedClass)" :key="student.id">
                                    <tr class="hover:bg-gray-55/50">
                                        <td class="px-4 py-2 text-xs font-bold text-gray-400" x-text="index + 1"></td>
                                        <td class="px-4 py-2 text-xs font-bold text-gray-900" x-text="student.name"></td>
                                        <td class="px-4 py-2 text-center">
                                            <input type="radio" 
                                                   :name="'attendance_global_' + student.id" 
                                                   value="hadir" 
                                                   :checked="attendances[student.id] === 'hadir'"
                                                   @change="saveAttendance(student.id, 'hadir')"
                                                   class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500 cursor-pointer">
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <input type="radio" 
                                                   :name="'attendance_global_' + student.id" 
                                                   value="sakit" 
                                                   :checked="attendances[student.id] === 'sakit'"
                                                   @change="saveAttendance(student.id, 'sakit')"
                                                   class="h-4 w-4 text-yellow-600 border-gray-300 focus:ring-yellow-500 cursor-pointer">
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <input type="radio" 
                                                   :name="'attendance_global_' + student.id" 
                                                   value="izin" 
                                                   :checked="attendances[student.id] === 'izin'"
                                                   @change="saveAttendance(student.id, 'izin')"
                                                   class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500 cursor-pointer">
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <input type="radio" 
                                                   :name="'attendance_global_' + student.id" 
                                                   value="alpa" 
                                                   :checked="attendances[student.id] === 'alpa'"
                                                   @change="saveAttendance(student.id, 'alpa')"
                                                   class="h-4 w-4 text-red-650 border-gray-300 focus:ring-red-500 cursor-pointer">
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-show="!isUmmiSelected">

                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="px-6 py-4 border-b flex items-center justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-gray-900">Input Cepat Hafalan</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Untuk setoran baru, lanjutan, atau perbaikan.
                            </p>
                        </div>

                        <button type="submit"
                                form="hafalan-form"
                                style="background-color: #059669; color: #ffffff;"
                                class="shrink-0 inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm hover:opacity-90">
                            Simpan Hafalan
                        </button>
                    </div>

                    <form id="hafalan-form" method="POST" action="{{ route('quick-inputs.hafalan.store') }}" class="p-6 space-y-4">
                        @csrf

                        <div>
                            <label for="hafalan_student_id" class="block text-sm font-medium text-gray-700">
                                Santri
                            </label>
                            <select id="hafalan_student_id"
                                    name="student_id"
                                    required
                                    x-model="selectedStudentId"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Pilih santri</option>
                                <template x-for="student in filteredStudents" :key="student.id">
                                    <option :value="student.id" x-text="student.name + (student.className ? ' — ' + student.className : '')" :selected="student.id == selectedStudentId"></option>
                                </template>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="hafalan_surah_id" class="block text-sm font-medium text-gray-700">
                                    Surah Mulai
                                </label>
                                <select id="hafalan_surah_id"
                                        name="surah_id"
                                        required
                                        x-model="surahStartHafalan"
                                        @change="if (!surahEndHafalan || surahEndHafalan == '') { surahEndHafalan = surahStartHafalan; }"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Pilih surah mulai</option>
                                    @foreach ($surahs as $surah)
                                        <option value="{{ $surah->id }}">
                                            {{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->total_ayah }} ayat
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="hafalan_surah_end_id" class="block text-sm font-medium text-gray-700">
                                    Surah Akhir
                                </label>
                                <select id="hafalan_surah_end_id"
                                        name="surah_end_id"
                                        required
                                        x-model="surahEndHafalan"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Pilih surah akhir</option>
                                    @foreach ($surahs as $surah)
                                        <option value="{{ $surah->id }}">
                                            {{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->total_ayah }} ayat
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                             <div>
                                 <label for="hafalan_ayah_start" class="block text-sm font-medium text-gray-700">
                                     Ayat Mulai
                                 </label>
                                 <input id="hafalan_ayah_start"
                                        type="number"
                                        name="ayah_start"
                                        min="1"
                                        required
                                        x-model="ayahStart"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                             </div>

                             <div>
                                 <label for="hafalan_ayah_end" class="block text-sm font-medium text-gray-700">
                                     Ayat Akhir
                                 </label>
                                 <input id="hafalan_ayah_end"
                                        type="number"
                                        name="ayah_end"
                                        min="1"
                                        required
                                        x-model="ayahEnd"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                             </div>

                             <div class="sm:col-span-2" x-show="surahStartHafalan && ayahStart && ayahEnd && parseInt(ayahStart) <= parseInt(ayahEnd)">
                                 <p class="text-xs text-gray-500 font-semibold flex items-center gap-1.5">
                                     <span>Taksiran Capaian:</span>
                                     <span class="px-2 py-0.5 rounded bg-zinc-100 text-zinc-800 font-extrabold" x-text="calculateLines(surahStartHafalan, ayahStart, ayahEnd) + ' Baris'"></span>
                                 </p>
                             </div>
                         </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="submission_type" class="block text-sm font-medium text-gray-700">
                                    Jenis Setoran
                                </label>
                                <select id="submission_type"
                                        name="submission_type"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="new" @selected(old('submission_type') === 'new')>Baru</option>
                                    <option value="continuation" @selected(old('submission_type') === 'continuation')>Lanjutan</option>
                                    <option value="revision" @selected(old('submission_type') === 'revision')>Perbaikan</option>
                                </select>
                            </div>

                            <div>
                                <label for="hafalan_score" class="block text-sm font-medium text-gray-700">
                                    Nilai (Skala Huruf)
                                </label>
                                <select id="hafalan_score"
                                        name="score"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Pilih Nilai</option>
                                    <option value="95" @selected(old('score') == '95' || old('score') === 'A')>A (Sangat Baik)</option>
                                    <option value="85" @selected(old('score') == '85' || old('score') === 'B')>B (Baik)</option>
                                    <option value="75" @selected(old('score') == '75' || old('score') === 'C')>C (Cukup)</option>
                                    <option value="65" @selected(old('score') == '65' || old('score') === 'D')>D (Kurang)</option>
                                    <option value="55" @selected(old('score') == '55' || old('score') === 'E')>E (Sangat Kurang)</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="hafalan_status" class="block text-sm font-medium text-gray-700">
                                    Status
                                </label>
                                <select id="hafalan_status"
                                        name="status"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="passed" @selected(old('status') === 'passed')>Lulus</option>
                                    <option value="repeat" @selected(old('status') === 'repeat')>Ulang</option>
                                    <option value="needs_improvement" @selected(old('status') === 'needs_improvement')>Perlu Perbaikan</option>
                                </select>
                            </div>

                            <div>
                                <label for="submitted_at" class="block text-sm font-medium text-gray-700">
                                    Tanggal
                                </label>
                                <input id="submitted_at"
                                       type="date"
                                       name="submitted_at"
                                       required
                                       x-model="selectedDate"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div>
                            <label for="hafalan_notes" class="block text-sm font-medium text-gray-700">
                                Catatan
                            </label>
                            <textarea id="hafalan_notes"
                                      name="notes"
                                      rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Catatan guru, kesalahan tajwid, atau arahan perbaikan.">{{ old('notes') }}</textarea>
                        </div>

                        <div class="border-t pt-4 flex justify-end">
                            <button type="submit"
                                    style="background-color: #059669; color: #ffffff;"
                                    class="inline-flex items-center rounded-lg px-5 py-2.5 text-sm font-semibold shadow-sm hover:opacity-90">
                                Simpan Hafalan
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="px-6 py-4 border-b flex items-center justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-gray-900">Input Cepat Murajaah</h3>
                            <p class="text-sm text-gray-500 mt-1">
                                Untuk evaluasi kelancaran, tajwid, dan makhraj.
                            </p>
                        </div>

                        <button type="submit"
                                form="murajaah-form"
                                style="background-color: #4f46e5; color: #ffffff;"
                                class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm hover:opacity-90">
                            Simpan Murajaah
                        </button>
                    </div>

                    <form id="murajaah-form" method="POST" action="{{ route('quick-inputs.murajaah.store') }}" class="p-6 space-y-4">
                        @csrf

                        <div>
                            <label for="murajaah_student_id" class="block text-sm font-medium text-gray-700">
                                Santri
                            </label>
                            <select id="murajaah_student_id"
                                    name="student_id"
                                    required
                                    x-model="selectedStudentId"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Pilih santri</option>
                                <template x-for="student in filteredStudents" :key="student.id">
                                    <option :value="student.id" x-text="student.name + (student.className ? ' — ' + student.className : '')" :selected="student.id == selectedStudentId"></option>
                                </template>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="murajaah_surah_id" class="block text-sm font-medium text-gray-700">
                                    Surah Mulai
                                </label>
                                <select id="murajaah_surah_id"
                                        name="surah_id"
                                        required
                                        x-model="surahStartMurajaah"
                                        @change="if (!surahEndMurajaah || surahEndMurajaah == '') { surahEndMurajaah = surahStartMurajaah; }"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Pilih surah mulai</option>
                                    @foreach ($surahs as $surah)
                                        <option value="{{ $surah->id }}">
                                            {{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->total_ayah }} ayat
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="murajaah_surah_end_id" class="block text-sm font-medium text-gray-700">
                                    Surah Akhir
                                </label>
                                <select id="murajaah_surah_end_id"
                                        name="surah_end_id"
                                        required
                                        x-model="surahEndMurajaah"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Pilih surah akhir</option>
                                    @foreach ($surahs as $surah)
                                        <option value="{{ $surah->id }}">
                                            {{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->total_ayah }} ayat
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="murajaah_ayah_start" class="block text-sm font-medium text-gray-700">
                                    Ayat Mulai
                                </label>
                                <input id="murajaah_ayah_start"
                                       type="number"
                                       name="ayah_start"
                                       min="1"
                                       required
                                       value="{{ old('ayah_start', request('ayah_start')) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label for="murajaah_ayah_end" class="block text-sm font-medium text-gray-700">
                                    Ayat Akhir
                                </label>
                                <input id="murajaah_ayah_end"
                                       type="number"
                                       name="ayah_end"
                                       min="1"
                                       required
                                       value="{{ old('ayah_end', request('ayah_end')) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label for="fluency_score" class="block text-sm font-medium text-gray-700">
                                    Kelancaran
                                </label>
                                <input id="fluency_score"
                                       type="number"
                                       name="fluency_score"
                                       min="0"
                                       max="100"
                                       value="{{ old('fluency_score') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label for="tajwid_score" class="block text-sm font-medium text-gray-700">
                                    Tajwid
                                </label>
                                <input id="tajwid_score"
                                       type="number"
                                       name="tajwid_score"
                                       min="0"
                                       max="100"
                                       value="{{ old('tajwid_score') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label for="makhraj_score" class="block text-sm font-medium text-gray-700">
                                    Makhraj
                                </label>
                                <input id="makhraj_score"
                                       type="number"
                                       name="makhraj_score"
                                       min="0"
                                       max="100"
                                       value="{{ old('makhraj_score') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div>
                                <label for="overall_score" class="block text-sm font-medium text-gray-700">
                                    Overall
                                </label>
                                <input id="overall_score"
                                       type="number"
                                       name="overall_score"
                                       min="0"
                                       max="100"
                                       value="{{ old('overall_score') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="murajaah_status" class="block text-sm font-medium text-gray-700">
                                    Status
                                </label>
                                <select id="murajaah_status"
                                        name="status"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="passed" @selected(old('status') === 'passed')>Lulus</option>
                                    <option value="repeat" @selected(old('status') === 'repeat')>Ulang</option>
                                    <option value="needs_improvement" @selected(old('status') === 'needs_improvement')>Perlu Perbaikan</option>
                                </select>
                            </div>

                            <div>
                                <label for="reviewed_at" class="block text-sm font-medium text-gray-700">
                                    Tanggal
                                </label>
                                <input id="reviewed_at"
                                       type="date"
                                       name="reviewed_at"
                                       required
                                       x-model="selectedDate"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <div>
                            <label for="murajaah_notes" class="block text-sm font-medium text-gray-700">
                                Catatan
                            </label>
                            <textarea id="murajaah_notes"
                                      name="notes"
                                      rows="3"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                      placeholder="Catatan hasil murajaah.">{{ old('notes') }}</textarea>
                        </div>

                        <div class="border-t pt-4 flex justify-end">
                            <button type="submit"
                                    style="background-color: #4f46e5; color: #ffffff;"
                                    class="inline-flex items-center rounded-lg px-5 py-2.5 text-sm font-semibold shadow-sm hover:opacity-90">
                                Simpan Murajaah
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Form Cepat UMMI (x-show="isUmmiSelected") -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden" x-show="isUmmiSelected" x-cloak>
                <div class="px-6 py-4 border-b flex items-center justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-gray-900">Input Cepat Tahsin UMMI (Per Kelas Halaqoh)</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Catat perkembangan jilid dan hafalan Metode UMMI satu kelas halaqoh secara bersamaan.
                        </p>
                    </div>
                    <button type="submit"
                            form="ummi-form"
                            style="background-color: #f59e0b; color: #ffffff;"
                            class="shrink-0 inline-flex items-center rounded-lg px-4 py-2 text-sm font-semibold shadow-sm hover:opacity-90">
                        Simpan Catatan UMMI
                    </button>
                </div>

                <form id="ummi-form" method="POST" action="{{ route('quick-inputs.ummi.store') }}" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="input_mode" value="ummi">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div>
                                <label for="ummi_class_room_id" class="block text-sm font-medium text-gray-700">
                                    Kelas Halaqoh
                                </label>
                                <select id="ummi_class_room_id"
                                        name="class_room_id"
                                        required
                                        x-model="selectedClass"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Pilih kelas halaqoh</option>
                                    @foreach ($classRooms as $class)
                                        <option value="{{ $class->id }}" :selected="selectedClass == {{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="ummi_tatap_muka" class="block text-sm font-medium text-gray-700">
                                        Tatap Muka (Ke-)
                                    </label>
                                    <input id="ummi_tatap_muka"
                                           type="number"
                                           name="tatap_muka"
                                           min="1"
                                           required
                                           x-model.number="tatapMuka"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label for="ummi_tanggal" class="block text-sm font-medium text-gray-700">
                                        Tanggal
                                    </label>
                                    <input id="ummi_tanggal"
                                           type="date"
                                           name="tanggal"
                                           required
                                           x-model="selectedDate"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <!-- Dynamic Hafalan List -->
                            <div class="space-y-2 border border-gray-200 rounded-lg p-3">
                                <label class="block text-xs font-bold uppercase text-gray-500 tracking-wider mb-2">
                                    Setoran Hafalan UMMI
                                </label>
                                <template x-for="(item, index) in ummiHafalans" :key="index">
                                    <div class="grid grid-cols-12 gap-3 items-end bg-gray-50/70 p-2.5 rounded border border-gray-150 relative">
                                        <div class="col-span-6">
                                            <label :for="'ummi_hafalan_surah_' + index" class="block text-xs font-medium text-gray-700 mb-1">
                                                Surah
                                            </label>
                                            <select :id="'ummi_hafalan_surah_' + index"
                                                    :name="'hafalan_surah_ids['+index+']'"
                                                    x-model="item.surah_id"
                                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs">
                                                <option value="">Pilih Surah</option>
                                                @foreach ($surahs as $surah)
                                                    <option value="{{ $surah->id }}">
                                                        {{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->total_ayah }} ayat
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-span-4">
                                            <label :for="'ummi_hafalan_ayah_' + index" class="block text-xs font-medium text-gray-700 mb-1">
                                                Ayat
                                            </label>
                                            <input type="text"
                                                   :id="'ummi_hafalan_ayah_' + index"
                                                   :name="'hafalan_ayahs['+index+']'"
                                                   x-model="item.ayah"
                                                   placeholder="e.g. 1-10"
                                                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs">
                                        </div>
                                        <div class="col-span-2 text-right">
                                            <button type="button"
                                                    @click="if(ummiHafalans.length > 1) { ummiHafalans.splice(index, 1); } else { item.surah_id = ''; item.ayah = ''; }"
                                                    class="inline-flex items-center px-2 py-1.5 bg-red-50 text-red-600 hover:bg-red-100 rounded text-[10px] font-bold border border-red-200">
                                                Hapus
                                            </button>
                                        </div>
                                        <!-- Taksiran Baris -->
                                        <div class="col-span-12 mt-1 text-[10px] text-right" x-show="item.surah_id && item.ayah && parseAyahRange(item.ayah)">
                                            <span class="text-zinc-500 font-semibold">Taksiran Hafalan:</span>
                                            <span class="px-1.5 py-0.5 rounded bg-zinc-100 text-zinc-800 font-extrabold" x-text="calculateUmmiLines(item.surah_id, item.ayah) + ' Baris'"></span>
                                        </div>
                                    </div>
                                </template>
                                <div class="pt-1">
                                    <button type="button"
                                            @click="ummiHafalans.push({ surah_id: '', ayah: '' })"
                                            class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded text-xs font-semibold border border-indigo-200 transition-colors">
                                        + Tambah Surat Hafalan
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="ummi_jilid" class="block text-sm font-medium text-gray-700">
                                        UMMI (Jilid)
                                    </label>
                                    <input id="ummi_jilid"
                                           type="text"
                                           name="ummi_jilid"
                                           value="{{ old('ummi_jilid') }}"
                                           placeholder="e.g. Jilid 4"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label for="ummi_halaman" class="block text-sm font-medium text-gray-700">
                                        Halaman
                                    </label>
                                    <input id="ummi_halaman"
                                           type="text"
                                           name="ummi_halaman"
                                           value="{{ old('ummi_halaman') }}"
                                           placeholder="e.g. Hal 12"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div>
                                <label for="ummi_materi" class="block text-sm font-medium text-gray-700">
                                    Materi Pembelajaran UMMI
                                </label>
                                <input id="ummi_materi"
                                       type="text"
                                       name="materi"
                                       value="{{ old('materi') }}"
                                       placeholder="e.g. Mad Thabi'i"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="ummi_nilai" class="block text-sm font-medium text-gray-700">
                                        Nilai
                                    </label>
                                    <select id="ummi_nilai"
                                            name="nilai"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Pilih Nilai</option>
                                        <option value="A+" @selected(old('nilai') === 'A+')>A+ (Kesalahan 0)</option>
                                        <option value="A" @selected(old('nilai') === 'A')>A (Kesalahan 0)</option>
                                        <option value="B+" @selected(old('nilai') === 'B+')>B+ (Kesalahan -1)</option>
                                        <option value="B" @selected(old('nilai') === 'B')>B (Kesalahan -2)</option>
                                        <option value="B-" @selected(old('nilai') === 'B-')>B- (Kesalahan -3)</option>
                                        <option value="C+" @selected(old('nilai') === 'C+')>C+ (Kesalahan -4)</option>
                                        <option value="C" @selected(old('nilai') === 'C')>C (Kesalahan -5)</option>
                                        <option value="C-" @selected(old('nilai') === 'C-')>C- (Kesalahan -6)</option>
                                        <option value="D" @selected(old('nilai') === 'D')>D (Kesalahan -7)</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label for="ummi_disimak_guru" class="block text-sm font-medium text-gray-700">
                                            Disimak Guru
                                        </label>
                                        <select id="ummi_disimak_guru"
                                                name="disimak_guru"
                                                required
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="Ya" @selected(old('disimak_guru', 'Ya') === 'Ya')>Ya</option>
                                            <option value="Tidak" @selected(old('disimak_guru') === 'Tidak')>Tidak</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="ummi_disimak_ortu" class="block text-sm font-medium text-gray-700">
                                            Disimak Ortu
                                        </label>
                                        <select id="ummi_disimak_ortu"
                                                name="disimak_ortu"
                                                required
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="Tidak" @selected(old('disimak_ortu', 'Tidak') === 'Tidak')>Tidak</option>
                                            <option value="Ya" @selected(old('disimak_ortu') === 'Ya')>Ya</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                             <div>
                                <label for="ummi_keterangan" class="block text-sm font-medium text-gray-700">
                                    Keterangan / Catatan Kesalahan
                                </label>
                                <textarea id="ummi_keterangan"
                                          name="keterangan"
                                          rows="4"
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                          placeholder="Catatan kesalahan bacaan atau makhraj..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Student Checklist & Override Section -->
                    <div x-show="selectedClass" class="border-t pt-5 mt-4 space-y-4 student-checklist-container">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 border-b">
                            <div>
                                <h4 class="font-bold text-gray-900 text-sm">Daftar Santri & Penyesuaian Nilai Individu</h4>
                                <p class="text-xs text-gray-500 mt-0.5">Daftar santri aktif di kelas halaqoh terpilih. Anda dapat mengecualikan santri yang absen dan menyesuaikan nilai/catatan mereka secara individual jika dibutuhkan.</p>
                            </div>
                            <div class="flex items-center gap-3 text-xs shrink-0 mt-1 sm:mt-0">
                                <button type="button" @click="$el.closest('.student-checklist-container').querySelectorAll('input[type=checkbox]').forEach(el => { el.checked = true; el.dispatchEvent(new Event('change')) })" class="text-indigo-600 hover:text-indigo-800 font-semibold transition">Centang Semua</button>
                                <span class="text-gray-300">|</span>
                                <button type="button" @click="$el.closest('.student-checklist-container').querySelectorAll('input[type=checkbox]').forEach(el => { el.checked = false; el.dispatchEvent(new Event('change')) })" class="text-red-600 hover:text-red-800 font-semibold transition">Hapus Semua</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[350px] overflow-y-auto pr-1">
                            <template x-for="student in filteredStudents" :key="student.id">
                                <div class="flex flex-col p-3.5 bg-gray-50/70 rounded-xl border border-gray-200 gap-3 hover:bg-gray-100 dark:hover:bg-zinc-800/50 transition duration-150">
                                    <!-- Top Row: Checkbox and Name -->
                                    <div class="flex items-start gap-3">
                                        <input type="checkbox" 
                                               name="student_ids[]" 
                                               :id="'checkbox_std_' + student.id"
                                               :value="student.id" 
                                               checked 
                                               class="mt-0.5 rounded border-gray-300 dark:border-zinc-700 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                        <label :for="'checkbox_std_' + student.id" class="cursor-pointer select-none min-w-0 flex-1">
                                            <span class="font-bold text-xs text-gray-900 dark:text-zinc-200 block" x-text="student.name"></span>
                                            <span class="text-[10px] text-gray-500 dark:text-zinc-400 block mt-0.5" x-text="student.nis || student.student_number || '-'"></span>
                                        </label>
                                    </div>
                                    <!-- Bottom Row: Inputs -->
                                    <div class="flex items-center gap-2 pt-2.5 border-t border-gray-200/60 dark:border-zinc-800">
                                        <!-- Individual Score -->
                                        <div class="flex-1 min-w-0">
                                            <select :name="'student_scores[' + student.id + ']'" 
                                                    class="block w-full rounded-lg border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] py-1.5 pl-2 pr-7 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                                <option value="">Default Kelas</option>
                                                <option value="A+">A+ (0)</option>
                                                <option value="A">A (0)</option>
                                                <option value="B+">B+ (-1)</option>
                                                <option value="B">B (-2)</option>
                                                <option value="B-">B- (-3)</option>
                                                <option value="C+">C+ (-4)</option>
                                                <option value="C">C (-5)</option>
                                                <option value="C-">C- (-6)</option>
                                                <option value="D">D (-7)</option>
                                            </select>
                                        </div>
                                        <!-- Individual Note -->
                                        <div class="flex-[1.5] min-w-0">
                                            <input type="text" 
                                                   :name="'student_notes[' + student.id + ']'" 
                                                   placeholder="Catatan khusus" 
                                                   class="block w-full rounded-lg border-gray-300 dark:border-zinc-700 bg-transparent text-[11px] py-1.5 px-2.5 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="border-t pt-4 flex justify-end">
                        <button type="submit"
                                style="background-color: #f59e0b; color: #ffffff;"
                                class="inline-flex items-center rounded-lg px-5 py-2.5 text-sm font-semibold shadow-sm hover:opacity-90">
                            Simpan Catatan UMMI
                        </button>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h3 class="font-semibold text-gray-900">Hafalan Terbaru</h3>
                    </div>

                    <div class="divide-y">
                        @forelse ($latestHafalanRecords as $record)
                            <div class="px-6 py-4 flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ $record->student?->name ?? '-' }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        {{ $record->surah?->name_latin ?? '-' }} ayat {{ $record->ayah_range }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        Guru: {{ $record->teacher?->user?->name ?? '-' }}
                                    </p>
                                </div>

                                <div class="text-right shrink-0">
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ $record->status_label }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $record->submitted_at?->format('d M Y') ?? '-' }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-center text-gray-500">
                                Belum ada setoran hafalan.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h3 class="font-semibold text-gray-900">Murajaah Terbaru</h3>
                    </div>

                    <div class="divide-y">
                        @forelse ($latestMurajaahRecords as $record)
                            <div class="px-6 py-4 flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ $record->student?->name ?? '-' }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        {{ $record->surah?->name_latin ?? '-' }} ayat {{ $record->ayah_range }}
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        Guru: {{ $record->teacher?->user?->name ?? '-' }}
                                    </p>
                                </div>

                                <div class="text-right shrink-0">
                                    <p class="text-sm font-semibold text-gray-900">
                                        {{ $record->status_label }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $record->reviewed_at?->format('d M Y') ?? '-' }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-center text-gray-500">
                                Belum ada murajaah.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="px-6 py-4 border-b">
                        <h3 class="font-semibold text-gray-900">Catatan UMMI Terbaru</h3>
                    </div>

                    <div class="divide-y">
                        @forelse ($latestUmmiRecords as $record)
                            <div class="px-6 py-4 flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ $record->student?->name ?? '-' }}
                                    </p>
                                    <p class="text-sm text-gray-600 font-semibold text-amber-600">
                                        @if($record->ummi_jilid)
                                            {{ $record->ummi_jilid }} Hal. {{ $record->ummi_halaman ?: '-' }}
                                        @endif
                                        @if($record->hafalan_surah_id)
                                            <span class="text-gray-500 font-normal block text-xs mt-0.5">Hafalan: QS. {{ $record->surah?->name_latin }} Ayat {{ $record->hafalan_ayah }}</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        Guru: {{ $record->teacher?->user?->name ?? '-' }}
                                    </p>
                                </div>

                                <div class="text-right shrink-0">
                                    <p class="text-sm font-semibold text-gray-900">
                                        Nilai: {{ $record->nilai ?: '-' }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $record->tanggal?->format('d M Y') ?? '-' }}
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-8 text-center text-gray-500">
                                Belum ada catatan UMMI.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
        </div>
    </div>
</x-app-layout>