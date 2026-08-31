<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-zinc-200 leading-tight">
            Edit Catatan Progres UMMI
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 shadow-sm sm:rounded-xl p-6">
                <form method="POST" action="{{ route('ummi-records.update', $ummiRecord) }}" class="space-y-6" x-data="{
                    selectedClass: '',
                    selectedStudent: '{{ old('student_id', $ummiRecord->student_id) }}',
                    allStudents: [
                        @foreach($students as $student)
                            { id: {{ $student->id }}, name: '{{ addslashes($student->name) }}', nis: '{{ $student->student_number ?? '' }}', classId: '{{ $student->class_room_id }}', className: '{{ $student->classRoom?->name ?? '' }}' },
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
                }">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="class_room_filter" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                                Saring Berdasarkan Kelas
                            </label>
                            <select
                                id="class_room_filter"
                                x-model="selectedClass"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm"
                            >
                                <option value="">Semua Kelas</option>
                                @foreach ($classRooms as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="student_id" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                                Murid <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="student_id"
                                name="student_id"
                                x-model="selectedStudent"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm"
                                required
                            >
                                <option value="">Pilih Murid</option>
                                <template x-for="student in filteredStudents" :key="student.id">
                                    <option :value="student.id" x-text="student.name + (student.className ? ' - ' + student.className : '')" :selected="student.id == selectedStudent"></option>
                                </template>
                            </select>

                            @error('student_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="tanggal" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                                Tanggal <span class="text-red-500">*</span>
                            </label>

                            <input
                                type="date"
                                id="tanggal"
                                name="tanggal"
                                value="{{ old('tanggal', $ummiRecord->tanggal?->format('Y-m-d')) }}"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm"
                                required
                            />

                            @error('tanggal')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="tatap_muka" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                                Tatap Muka Ke-
                            </label>

                            <input
                                type="number"
                                id="tatap_muka"
                                name="tatap_muka"
                                value="{{ old('tatap_muka', $ummiRecord->tatap_muka ?? 1) }}"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm"
                                min="1"
                            />

                            @error('tatap_muka')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="ummi_jilid" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                                Buku / Jilid UMMI
                            </label>

                            <select
                                id="ummi_jilid"
                                name="ummi_jilid"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm"
                            >
                                <option value="">Pilih Jilid</option>
                                @foreach(['Jilid 1', 'Jilid 2', 'Jilid 3', 'Al-Qur\'an', 'Ghoroib', 'Tajwid'] as $jilid)
                                    <option value="{{ $jilid }}" @selected(old('ummi_jilid', $ummiRecord->ummi_jilid) === $jilid)>{{ $jilid }}</option>
                                @endforeach
                            </select>

                            @error('ummi_jilid')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="ummi_halaman" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                                Halaman
                            </label>

                            <input
                                type="text"
                                id="ummi_halaman"
                                name="ummi_halaman"
                                value="{{ old('ummi_halaman', $ummiRecord->ummi_halaman) }}"
                                placeholder="Contoh: 15-18"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm"
                            />

                            @error('ummi_halaman')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="materi" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                                Materi
                            </label>

                            <input
                                type="text"
                                id="materi"
                                name="materi"
                                value="{{ old('materi', $ummiRecord->materi) }}"
                                placeholder="Materi yang dipelajari"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm"
                            />

                            @error('materi')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="nilai" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                                Nilai UMMI
                            </label>

                            <select
                                id="nilai"
                                name="nilai"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm"
                            >
                                <option value="">Pilih Nilai</option>
                                @foreach(['A+', 'A', 'B+', 'B', 'B-', 'C+', 'C', 'D'] as $n)
                                    <option value="{{ $n }}" @selected(old('nilai', $ummiRecord->nilai) === $n)>{{ $n }}</option>
                                @endforeach
                            </select>

                            @error('nilai')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="hafalan_surah_id" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                                Surah Hafalan UMMI (Opsional)
                            </label>

                            <select
                                id="hafalan_surah_id"
                                name="hafalan_surah_id"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm"
                            >
                                <option value="">Pilih Surah</option>
                                @foreach ($surahs as $surah)
                                    <option value="{{ $surah->id }}" @selected((string) old('hafalan_surah_id', $ummiRecord->hafalan_surah_id) === (string) $surah->id)>
                                        {{ $surah->number }}. {{ $surah->name_latin }}
                                    </option>
                                @endforeach
                            </select>

                            @error('hafalan_surah_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="hafalan_ayah" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                                Ayat Hafalan UMMI (Opsional)
                            </label>

                            <input
                                type="text"
                                id="hafalan_ayah"
                                name="hafalan_ayah"
                                value="{{ old('hafalan_ayah', $ummiRecord->hafalan_ayah) }}"
                                placeholder="Contoh: 1-15"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm"
                            />

                            @error('hafalan_ayah')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="disimak_guru" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                                Disimak Guru <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="disimak_guru"
                                name="disimak_guru"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm"
                                required
                            >
                                <option value="Ya" @selected(old('disimak_guru', $ummiRecord->disimak_guru) === 'Ya')>Ya</option>
                                <option value="Tidak" @selected(old('disimak_guru', $ummiRecord->disimak_guru) === 'Tidak')>Tidak</option>
                            </select>

                            @error('disimak_guru')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="disimak_ortu" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                                Disimak Orang Tua <span class="text-red-500">*</span>
                            </label>

                            <select
                                id="disimak_ortu"
                                name="disimak_ortu"
                                class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm"
                                required
                            >
                                <option value="Tidak" @selected(old('disimak_ortu', $ummiRecord->disimak_ortu) === 'Tidak')>Tidak</option>
                                <option value="Ya" @selected(old('disimak_ortu', $ummiRecord->disimak_ortu) === 'Ya')>Ya</option>
                            </select>

                            @error('disimak_ortu')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="catatan" class="block text-sm font-medium text-gray-700 dark:text-zinc-300">
                            Catatan Tambahan
                        </label>

                        <textarea
                            id="catatan"
                            name="catatan"
                            rows="3"
                            class="mt-1 block w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white shadow-sm"
                        >{{ old('catatan', $ummiRecord->catatan) }}</textarea>

                        @error('catatan')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-zinc-800">
                        <a
                            href="{{ route('hafalan-records.index', ['category' => 'ummi']) }}"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-lg transition"
                        >
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow transition"
                        >
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
