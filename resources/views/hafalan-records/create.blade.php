<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Input Setoran Hafalan
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('hafalan-records.store') }}" class="space-y-6" x-data="{
                    selectedClass: '',
                    selectedStudent: '{{ old('student_id') }}',
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

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="class_room_filter" class="block text-sm font-medium text-gray-700">
                                Saring Berdasarkan Kelas
                            </label>
                            <select
                                id="class_room_filter"
                                x-model="selectedClass"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >
                                <option value="">Semua Kelas</option>
                                @foreach ($classRooms as $class)
                                    <option value="{{ $class->id }}">{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="student_id" class="block text-sm font-medium text-gray-700">
                                Santri
                            </label>

                            <select
                                id="student_id"
                                name="student_id"
                                x-model="selectedStudent"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            >
                                <option value="">Pilih Santri</option>
                                <template x-for="student in filteredStudents" :key="student.id">
                                    <option :value="student.id" x-text="student.name + (student.nis ? ' - ' + student.nis : '') + (student.className ? ' - ' + student.className : '')" :selected="student.id == selectedStudent"></option>
                                </template>
                            </select>

                            @error('student_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror

                            @if ($students->isEmpty())
                                <p class="mt-1 text-sm text-red-600">
                                    Tidak ada santri aktif yang bisa dipilih.
                                </p>
                            @endif
                        </div>

                        <div>
                            <label for="surah_id" class="block text-sm font-medium text-gray-700">
                                Surah
                            </label>

                            <select
                                id="surah_id"
                                name="surah_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            >
                                <option value="">Pilih Surah</option>
                                @foreach ($surahs as $surah)
                                    <option value="{{ $surah->id }}" @selected((string) old('surah_id') === (string) $surah->id)>
                                        {{ $surah->number }}. {{ $surah->name_latin }} — {{ $surah->total_ayah }} ayat
                                    </option>
                                @endforeach
                            </select>

                            @error('surah_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="ayah_start" class="block text-sm font-medium text-gray-700">
                                Ayat Mulai
                            </label>

                            <input
                                id="ayah_start"
                                name="ayah_start"
                                type="number"
                                min="1"
                                value="{{ old('ayah_start') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            >

                            @error('ayah_start')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="ayah_end" class="block text-sm font-medium text-gray-700">
                                Ayat Akhir
                            </label>

                            <input
                                id="ayah_end"
                                name="ayah_end"
                                type="number"
                                min="1"
                                value="{{ old('ayah_end') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            >

                            @error('ayah_end')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="submission_type" class="block text-sm font-medium text-gray-700">
                                Jenis Setoran
                            </label>

                            <select
                                id="submission_type"
                                name="submission_type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            >
                                <option value="new" @selected(old('submission_type', 'new') === 'new')>Baru</option>
                                <option value="continuation" @selected(old('submission_type') === 'continuation')>Lanjutan</option>
                                <option value="revision" @selected(old('submission_type') === 'revision')>Perbaikan</option>
                            </select>

                            @error('submission_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="score" class="block text-sm font-medium text-gray-700">
                                Nilai
                            </label>

                            <input
                                id="score"
                                name="score"
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                value="{{ old('score') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                            >

                            @error('score')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">
                                Status Setoran
                            </label>

                            <select
                                id="status"
                                name="status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            >
                                <option value="passed" @selected(old('status') === 'passed')>Lulus</option>
                                <option value="repeat" @selected(old('status') === 'repeat')>Ulang</option>
                                <option value="needs_improvement" @selected(old('status', 'needs_improvement') === 'needs_improvement')>Perlu Perbaikan</option>
                            </select>

                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="submitted_at" class="block text-sm font-medium text-gray-700">
                                Tanggal Setoran
                            </label>

                            <input
                                id="submitted_at"
                                name="submitted_at"
                                type="date"
                                value="{{ old('submitted_at', now()->format('Y-m-d')) }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                required
                            >

                            @error('submitted_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="notes" class="block text-sm font-medium text-gray-700">
                                Catatan Guru
                            </label>

                            <textarea
                                id="notes"
                                name="notes"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                                placeholder="Contoh: Lancar, tajwid masih perlu diperbaiki pada mad."
                            >{{ old('notes') }}</textarea>

                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('hafalan-records.index') }}" class="text-sm text-gray-600 hover:underline">
                            Batal
                        </a>

                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                        >
                            Simpan Setoran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>