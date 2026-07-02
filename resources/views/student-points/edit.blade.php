<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                Ubah Catatan Poin
            </h2>
            <p class="text-sm text-gray-600 dark:text-zinc-400">
                Ubah informasi catatan poin kedisiplinan santri.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-zinc-900 rounded-2xl border border-gray-200 dark:border-zinc-800 shadow-sm overflow-hidden transition-colors duration-200">
                <div class="border-b border-gray-200 dark:border-zinc-800 px-6 py-4 bg-gray-50/50 dark:bg-[#09090b]/40">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">Ubah Catatan Kedisiplinan</h3>
                </div>

                <form method="POST" action="{{ route('student-points.update', $studentPoint) }}" class="p-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- Student Select -->
                    <div class="space-y-2">
                        <label for="student_id" class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                            Santri
                        </label>
                        <select
                            name="student_id"
                            id="student_id"
                            required
                            class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        >
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" {{ old('student_id', $studentPoint->student_id) == $student->id ? 'selected' : '' }}>
                                    {{ $student->name }} (NIS: {{ $student->student_number ?: '-' }}) - {{ $student->classRoom?->name ?? 'Tanpa Kelas' }}
                                </option>
                            @endforeach
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
                                required
                                class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            >
                                <option value="violation" {{ old('type', $studentPoint->type) === 'violation' ? 'selected' : '' }}>Pelanggaran Tata Tertib</option>
                                <option value="reward" {{ old('type', $studentPoint->type) === 'reward' ? 'selected' : '' }}>Penghargaan / Prestasi</option>
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
                                value="{{ old('points', $studentPoint->points) }}"
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

                    <!-- Title / Nama Pelanggaran/Penghargaan -->
                    <div class="space-y-2">
                        <label for="title" class="block text-sm font-semibold text-gray-700 dark:text-zinc-300">
                            Nama Pelanggaran / Penghargaan
                        </label>
                        <input
                            type="text"
                            name="title"
                            id="title"
                            value="{{ old('title', $studentPoint->title) }}"
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
                            value="{{ old('date', $studentPoint->date?->format('Y-m-d')) }}"
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
                            class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                        >{{ old('description', $studentPoint->description) }}</textarea>
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
