@php
    $record = $murajaahRecord ?? null;
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-6" x-data="{
    selectedClass: '',
    selectedStudent: '{{ old('student_id', $record?->student_id) }}',
    surahStart: '{{ old('surah_id', $record?->surah_id) }}',
    surahEnd: '{{ old('surah_end_id', $record?->surah_end_id ?? $record?->surah_id) }}',
    ayahStart: '{{ old('ayah_start', $record?->ayah_start) }}',
    ayahEnd: '{{ old('ayah_end', $record?->ayah_end) }}',
    fluency: '{{ old('fluency_score', $record?->fluency_score) }}',
    tajwid: '{{ old('tajwid_score', $record?->tajwid_score) }}',
    makhraj: '{{ old('makhraj_score', $record?->makhraj_score) }}',
    overall: '{{ old('overall_score', $record?->overall_score) }}',
    allStudents: [
        @foreach($students as $student)
            { id: {{ $student->id }}, name: '{{ addslashes($student->name) }}', nis: '{{ $student->student_number ?? '' }}', classId: '{{ $student->class_room_id }}', className: '{{ $student->classRoom?->name ?? '' }}' },
        @endforeach
    ],
    surahDetails: {
        @foreach ($surahs as $surah)
            '{{ $surah->id }}': { id: {{ $surah->id }}, number: {{ $surah->number }}, totalAyah: {{ $surah->total_ayah }}, name: '{{ addslashes($surah->name_latin) }}' },
        @endforeach
    },
    get filteredStudents() {
        if (!this.selectedClass) return this.allStudents;
        return this.allStudents.filter(s => s.classId == this.selectedClass);
    },
    autoCalculateOverall() {
        let f = parseFloat(this.fluency) || 0;
        let t = parseFloat(this.tajwid) || 0;
        let m = parseFloat(this.makhraj) || 0;
        let count = 0;
        if (f > 0) count++;
        if (t > 0) count++;
        if (m > 0) count++;
        if (count > 0) {
            this.overall = ((f + t + m) / count).toFixed(2);
        } else {
            this.overall = '';
        }
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
            Murid
        </label>

        <div x-data="{ open: false, search: '' }" @click.outside="open = false" class="relative mt-1">
            <button type="button" 
                    @click="open = !open" 
                    class="flex items-center justify-between w-full rounded-md border border-gray-300 bg-white text-left text-xs px-3 py-2 text-gray-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                <span x-text="selectedStudent && allStudents.find(s => s.id == selectedStudent) ? allStudents.find(s => s.id == selectedStudent).name + (allStudents.find(s => s.id == selectedStudent).className ? ' — ' + allStudents.find(s => s.id == selectedStudent).className : '') : 'Pilih Murid'"></span>
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            
            <div x-show="open" 
                 class="absolute z-50 mt-1 w-full bg-white rounded-md shadow-lg border border-gray-200"
                 style="display: none;">
                <div class="p-2 border-b border-gray-200 bg-gray-50">
                    <input type="text" 
                           x-model="search" 
                           placeholder="Cari nama murid..." 
                           class="w-full rounded border border-gray-300 bg-transparent text-xs px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <ul class="max-h-[180px] overflow-y-auto py-1 text-xs">
                    <template x-for="student in filteredStudents.filter(s => s.name.toLowerCase().includes(search.toLowerCase()))" :key="student.id">
                        <li @click="selectedStudent = student.id; open = false; search = ''" 
                            class="px-3 py-2 hover:bg-indigo-600 hover:text-white cursor-pointer transition-colors"
                            x-text="student.name + (student.className ? ' — ' + student.className : '')">
                        </li>
                    </template>
                    <li x-show="filteredStudents.filter(s => s.name.toLowerCase().includes(search.toLowerCase())).length === 0" 
                        class="px-3 py-2 text-gray-500 text-center">
                        Murid tidak ditemukan
                    </li>
                </ul>
            </div>
            <input type="hidden" id="student_id" name="student_id" x-model="selectedStudent" required>
        </div>

        @error('student_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="surah_id" class="block text-sm font-medium text-gray-700">
            {{ $record ? 'Surah' : 'Surah Mulai' }}
        </label>

        <div x-data="{ open: false, search: '' }" @click.outside="open = false" class="relative mt-1">
            <button type="button" 
                    @click="open = !open" 
                    class="flex items-center justify-between w-full rounded-md border border-gray-300 bg-white text-left text-xs px-3 py-2 text-gray-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                <span x-text="surahStart && surahDetails[surahStart] ? surahDetails[surahStart].number + '. ' + surahDetails[surahStart].name + ' — ' + surahDetails[surahStart].totalAyah + ' ayat' : 'Pilih Surah{{ $record ? '' : ' Mulai' }}'"></span>
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            
            <div x-show="open" 
                 class="absolute z-50 mt-1 w-full bg-white rounded-md shadow-lg border border-gray-200"
                 style="display: none;">
                <div class="p-2 border-b border-gray-200 bg-gray-50">
                    <input type="text" 
                           x-model="search" 
                           placeholder="Cari nama surat..." 
                           class="w-full rounded border border-gray-300 bg-transparent text-xs px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <ul class="max-h-[180px] overflow-y-auto py-1 text-xs">
                    <template x-for="surah in Object.values(surahDetails).filter(s => s.name.toLowerCase().includes(search.toLowerCase()))" :key="surah.id">
                        <li @click="surahStart = surah.id; if (!surahEnd || surahEnd == '') { surahEnd = surahStart; }; ayahStart = 1; ayahEnd = surah.totalAyah; open = false; search = ''" 
                            class="px-3 py-2 hover:bg-indigo-600 hover:text-white cursor-pointer transition-colors"
                            x-text="surah.number + '. ' + surah.name + ' — ' + surah.totalAyah + ' ayat'">
                        </li>
                    </template>
                    <li x-show="Object.values(surahDetails).filter(s => s.name.toLowerCase().includes(search.toLowerCase())).length === 0" 
                        class="px-3 py-2 text-gray-500 text-center">
                        Surah tidak ditemukan
                    </li>
                </ul>
            </div>
            <input type="hidden" id="surah_id" name="surah_id" x-model="surahStart" required>
        </div>

        @error('surah_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @if(!$record)
    <div>
        <label for="surah_end_id" class="block text-sm font-medium text-gray-700">
            Surah Akhir
        </label>

        <div x-data="{ open: false, search: '' }" @click.outside="open = false" class="relative mt-1">
            <button type="button" 
                    @click="open = !open" 
                    class="flex items-center justify-between w-full rounded-md border border-gray-300 bg-white text-left text-xs px-3 py-2 text-gray-700 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">
                <span x-text="surahEnd && surahDetails[surahEnd] ? surahDetails[surahEnd].number + '. ' + surahDetails[surahEnd].name + ' — ' + surahDetails[surahEnd].totalAyah + ' ayat' : 'Pilih Surah Akhir'"></span>
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            
            <div x-show="open" 
                 class="absolute z-50 mt-1 w-full bg-white rounded-md shadow-lg border border-gray-200"
                 style="display: none;">
                <div class="p-2 border-b border-gray-200 bg-gray-50">
                    <input type="text" 
                           x-model="search" 
                           placeholder="Cari nama surat..." 
                           class="w-full rounded border border-gray-300 bg-transparent text-xs px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <ul class="max-h-[180px] overflow-y-auto py-1 text-xs">
                    <template x-for="surah in Object.values(surahDetails).filter(s => s.name.toLowerCase().includes(search.toLowerCase()))" :key="surah.id">
                        <li @click="surahEnd = surah.id; ayahEnd = surah.totalAyah; open = false; search = ''" 
                            class="px-3 py-2 hover:bg-indigo-600 hover:text-white cursor-pointer transition-colors"
                            x-text="surah.number + '. ' + surah.name + ' — ' + surah.totalAyah + ' ayat'">
                        </li>
                    </template>
                    <li x-show="Object.values(surahDetails).filter(s => s.name.toLowerCase().includes(search.toLowerCase())).length === 0" 
                        class="px-3 py-2 text-gray-500 text-center">
                        Surah tidak ditemukan
                    </li>
                </ul>
            </div>
            <input type="hidden" id="surah_end_id" name="surah_end_id" x-model="surahEnd" required>
        </div>

        @error('surah_end_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
    @endif

    <div>
        <label for="ayah_start" class="block text-sm font-medium text-gray-700">
            Ayat Mulai
        </label>

        <input id="ayah_start"
               type="number"
               min="1"
               name="ayah_start"
               x-model="ayahStart"
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
               x-model="ayahEnd"
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
               x-model="fluency"
               @input="autoCalculateOverall()"
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
               x-model="tajwid"
               @input="autoCalculateOverall()"
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
               x-model="makhraj"
               @input="autoCalculateOverall()"
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
               x-model="overall"
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