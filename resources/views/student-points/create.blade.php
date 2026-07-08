<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                Catat Poin Baru
            </h2>
            <p class="text-sm text-gray-600 dark:text-zinc-400">
                Pencatatan pelanggaran tata tertib atau pemberian penghargaan santri.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm overflow-hidden transition-colors duration-200">
                <div class="border-b border-gray-200 dark:border-zinc-800 px-6 py-4 bg-gray-50/50 dark:bg-[#09090b]/40">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Formulir Catatan Kedisiplinan</h3>
                </div>

                <form method="POST" action="{{ route('student-points.store') }}" class="p-6 space-y-6" x-data="{
                    type: '{{ old('type', 'violation') }}',
                    selectedClass: '',
                    selectedStudent: '{{ old('student_id', $selectedStudentId) }}',
                    allStudents: [
                        @foreach($students as $student)
                            { id: {{ $student->id }}, name: '{{ addslashes($student->name) }}', nis: '{{ $student->student_number ?? '' }}', classId: '{{ $student->class_room_id }}', className: '{{ $student->classRoom?->name ?? 'Tanpa Kelas' }}' },
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

                    <!-- Filter Kelas -->
                    <div class="space-y-2">
                        <label for="class_room_filter" class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                            Saring Berdasarkan Kelas
                        </label>
                        <select
                            id="class_room_filter"
                            x-model="selectedClass"
                            class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        >
                            <option value="">Semua Kelas</option>
                            @foreach ($classRooms as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Student Select -->
                    <div class="space-y-2">
                        <label for="student_id" class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                            Pilih Santri
                        </label>
                        <select
                            name="student_id"
                            id="student_id"
                            x-model="selectedStudent"
                            required
                            class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        >
                            <option value="">-- Pilih Santri --</option>
                            <template x-for="student in filteredStudents" :key="student.id">
                                <option :value="student.id" x-text="student.name + (student.nis ? ' (NIS: ' + student.nis + ')' : '') + ' - ' + student.className" :selected="student.id == selectedStudent"></option>
                            </template>
                        </select>
                        @error('student_id')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Type Select -->
                        <div class="space-y-2">
                            <label for="type" class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                                Tipe Catatan
                            </label>
                            <select
                                name="type"
                                id="type"
                                x-model="type"
                                required
                                class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            >
                                <option value="violation" {{ old('type') === 'violation' ? 'selected' : '' }}>Pelanggaran Tata Tertib</option>
                                <option value="reward" {{ old('type') === 'reward' ? 'selected' : '' }}>Penghargaan / Prestasi</option>
                            </select>
                            @error('type')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Points Input -->
                        <div class="space-y-2">
                            <label for="points" class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                                Nilai Poin
                            </label>
                            <input
                                type="number"
                                name="points"
                                id="points"
                                value="{{ old('points', 5) }}"
                                min="1"
                                max="1000"
                                required
                                class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            />
                            @error('points')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Violation specific fields -->
                    <div x-show="type === 'violation'" x-transition class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Category Select -->
                            <div class="space-y-2">
                                <label for="category" class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                                    Kategori Pelanggaran
                                </label>
                                <select
                                    name="category"
                                    id="category"
                                    class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                >
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="ringan" {{ old('category') === 'ringan' ? 'selected' : '' }}>Ringan</option>
                                    <option value="sedang" {{ old('category') === 'sedang' ? 'selected' : '' }}>Sedang</option>
                                    <option value="berat" {{ old('category') === 'berat' ? 'selected' : '' }}>Berat</option>
                                </select>
                                @error('category')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Location Input -->
                            <div class="space-y-2">
                                <label for="location" class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                                    Lokasi Kejadian
                                </label>
                                <input
                                    type="text"
                                    name="location"
                                    id="location"
                                    value="{{ old('location') }}"
                                    placeholder="Contoh: Masjid, Kelas, Asrama"
                                    class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                />
                                @error('location')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Sanction Textarea -->
                        <div class="space-y-2">
                            <label for="sanction" class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                                Sanksi yang Diberikan
                            </label>
                            <textarea
                                name="sanction"
                                id="sanction"
                                rows="3"
                                placeholder="Ketik detail sanksi atau tindakan pembinaan yang diberikan..."
                                class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            >{{ old('sanction') }}</textarea>
                            @error('sanction')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Reward specific fields -->
                    <div x-show="type === 'reward'" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Achievement Type Select -->
                        <div class="space-y-2">
                            <label for="achievement_type" class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                                Tipe Prestasi
                            </label>
                            <select
                                name="achievement_type"
                                id="achievement_type"
                                class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            >
                                <option value="">-- Pilih Tipe --</option>
                                <option value="academic" {{ old('achievement_type') === 'academic' ? 'selected' : '' }}>Akademik</option>
                                <option value="non-academic" {{ old('achievement_type') === 'non-academic' ? 'selected' : '' }}>Non-Akademik</option>
                            </select>
                            @error('achievement_type')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Achievement Level Select -->
                        <div class="space-y-2">
                            <label for="achievement_level" class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                                Tingkat Prestasi
                            </label>
                            <select
                                name="achievement_level"
                                id="achievement_level"
                                class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            >
                                <option value="">-- Pilih Tingkat --</option>
                                <option value="school" {{ old('achievement_level') === 'school' ? 'selected' : '' }}>Sekolah</option>
                                <option value="district" {{ old('achievement_level') === 'district' ? 'selected' : '' }}>Kabupaten/Kota</option>
                                <option value="province" {{ old('achievement_level') === 'province' ? 'selected' : '' }}>Provinsi</option>
                                <option value="national" {{ old('achievement_level') === 'national' ? 'selected' : '' }}>Nasional</option>
                            </select>
                            @error('achievement_level')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Title / Nama Pelanggaran/Penghargaan -->
                    <div class="space-y-2">
                        <label for="title" class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                            Nama Pelanggaran / Penghargaan
                        </label>
                        <input
                            type="text"
                            name="title"
                            id="title"
                            value="{{ old('title') }}"
                            placeholder="Contoh: Terlambat Shalat Berjamaah / Juara 1 Lomba MHQ"
                            required
                            class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        />
                        @error('title')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Date -->
                    <div class="space-y-2">
                        <label for="date" class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                            Tanggal Kejadian
                        </label>
                        <input
                            type="date"
                            name="date"
                            id="date"
                            value="{{ old('date', date('Y-m-d')) }}"
                            required
                            class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        />
                        @error('date')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                            Catatan / Deskripsi Tambahan (Opsional)
                        </label>
                        <textarea
                            name="description"
                            id="description"
                            rows="4"
                            placeholder="Ketik detail kejadian atau informasi tambahan di sini..."
                            class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-4 border-t border-gray-200 dark:border-zinc-800 flex justify-end gap-3">
                        <a href="{{ route('student-points.index') }}" class="inline-flex items-center justify-center px-4 py-2.5 border border-gray-300 dark:border-zinc-700 rounded-xl text-sm font-semibold text-gray-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                            Batal
                        </a>
                        <button type="submit" class="inline-flex items-center justify-center px-4 py-2.5 border border-transparent rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors">
                            Simpan Catatan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
