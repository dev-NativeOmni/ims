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

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl shadow-sm border p-5">
                    <p class="text-sm text-gray-500">Santri Aktif Bisa Diinput</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $students->count() }}</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border p-5">
                    <p class="text-sm text-gray-500">Data Surah</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ $surahs->count() }}</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm border p-5">
                    <p class="text-sm text-gray-500">Mode Input</p>
                    <p class="mt-2 text-xl font-bold text-gray-900">Hafalan + Murajaah</p>
                </div>
            </div>

            <div x-data="{
                selectedClass: '',
                selectedStudentId: '{{ old('student_id', request('student_id', '')) }}',
                allStudents: [
                    @foreach($students as $student)
                        { id: {{ $student->id }}, name: '{{ addslashes($student->name) }}', classId: '{{ $student->class_room_id }}', className: '{{ $student->classRoom?->name ?? '' }}', level: '{{ $student->tahfizh_level }}' },
                    @endforeach
                ],
                get filteredStudents() {
                    if (!this.selectedClass) return this.allStudents;
                    return this.allStudents.filter(s => s.classId == this.selectedClass);
                },
                get isUmmiSelected() {
                    let s = this.allStudents.find(x => x.id == this.selectedStudentId);
                    return s && s.level === 'ummi';
                }
            }" x-init="
                if (selectedStudentId) {
                    let s = allStudents.find(x => x.id == selectedStudentId);
                    if (s) selectedClass = s.classId;
                }
            }" class="space-y-6">

                <!-- Filter Kelas Global -->
                <div class="bg-white rounded-xl shadow-sm border p-5">
                    <div class="max-w-md">
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

                        <div>
                            <label for="hafalan_surah_id" class="block text-sm font-medium text-gray-700">
                                Surah
                            </label>
                            <select id="hafalan_surah_id"
                                    name="surah_id"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Pilih surah</option>
                                @foreach ($surahs as $surah)
                                    <option value="{{ $surah->id }}" @selected(old('surah_id', request('surah_id')) == $surah->id)>
                                        {{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->total_ayah }} ayat
                                    </option>
                                @endforeach
                            </select>
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
                                       value="{{ old('ayah_start', request('ayah_start')) }}"
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
                                       value="{{ old('ayah_end', request('ayah_end')) }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                                    Nilai
                                </label>
                                <input id="hafalan_score"
                                       type="number"
                                       name="score"
                                       min="0"
                                       max="100"
                                       value="{{ old('score') }}"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                                       value="{{ old('submitted_at', now()->toDateString()) }}"
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

                        <div>
                            <label for="murajaah_surah_id" class="block text-sm font-medium text-gray-700">
                                Surah
                            </label>
                            <select id="murajaah_surah_id"
                                    name="surah_id"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Pilih surah</option>
                                @foreach ($surahs as $surah)
                                    <option value="{{ $surah->id }}" @selected(old('surah_id', request('surah_id')) == $surah->id)>
                                        {{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->total_ayah }} ayat
                                    </option>
                                @endforeach
                            </select>
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
                                       value="{{ old('reviewed_at', now()->toDateString()) }}"
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
                        <h3 class="font-semibold text-gray-900">Input Cepat Tahsin UMMI (Kelas 10)</h3>
                        <p class="text-sm text-gray-500 mt-1">
                            Catat perkembangan jilid dan hafalan Metode UMMI santri.
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

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Left Column -->
                        <div class="space-y-4">
                            <div>
                                <label for="ummi_student_id" class="block text-sm font-medium text-gray-700">
                                    Santri
                                </label>
                                <select id="ummi_student_id"
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
                                           value="{{ old('tatap_muka', 1) }}"
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
                                           value="{{ old('tanggal', now()->toDateString()) }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="ummi_hafalan_surah" class="block text-sm font-medium text-gray-700">
                                        Hafalan (Surah)
                                    </label>
                                    <select id="ummi_hafalan_surah"
                                            name="hafalan_surah_id"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Pilih Surah</option>
                                        @foreach ($surahs as $surah)
                                            <option value="{{ $surah->id }}" @selected(old('hafalan_surah_id') == $surah->id)>
                                                {{ $surah->number }}. {{ $surah->name_latin }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="ummi_hafalan_ayah" class="block text-sm font-medium text-gray-700">
                                        Hafalan (Ayat)
                                    </label>
                                    <input id="ummi_hafalan_ayah"
                                           type="text"
                                           name="hafalan_ayah"
                                           value="{{ old('hafalan_ayah') }}"
                                           placeholder="e.g. 1-10"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="ummi_jilid" class="block text-sm font-medium text-gray-700">
                                        UMMI / Al-Qur'an (Jilid/Surat)
                                    </label>
                                    <input id="ummi_jilid"
                                           type="text"
                                           name="ummi_jilid"
                                           value="{{ old('ummi_jilid') }}"
                                           placeholder="e.g. Jilid 4 atau QS. Al-Mulk"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label for="ummi_halaman" class="block text-sm font-medium text-gray-700">
                                        Halaman / Ayat
                                    </label>
                                    <input id="ummi_halaman"
                                           type="text"
                                           name="ummi_halaman"
                                           value="{{ old('ummi_halaman') }}"
                                           placeholder="e.g. Hal 12 atau Ayat 1-5"
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