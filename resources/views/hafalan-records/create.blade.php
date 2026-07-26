<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-200 leading-tight">
                Input Setoran Hafalan
            </h2>
            <a href="{{ route('hafalan-records.index') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-100 dark:bg-zinc-800 text-gray-700 dark:text-zinc-300 rounded-lg text-xs font-semibold hover:bg-gray-200 transition">
                ← Kembali ke List
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        method: '{{ old('method', request('method', 'reguler')) }}',
        selectedClass: '',
        selectedStudent: '{{ old('student_id') }}',
        hafalans: [
            @if(old('surah_ids'))
                @foreach(old('surah_ids') as $index => $oldSurahId)
                    {
                        surah_id: '{{ $oldSurahId }}',
                        ayah_start: '{{ old("ayah_starts")[$index] ?? "" }}',
                        ayah_end: '{{ old("ayah_ends")[$index] ?? "" }}',
                        submission_type: '{{ old("submission_types")[$index] ?? "new" }}',
                        score: '{{ old("scores")[$index] ?? "" }}',
                        status: '{{ old("statuses")[$index] ?? "passed" }}'
                    },
                @endforeach
            @else
                { surah_id: '', ayah_start: '', ayah_end: '', submission_type: 'new', score: '', status: 'passed' }
            @endif
        ],
        ummiHafalans: [
            { surah_id: '', ayah: '' }
        ],
        allStudents: [
            @foreach($students as $student)
                { id: {{ $student->id }}, name: '{{ addslashes($student->name) }}', nis: '{{ $student->student_number ?? '' }}', classId: '{{ $student->class_room_id }}', className: '{{ $student->classRoom?->name ?? '' }}', level: '{{ $student->tahfizh_level }}' },
            @endforeach
        ],
        get filteredStudents() {
            if (!this.selectedClass) return this.allStudents;
            return this.allStudents.filter(s => s.classId == this.selectedClass);
        }
    }" x-init="
        if (selectedStudent) {
            let s = allStudents.find(x => x.id == selectedStudent);
            if (s) selectedClass = s.classId;
        }
    ">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Tab Switcher Metode Setoran -->
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm rounded-xl p-3 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="text-xs font-bold uppercase text-gray-500 dark:text-zinc-400 px-2 tracking-wider">
                    Pilih Metode Setoran:
                </div>
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="button"
                            @click="method = 'reguler'"
                            :class="method === 'reguler' ? 'bg-indigo-600 text-white shadow' : 'bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 hover:bg-gray-200'"
                            class="flex-1 sm:flex-none px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <span>📖</span> Reguler (Al-Qur'an)
                    </button>
                    <button type="button"
                            @click="method = 'ummi'"
                            :class="method === 'ummi' ? 'bg-emerald-600 text-white shadow' : 'bg-gray-100 dark:bg-zinc-800 text-gray-600 dark:text-zinc-400 hover:bg-gray-200'"
                            class="flex-1 sm:flex-none px-4 py-2 rounded-lg font-bold text-xs uppercase tracking-wider transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <span>🌱</span> Metode UMMI
                    </button>
                </div>
            </div>

            <!-- Error list jika ada -->
            @if ($errors->any())
                <div class="p-4 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-lg text-xs text-red-600 dark:text-red-300 space-y-1">
                    <p class="font-bold">Ada beberapa kesalahan input setoran:</p>
                    <ul class="list-disc pl-4 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- FORM 1: METODE REGULER -->
            <div x-show="method === 'reguler'" class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm sm:rounded-xl p-6">
                <div class="border-b dark:border-zinc-800 pb-4 mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-lg">Input Setoran Hafalan Reguler</h3>
                        <p class="text-xs text-gray-500 dark:text-zinc-400">Setoran Al-Qur'an per Surah & Ayat untuk kelompok reguler / tahfizh.</p>
                    </div>
                    <span class="px-2.5 py-1 rounded bg-indigo-50 dark:bg-indigo-950/40 text-indigo-700 dark:text-indigo-400 font-bold text-xs border border-indigo-100 dark:border-indigo-900">
                        Reguler
                    </span>
                </div>

                <form method="POST" action="{{ route('hafalan-records.store') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="method" value="reguler">

                    <!-- Saring & Santri & Tanggal -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label for="class_room_filter_reguler" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                                Saring Berdasarkan Kelas
                            </label>
                            <select id="class_room_filter_reguler"
                                    x-model="selectedClass"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                <option value="" class="dark:bg-zinc-900">Semua Kelas</option>
                                @foreach ($classRooms as $class)
                                    <option value="{{ $class->id }}" class="dark:bg-zinc-900">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="student_id_reguler" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                                Santri
                            </label>
                            <select id="student_id_reguler"
                                    name="student_id"
                                    x-model="selectedStudent"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white"
                                    required>
                                <option value="" class="dark:bg-zinc-900">Pilih Santri</option>
                                <template x-for="student in filteredStudents" :key="student.id">
                                    <option :value="student.id" x-text="student.name + (student.nis ? ' - ' + student.nis : '') + (student.className ? ' - ' + student.className : '')" :selected="student.id == selectedStudent" class="dark:bg-zinc-900"></option>
                                </template>
                            </select>
                            @error('student_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="submitted_at_reguler" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                                Tanggal Setoran
                            </label>
                            <input id="submitted_at_reguler"
                                   name="submitted_at"
                                   type="date"
                                   value="{{ old('submitted_at', now()->format('Y-m-d')) }}"
                                   class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white"
                                   required>
                            @error('submitted_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Dynamic Setoran List -->
                    <div class="mt-6 border border-gray-200 dark:border-zinc-800 rounded-lg p-5 bg-gray-50/50 dark:bg-zinc-900/50 space-y-4">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase text-gray-600 dark:text-zinc-400 tracking-wider">
                                Daftar Setoran Hafalan
                            </h3>
                            <button type="button"
                                    @click="hafalans.push({ surah_id: '', ayah_start: '', ayah_end: '', submission_type: 'new', score: '', status: 'passed' })"
                                    class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-semibold shadow-sm transition-colors cursor-pointer">
                                + Tambah Baris Setoran
                            </button>
                        </div>

                        <div class="space-y-4">
                            <template x-for="(item, index) in hafalans" :key="index">
                                <div class="bg-white dark:bg-zinc-900 p-4 rounded-lg border border-gray-200 dark:border-zinc-800 shadow-sm relative space-y-4">
                                    <div class="flex items-center justify-between border-b dark:border-zinc-800 pb-2">
                                        <span class="text-xs font-bold text-gray-500 dark:text-zinc-400" x-text="'Setoran #' + (index + 1)"></span>
                                        <button type="button"
                                                @click="if (hafalans.length > 1) { hafalans.splice(index, 1); } else { item.surah_id = ''; item.ayah_start = ''; item.ayah_end = ''; item.submission_type = 'new'; item.score = ''; item.status = 'passed'; }"
                                                class="text-xs text-red-600 hover:text-red-800 font-medium cursor-pointer">
                                            Hapus
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-4">
                                        <div class="col-span-1 md:col-span-2">
                                            <label class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                                Surah
                                            </label>
                                            <select :name="'surah_ids['+index+']'"
                                                    x-model="item.surah_id"
                                                    class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white"
                                                    required>
                                                <option value="" class="dark:bg-zinc-900">Pilih Surah</option>
                                                @foreach ($surahs as $surah)
                                                    <option value="{{ $surah->id }}" class="dark:bg-zinc-900">
                                                        {{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->total_ayah }} ayat
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                                Ayat Mulai
                                            </label>
                                            <input type="number"
                                                   min="1"
                                                   :name="'ayah_starts['+index+']'"
                                                   x-model="item.ayah_start"
                                                   class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white"
                                                   required>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                                Ayat Akhir
                                            </label>
                                            <input type="number"
                                                   min="1"
                                                   :name="'ayah_ends['+index+']'"
                                                   x-model="item.ayah_end"
                                                   class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white"
                                                   required>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                                Jenis Setoran
                                            </label>
                                            <select :name="'submission_types['+index+']'"
                                                    x-model="item.submission_type"
                                                    class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white"
                                                    required>
                                                <option value="new" class="dark:bg-zinc-900">Baru</option>
                                                <option value="continuation" class="dark:bg-zinc-900">Lanjutan</option>
                                                <option value="revision" class="dark:bg-zinc-900">Perbaikan</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                                Nilai (Skala A - E)
                                            </label>
                                            <select :name="'scores['+index+']'"
                                                    x-model="item.score"
                                                    class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                                <option value="" class="dark:bg-zinc-900">Pilih Nilai</option>
                                                <option value="95" class="dark:bg-zinc-900">A (Sangat Baik)</option>
                                                <option value="85" class="dark:bg-zinc-900">B (Baik)</option>
                                                <option value="75" class="dark:bg-zinc-900">C (Cukup)</option>
                                                <option value="65" class="dark:bg-zinc-900">D (Kurang)</option>
                                                <option value="55" class="dark:bg-zinc-900">E (Sangat Kurang)</option>
                                            </select>
                                        </div>

                                        <div class="col-span-1 md:col-span-2">
                                            <label class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                                Status Setoran
                                            </label>
                                            <select :name="'statuses['+index+']'"
                                                    x-model="item.status"
                                                    class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white"
                                                    required>
                                                <option value="passed" class="dark:bg-zinc-900">Lulus</option>
                                                <option value="repeat" class="dark:bg-zinc-900">Ulang</option>
                                                <option value="needs_improvement" class="dark:bg-zinc-900">Perlu Perbaikan</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Catatan Guru -->
                    <div class="mt-6">
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                            Catatan Guru (Berlaku untuk semua setoran di atas)
                        </label>
                        <textarea id="notes"
                                  name="notes"
                                  rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white"
                                  placeholder="Contoh: Lancar, tajwid masih perlu diperbaiki pada mad.">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t dark:border-zinc-800">
                        <a href="{{ route('hafalan-records.index') }}" class="px-4 py-2 text-sm text-gray-600 dark:text-zinc-400 hover:underline">
                            Batal
                        </a>
                        <button type="submit"
                                class="inline-flex items-center px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                            Simpan Setoran Reguler
                        </button>
                    </div>
                </form>
            </div>

            <!-- FORM 2: METODE UMMI -->
            <div x-show="method === 'ummi'" x-cloak class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm sm:rounded-xl p-6">
                <div class="border-b dark:border-zinc-800 pb-4 mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white text-lg">Input Catatan Hafalan Metode UMMI</h3>
                        <p class="text-xs text-gray-500 dark:text-zinc-400">Pencatatan tatap muka, jilid UMMI, materi, serta nilai evaluasi santri.</p>
                    </div>
                    <span class="px-2.5 py-1 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 font-bold text-xs border border-emerald-100 dark:border-emerald-900">
                        Metode UMMI
                    </span>
                </div>

                <form method="POST" action="{{ route('quick-inputs.ummi.store') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="method" value="ummi">
                    <input type="hidden" name="redirect_to" value="hafalan">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div>
                                <label for="class_room_filter_ummi" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                    Saring Berdasarkan Kelas
                                </label>
                                <select id="class_room_filter_ummi"
                                        x-model="selectedClass"
                                        class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                    <option value="" class="dark:bg-zinc-900">Semua Kelas</option>
                                    @foreach ($classRooms as $class)
                                        <option value="{{ $class->id }}" class="dark:bg-zinc-900">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="student_id_ummi" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                    Santri
                                </label>
                                <select id="student_id_ummi"
                                        name="student_id"
                                        required
                                        x-model="selectedStudent"
                                        class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                    <option value="" class="dark:bg-zinc-900">Pilih Santri</option>
                                    <template x-for="student in filteredStudents" :key="student.id">
                                        <option :value="student.id" x-text="student.name + (student.className ? ' — ' + student.className : '')" :selected="student.id == selectedStudent" class="dark:bg-zinc-900"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="ummi_tatap_muka" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                        Tatap Muka (Ke-)
                                    </label>
                                    <input id="ummi_tatap_muka"
                                           type="number"
                                           name="tatap_muka"
                                           min="1"
                                           required
                                           value="{{ old('tatap_muka', 1) }}"
                                           class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                </div>
                                <div>
                                    <label for="ummi_tanggal" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                        Tanggal
                                    </label>
                                    <input id="ummi_tanggal"
                                           type="date"
                                           name="tanggal"
                                           required
                                           value="{{ old('tanggal', now()->toDateString()) }}"
                                           class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                </div>
                            </div>

                            <!-- Dynamic Hafalan List UMMI -->
                            <div class="space-y-3 border border-gray-200 dark:border-zinc-800 rounded-lg p-4 bg-gray-50/50 dark:bg-zinc-900/50">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold uppercase text-gray-500 dark:text-zinc-400 tracking-wider">
                                        Setoran Hafalan UMMI
                                    </label>
                                    <button type="button"
                                            @click="ummiHafalans.push({ surah_id: '', ayah: '' })"
                                            class="inline-flex items-center px-2.5 py-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 rounded text-xs font-semibold hover:bg-emerald-100 transition cursor-pointer">
                                        + Tambah Surah
                                    </button>
                                </div>

                                <template x-for="(item, index) in ummiHafalans" :key="index">
                                    <div class="grid grid-cols-12 gap-3 items-end bg-white dark:bg-zinc-900 p-3 rounded-lg border border-gray-200 dark:border-zinc-800 relative">
                                        <div class="col-span-6">
                                            <label :for="'ummi_hafalan_surah_' + index" class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                                Surah
                                            </label>
                                            <select :id="'ummi_hafalan_surah_' + index"
                                                    :name="'hafalan_surah_ids['+index+']'"
                                                    x-model="item.surah_id"
                                                    class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                                <option value="" class="dark:bg-zinc-900">Pilih Surah</option>
                                                @foreach ($surahs as $surah)
                                                    <option value="{{ $surah->id }}" class="dark:bg-zinc-900">
                                                        {{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->total_ayah }} ayat
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-span-4">
                                            <label :for="'ummi_hafalan_ayah_' + index" class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                                Ayat
                                            </label>
                                            <input type="text"
                                                   :id="'ummi_hafalan_ayah_' + index"
                                                   :name="'hafalan_ayahs['+index+']'"
                                                   x-model="item.ayah"
                                                   placeholder="e.g. 1-10"
                                                   class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                        </div>
                                        <div class="col-span-2 text-right">
                                            <button type="button"
                                                    @click="if(ummiHafalans.length > 1) { ummiHafalans.splice(index, 1); } else { item.surah_id = ''; item.ayah = ''; }"
                                                    class="inline-flex items-center px-2 py-1.5 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 hover:bg-rose-100 rounded text-[11px] font-bold border border-rose-200 dark:border-rose-800 cursor-pointer">
                                                Hapus
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="ummi_jilid" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                        UMMI / Al-Qur'an (Jilid/Surat)
                                    </label>
                                    <input id="ummi_jilid"
                                           type="text"
                                           name="ummi_jilid"
                                           value="{{ old('ummi_jilid') }}"
                                           placeholder="e.g. Jilid 4 atau QS. Al-Mulk"
                                           class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                </div>
                                <div>
                                    <label for="ummi_halaman" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                        Halaman / Ayat
                                    </label>
                                    <input id="ummi_halaman"
                                           type="text"
                                           name="ummi_halaman"
                                           value="{{ old('ummi_halaman') }}"
                                           placeholder="e.g. Hal 12 atau Ayat 1-5"
                                           class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-4">
                            <div>
                                <label for="ummi_materi" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                    Materi Pembelajaran UMMI
                                </label>
                                <input id="ummi_materi"
                                       type="text"
                                       name="materi"
                                       value="{{ old('materi') }}"
                                       placeholder="e.g. Mad Thabi'i"
                                       class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="ummi_nilai" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                        Nilai Evaluasi
                                    </label>
                                    <select id="ummi_nilai"
                                            name="nilai"
                                            class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                        <option value="" class="dark:bg-zinc-900">Pilih Nilai</option>
                                        <option value="A+" @selected(old('nilai') === 'A+') class="dark:bg-zinc-900">A+ (Kesalahan 0)</option>
                                        <option value="A" @selected(old('nilai') === 'A') class="dark:bg-zinc-900">A (Kesalahan 0)</option>
                                        <option value="B+" @selected(old('nilai') === 'B+') class="dark:bg-zinc-900">B+ (Kesalahan -1)</option>
                                        <option value="B" @selected(old('nilai') === 'B') class="dark:bg-zinc-900">B (Kesalahan -2)</option>
                                        <option value="B-" @selected(old('nilai') === 'B-') class="dark:bg-zinc-900">B- (Kesalahan -3)</option>
                                        <option value="C+" @selected(old('nilai') === 'C+') class="dark:bg-zinc-900">C+ (Kesalahan -4)</option>
                                        <option value="C" @selected(old('nilai') === 'C') class="dark:bg-zinc-900">C (Kesalahan -5)</option>
                                        <option value="C-" @selected(old('nilai') === 'C-') class="dark:bg-zinc-900">C- (Kesalahan -6)</option>
                                        <option value="D" @selected(old('nilai') === 'D') class="dark:bg-zinc-900">D (Kesalahan -7)</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label for="ummi_disimak_guru" class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                            Simak Guru
                                        </label>
                                        <select id="ummi_disimak_guru"
                                                name="disimak_guru"
                                                required
                                                class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                            <option value="Ya" @selected(old('disimak_guru', 'Ya') === 'Ya') class="dark:bg-zinc-900">Ya</option>
                                            <option value="Tidak" @selected(old('disimak_guru') === 'Tidak') class="dark:bg-zinc-900">Tidak</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="ummi_disimak_ortu" class="block text-xs font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                            Simak Ortu
                                        </label>
                                        <select id="ummi_disimak_ortu"
                                                name="disimak_ortu"
                                                required
                                                class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-xs focus:border-indigo-500 focus:ring-indigo-500 dark:text-white">
                                            <option value="Ya" @selected(old('disimak_ortu', 'Ya') === 'Ya') class="dark:bg-zinc-900">Ya</option>
                                            <option value="Tidak" @selected(old('disimak_ortu') === 'Tidak') class="dark:bg-zinc-900">Tidak</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="ummi_keterangan" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                    Catatan / Keterangan
                                </label>
                                <textarea id="ummi_keterangan"
                                          name="keterangan"
                                          rows="3"
                                          class="block w-full rounded-md border-gray-300 dark:border-zinc-700 bg-transparent text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:text-white"
                                          placeholder="Catatan perkembangan atau arahan tajwid dari guru.">{{ old('keterangan') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t dark:border-zinc-800">
                        <a href="{{ route('hafalan-records.index', ['category' => 'ummi']) }}" class="px-4 py-2 text-sm text-gray-600 dark:text-zinc-400 hover:underline">
                            Batal
                        </a>
                        <button type="submit"
                                class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-semibold shadow-sm transition">
                            Simpan Catatan UMMI
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</x-app-layout>