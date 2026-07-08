@php
    $record = $murajaahRecord ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-data="{
    selectedClass: '',
    selectedStudent: '{{ old('student_id', $record?->student_id) }}',
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
    <div>
        <label for="class_room_filter" class="block text-sm font-medium text-gray-700">
            Saring Berdasarkan Kelas
        </label>
        <select id="class_room_filter"
                x-model="selectedClass"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
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

        <select id="student_id"
                name="student_id"
                x-model="selectedStudent"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                required>
            <option value="">Pilih Santri</option>
            <template x-for="student in filteredStudents" :key="student.id">
                <option :value="student.id" x-text="student.name + (student.nis ? ' — ' + student.nis : '') + (student.className ? ' — ' + student.className : '')" :selected="student.id == selectedStudent"></option>
            </template>
        </select>

        @error('student_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="surah_id" class="block text-sm font-medium text-gray-700">
            Surah
        </label>

        <select id="surah_id"
                name="surah_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                required>
            <option value="">Pilih Surah</option>
            @foreach ($surahs as $surah)
                <option value="{{ $surah->id }}" @selected(old('surah_id', $record?->surah_id) == $surah->id)>
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

        <input id="ayah_start"
               type="number"
               min="1"
               name="ayah_start"
               value="{{ old('ayah_start', $record?->ayah_start) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
               required>

        @error('ayah_start')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="ayah_end" class="block text-sm font-medium text-gray-700">
            Ayat Akhir
        </label>

        <input id="ayah_end"
               type="number"
               min="1"
               name="ayah_end"
               value="{{ old('ayah_end', $record?->ayah_end) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
               required>

        @error('ayah_end')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="fluency_score" class="block text-sm font-medium text-gray-700">
            Nilai Kelancaran
        </label>

        <input id="fluency_score"
               type="number"
               min="0"
               max="100"
               step="0.01"
               name="fluency_score"
               value="{{ old('fluency_score', $record?->fluency_score) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">

        @error('fluency_score')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="tajwid_score" class="block text-sm font-medium text-gray-700">
            Nilai Tajwid
        </label>

        <input id="tajwid_score"
               type="number"
               min="0"
               max="100"
               step="0.01"
               name="tajwid_score"
               value="{{ old('tajwid_score', $record?->tajwid_score) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">

        @error('tajwid_score')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="makhraj_score" class="block text-sm font-medium text-gray-700">
            Nilai Makhraj
        </label>

        <input id="makhraj_score"
               type="number"
               min="0"
               max="100"
               step="0.01"
               name="makhraj_score"
               value="{{ old('makhraj_score', $record?->makhraj_score) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">

        @error('makhraj_score')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="overall_score" class="block text-sm font-medium text-gray-700">
            Nilai Keseluruhan
        </label>

        <input id="overall_score"
               type="number"
               min="0"
               max="100"
               step="0.01"
               name="overall_score"
               value="{{ old('overall_score', $record?->overall_score) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">

        @error('overall_score')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="block text-sm font-medium text-gray-700">
            Status
        </label>

        <select id="status"
                name="status"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                required>
            <option value="passed" @selected(old('status', $record?->status ?? 'needs_improvement') === 'passed')>
                Lulus
            </option>
            <option value="repeat" @selected(old('status', $record?->status ?? 'needs_improvement') === 'repeat')>
                Ulang
            </option>
            <option value="needs_improvement" @selected(old('status', $record?->status ?? 'needs_improvement') === 'needs_improvement')>
                Perlu Perbaikan
            </option>
        </select>

        @error('status')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="reviewed_at" class="block text-sm font-medium text-gray-700">
            Tanggal Murajaah
        </label>

        <input id="reviewed_at"
               type="date"
               name="reviewed_at"
               value="{{ old('reviewed_at', $record?->reviewed_at?->format('Y-m-d') ?? now()->toDateString()) }}"
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
               required>

        @error('reviewed_at')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="notes" class="block text-sm font-medium text-gray-700">
            Catatan
        </label>

        <textarea id="notes"
                  name="notes"
                  rows="4"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                  placeholder="Contoh: Kelancaran baik, tajwid mad masih perlu diperbaiki.">{{ old('notes', $record?->notes) }}</textarea>

        @error('notes')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-6 flex items-center gap-3">
    <button type="submit"
            class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm font-semibold">
        Simpan
    </button>

    <a href="{{ route('murajaah-records.index') }}"
       class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md text-sm font-semibold">
        Batal
    </a>
</div>