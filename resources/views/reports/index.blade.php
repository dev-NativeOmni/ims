<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Laporan Progress
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Filter laporan hafalan dan murajaah santri.
                </p>
            </div>

            <a
                href="{{ route('reports.export.csv', request()->query()) }}"
                class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-700"
            >
                Export CSV
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <p class="text-sm text-gray-500">Total Hafalan</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['total_hafalan'] }}</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5">
                    <p class="text-sm text-gray-500">Total Murajaah</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $summary['total_murajaah'] }}</p>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5">
                    <p class="text-sm text-gray-500">Butuh Perhatian</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">
                        {{ $summary['hafalan_need_attention'] + $summary['murajaah_need_attention'] }}
                    </p>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5">
                    <p class="text-sm text-gray-500">Rata-rata Nilai</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">
                        H: {{ $summary['average_hafalan_score'] ?: '-' }} /
                        M: {{ $summary['average_murajaah_score'] ?: '-' }}
                    </p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6">
                <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="student_id" class="block text-sm font-medium text-gray-700">Santri</label>
                        <select id="student_id" name="student_id" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">Semua Santri</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" @selected((string) request('student_id') === (string) $student->id)>
                                    {{ $student->name }} {{ $student->student_number ? '— ' . $student->student_number : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="class_room_id" class="block text-sm font-medium text-gray-700">Kelas</label>
                        <select id="class_room_id" name="class_room_id" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">Semua Kelas</option>
                            @foreach ($classRooms as $classRoom)
                                <option value="{{ $classRoom->id }}" @selected((string) request('class_room_id') === (string) $classRoom->id)>
                                    {{ $classRoom->name }}
                                    {{ $classRoom->program ? '— ' . $classRoom->program->name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if (! auth()->user()?->hasRole('teacher'))
                        <div>
                            <label for="teacher_id" class="block text-sm font-medium text-gray-700">Guru</label>
                            <select id="teacher_id" name="teacher_id" class="mt-1 block w-full rounded-md border-gray-300">
                                <option value="">Semua Guru</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" @selected((string) request('teacher_id') === (string) $teacher->id)>
                                        {{ $teacher->user?->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label for="surah_id" class="block text-sm font-medium text-gray-700">Surah</label>
                        <select id="surah_id" name="surah_id" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">Semua Surah</option>
                            @foreach ($surahs as $surah)
                                <option value="{{ $surah->id }}" @selected((string) request('surah_id') === (string) $surah->id)>
                                    {{ $surah->number }}. {{ $surah->name_latin }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300">
                            <option value="">Semua Status</option>
                            <option value="passed" @selected(request('status') === 'passed')>Lulus</option>
                            <option value="repeat" @selected(request('status') === 'repeat')>Ulang</option>
                            <option value="needs_improvement" @selected(request('status') === 'needs_improvement')>Perlu Perbaikan</option>
                        </select>
                    </div>

                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700">Dari Tanggal</label>
                        <input
                            id="date_from"
                            type="date"
                            name="date_from"
                            value="{{ request('date_from') }}"
                            class="mt-1 block w-full rounded-md border-gray-300"
                        >
                    </div>

                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700">Sampai Tanggal</label>
                        <input
                            id="date_to"
                            type="date"
                            name="date_to"
                            value="{{ request('date_to') }}"
                            class="mt-1 block w-full rounded-md border-gray-300"
                        >
                    </div>

                    <div class="flex items-end gap-2">
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-gray-900 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
                        >
                            Filter
                        </button>

                        <a
                            href="{{ route('reports.index') }}"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50"
                        >
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Laporan Hafalan</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Tanggal</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Santri</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Kelas</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Guru</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Surah</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Ayat</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Jenis</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Nilai</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($hafalanRecords as $record)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->submitted_at?->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ $record->student?->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $record->student?->student_number }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->student?->classRoom?->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->teacher?->user?->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->surah?->name_latin ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->submission_type_label }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->score ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            @if ($record->status === 'passed') bg-green-100 text-green-700
                                            @elseif ($record->status === 'repeat') bg-red-100 text-red-700
                                            @else bg-yellow-100 text-yellow-700
                                            @endif
                                        ">
                                            {{ $record->status_label }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                        Belum ada data hafalan sesuai filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $hafalanRecords->links() }}
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">Laporan Murajaah</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Tanggal</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Santri</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Kelas</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Guru</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Surah</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Ayat</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Kelancaran</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Tajwid</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Makhraj</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Overall</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse ($murajaahRecords as $record)
                                <tr>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->reviewed_at?->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ $record->student?->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $record->student?->student_number }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->student?->classRoom?->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->teacher?->user?->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->surah?->name_latin ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->fluency_score ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->tajwid_score ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->makhraj_score ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $record->overall_score ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            @if ($record->status === 'passed') bg-green-100 text-green-700
                                            @elseif ($record->status === 'repeat') bg-red-100 text-red-700
                                            @else bg-yellow-100 text-yellow-700
                                            @endif
                                        ">
                                            {{ $record->status_label }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-4 py-8 text-center text-gray-500">
                                        Belum ada data murajaah sesuai filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $murajaahRecords->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>