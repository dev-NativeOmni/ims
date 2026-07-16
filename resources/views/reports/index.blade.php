<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Laporan IMS
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    Ringkasan hafalan, murajaah, target, dan progres santri.
                </p>
            </div>

            <a href="{{ route('reports.export.csv', request()->query()) }}"
               class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                Export CSV
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="student_id" class="mb-1 block text-sm font-semibold text-gray-700">
                            Santri
                        </label>
                        <select id="student_id" name="student_id" class="w-full rounded-lg border-gray-300 text-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Semua Santri</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" @selected((string) request('student_id') === (string) $student->id)>
                                    {{ $student->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="class_room_id" class="mb-1 block text-sm font-semibold text-gray-700">
                            Kelas
                        </label>
                        <select id="class_room_id" name="class_room_id" class="w-full rounded-lg border-gray-300 text-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Semua Kelas</option>
                            @foreach ($classRooms as $classRoom)
                                <option value="{{ $classRoom->id }}" @selected((string) request('class_room_id') === (string) $classRoom->id)>
                                    {{ $classRoom->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="teacher_id" class="mb-1 block text-sm font-semibold text-gray-700">
                            Guru
                        </label>
                        <select id="teacher_id" name="teacher_id" class="w-full rounded-lg border-gray-300 text-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Semua Guru</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}" @selected((string) request('teacher_id') === (string) $teacher->id)>
                                    {{ $teacher->user?->name ?? 'Guru #' . $teacher->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="surah_id" class="mb-1 block text-sm font-semibold text-gray-700">
                            Surah
                        </label>
                        <select id="surah_id" name="surah_id" class="w-full rounded-lg border-gray-300 text-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Semua Surah</option>
                            @foreach ($surahs as $surah)
                                <option value="{{ $surah->id }}" @selected((string) request('surah_id') === (string) $surah->id)>
                                    {{ $surah->number }}. {{ $surah->name_latin ?? $surah->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="status" class="mb-1 block text-sm font-semibold text-gray-700">
                            Status Setoran
                        </label>
                        <select id="status" name="status" class="w-full rounded-lg border-gray-300 text-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Semua Status</option>
                            <option value="passed" @selected(request('status') === 'passed')>Lulus</option>
                            <option value="good" @selected(request('status') === 'good')>Baik</option>
                            <option value="repeat" @selected(request('status') === 'repeat')>Ulang</option>
                            <option value="needs_improvement" @selected(request('status') === 'needs_improvement')>Perlu Perbaikan</option>
                        </select>
                    </div>

                    <div>
                        <label for="from" class="mb-1 block text-sm font-semibold text-gray-700">
                            Dari Tanggal
                        </label>
                        <input id="from" type="date" name="from" value="{{ request('from') }}"
                               class="w-full rounded-lg border-gray-300 text-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div>
                        <label for="to" class="mb-1 block text-sm font-semibold text-gray-700">
                            Sampai Tanggal
                        </label>
                        <input id="to" type="date" name="to" value="{{ request('to') }}"
                               class="w-full rounded-lg border-gray-300 text-gray-900 shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit"
                                class="inline-flex w-full items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-800">
                            Terapkan
                        </button>

                        <a href="{{ route('reports.index') }}"
                           class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">Total Hafalan</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($summary['total_hafalan'] ?? 0) }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">Total Murajaah</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($summary['total_murajaah'] ?? 0) }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">Target Aktif</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($summary['active_targets'] ?? 0) }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">Rata-rata Nilai</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ number_format((float) (($summary['average_hafalan_score'] ?? 0) + ($summary['average_murajaah_score'] ?? 0)) / 2, 2) }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">Hafalan Lulus</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($summary['passed_hafalan'] ?? 0) }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">Hafalan Perlu Perhatian</p>
                    <p class="mt-2 text-2xl font-bold text-amber-700">{{ number_format($summary['repeat_hafalan'] ?? 0) }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">Murajaah Lulus/Baik</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-700">{{ number_format($summary['passed_murajaah'] ?? 0) }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-600">Murajaah Perlu Perhatian</p>
                    <p class="mt-2 text-2xl font-bold text-amber-700">{{ number_format($summary['repeat_murajaah'] ?? 0) }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Data Hafalan</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Tanggal</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Santri</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Kelas</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Surah</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Ayat</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Nilai</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($hafalanRecords as $record)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ filled($record->submitted_at) ? \Illuminate\Support\Carbon::parse($record->submitted_at)->format('d M Y') : '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-gray-900">
                                        @if ($record->student)
                                            <a href="{{ route('reports.student', $record->student) }}" class="text-emerald-700 hover:text-emerald-900">
                                                {{ $record->student->name }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $record->student?->classRoom?->name ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $record->surah?->name_latin ?? $record->surah?->name ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-gray-900">
                                        {{ $record->score ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ \Illuminate\Support\Str::headline($record->status ?? '-') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-sm text-gray-500">
                                        Belum ada data hafalan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-200 px-5 py-4">
                    {{ $hafalanRecords->links() }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Data Murajaah</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Tanggal</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Santri</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Kelas</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Surah</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Ayat</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Nilai</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($murajaahRecords as $record)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ filled($record->reviewed_at) ? \Illuminate\Support\Carbon::parse($record->reviewed_at)->format('d M Y') : '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-gray-900">
                                        @if ($record->student)
                                            <a href="{{ route('reports.student', $record->student) }}" class="text-emerald-700 hover:text-emerald-900">
                                                {{ $record->student->name }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $record->student?->classRoom?->name ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $record->surah?->name_latin ?? $record->surah?->name ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-gray-900">
                                        {{ $record->overall_score ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ \Illuminate\Support\Str::headline($record->status ?? '-') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-sm text-gray-500">
                                        Belum ada data murajaah.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-200 px-5 py-4">
                    {{ $murajaahRecords->links() }}
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Target Hafalan</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Target</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Santri</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Surah</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Ayat</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Guru</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($hafalanTargets as $target)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ filled($target->target_date) ? \Illuminate\Support\Carbon::parse($target->target_date)->format('d M Y') : '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-gray-900">
                                        @if ($target->student)
                                            <a href="{{ route('reports.student', $target->student) }}" class="text-emerald-700 hover:text-emerald-900">
                                                {{ $target->student->name }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $target->surah?->name_latin ?? $target->surah?->name ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $target->ayah_start }} - {{ $target->ayah_end }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ \Illuminate\Support\Str::headline($target->status ?? '-') }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ $target->teacher?->user?->name ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500">
                                        Belum ada target hafalan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-200 px-5 py-4">
                    {{ $hafalanTargets->links() }}
                </div>
            </div>

        </div>
    </div>
</x-app-layout>