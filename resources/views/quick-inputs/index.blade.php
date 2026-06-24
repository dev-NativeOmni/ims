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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

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
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Pilih santri</option>
                                @foreach ($students as $student)
                                    <option value="{{ $student->id }}" @selected(old('student_id', request('student_id')) == $student->id)>
                                        {{ $student->name }}
                                        @if ($student->classRoom)
                                            — {{ $student->classRoom->name }}
                                        @endif
                                    </option>
                                @endforeach
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
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Pilih santri</option>
                                @foreach ($students as $student)
                                    <option value="{{ $student->id }}" @selected(old('student_id', request('student_id')) == $student->id)>
                                        {{ $student->name }}
                                        @if ($student->classRoom)
                                            — {{ $student->classRoom->name }}
                                        @endif
                                    </option>
                                @endforeach
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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
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
            </div>

        </div>
    </div>
</x-app-layout>