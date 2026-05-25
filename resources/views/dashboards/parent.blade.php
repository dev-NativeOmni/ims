<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-900">
                Dashboard Orangtua
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Monitoring progress hafalan, target, dan aktivitas anak.
            </p>
        </div>
    </x-slot>

    @php
        $parent = data_get($stats, 'parent');
        $children = collect(data_get($stats, 'children', []));
        $childrenProgress = collect(data_get($stats, 'children_progress', []));
        $childrenMotivation = collect(data_get($stats, 'children_motivation', []));
        $latestTargets = collect(data_get($stats, 'latest_targets', []));
        $latestHafalanRecords = collect(data_get($stats, 'latest_hafalan_records', []));
        $latestMurajaahRecords = collect(data_get($stats, 'latest_murajaah_records', []));
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (! $parent)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                    Profil orangtua belum terhubung dengan akun ini.
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-gray-500">Total Anak</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            {{ number_format(data_get($stats, 'total_children', $children->count())) }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-gray-500">Target Aktif</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            {{ number_format(data_get($stats, 'active_targets', 0)) }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-gray-500">Target Terlambat</p>
                        <p class="mt-2 text-3xl font-bold text-red-600">
                            {{ number_format(data_get($stats, 'overdue_targets', 0)) }}
                        </p>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-sm font-medium text-gray-500">Aktivitas Terbaru</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            {{ number_format($latestHafalanRecords->count() + $latestMurajaahRecords->count()) }}
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-200 px-5 py-4">
                        <h3 class="text-base font-semibold text-gray-900">
                            Progress Anak
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Ringkasan progress hafalan tiap anak.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-5 py-3 text-left font-semibold text-gray-600">Santri</th>
                                    <th class="px-5 py-3 text-left font-semibold text-gray-600">Kelas</th>
                                    <th class="px-5 py-3 text-left font-semibold text-gray-600">Progress</th>
                                    <th class="px-5 py-3 text-left font-semibold text-gray-600">Hafalan</th>
                                    <th class="px-5 py-3 text-left font-semibold text-gray-600">Murajaah</th>
                                    <th class="px-5 py-3 text-right font-semibold text-gray-600">Aksi</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($childrenProgress as $row)
                                    @php
                                        $student = data_get($row, 'student');
                                        $progressPercent = (float) data_get($row, 'progress_percent', data_get($row, 'progress_percentage', 0));
                                        $progressWidth = min(100, max(0, $progressPercent));
                                    @endphp

                                    <tr>
                                        <td class="px-5 py-4 align-top">
                                            <div class="font-semibold text-gray-900">
                                                {{ data_get($row, 'student_name', $student?->name ?? '-') }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ data_get($row, 'student_number', $student?->student_number ?? '-') }}
                                            </div>
                                        </td>

                                        <td class="px-5 py-4 align-top">
                                            <div class="text-gray-900">
                                                {{ data_get($row, 'class_room_name', $student?->classRoom?->name ?? '-') }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ data_get($row, 'program_name', $student?->classRoom?->program?->name ?? '-') }}
                                            </div>
                                        </td>

                                        <td class="px-5 py-4 align-top">
                                            <div class="mb-1 flex items-center justify-between gap-3">
                                                <span class="font-semibold text-gray-900">
                                                    {{ number_format($progressPercent, 2) }}%
                                                </span>
                                            </div>

                                            <div class="h-2.5 w-48 overflow-hidden rounded-full bg-gray-100">
                                                <div class="h-2.5 rounded-full bg-emerald-600"
                                                     style="width: {{ $progressWidth }}%">
                                                </div>
                                            </div>
                                        </td>

                                        <td class="px-5 py-4 align-top text-gray-700">
                                            {{ number_format(data_get($row, 'total_hafalan_records', 0)) }}
                                        </td>

                                        <td class="px-5 py-4 align-top text-gray-700">
                                            {{ number_format(data_get($row, 'total_murajaah_records', 0)) }}
                                        </td>

                                        <td class="px-5 py-4 text-right align-top">
                                            @if ($student && Route::has('progress.show'))
                                                <a href="{{ route('progress.show', $student) }}"
                                                   class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-3 py-2 text-xs font-semibold text-white hover:bg-gray-800">
                                                    Detail
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-8 text-center text-gray-500">
                                            Belum ada data anak.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    @forelse ($childrenMotivation as $item)
                        @include('dashboards.partials.motivation-card', [
                            'student' => data_get($item, 'student'),
                            'progress' => data_get($item, 'progress', []),
                            'motivation' => data_get($item, 'motivation', []),
                            'showStudentName' => true,
                        ])
                    @empty
                        <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-500 lg:col-span-2">
                            Belum ada data motivasi anak.
                        </div>
                    @endforelse
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-5 py-4">
                            <h3 class="text-base font-semibold text-gray-900">
                                Target Terbaru
                            </h3>
                        </div>

                        <div class="divide-y divide-gray-100">
                            @forelse ($latestTargets as $target)
                                <div class="p-5">
                                    <p class="font-semibold text-gray-900">
                                        {{ $target->student?->name ?? '-' }}
                                    </p>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ $target->surah?->name_latin ?? '-' }}
                                        · Ayat {{ $target->ayah_start }} - {{ $target->ayah_end }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        Target: {{ $target->target_date ? \Carbon\Carbon::parse($target->target_date)->format('d M Y') : '-' }}
                                    </p>
                                </div>
                            @empty
                                <div class="p-5 text-sm text-gray-500">
                                    Belum ada target.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-5 py-4">
                            <h3 class="text-base font-semibold text-gray-900">
                                Hafalan Terbaru
                            </h3>
                        </div>

                        <div class="divide-y divide-gray-100">
                            @forelse ($latestHafalanRecords as $record)
                                <div class="p-5">
                                    <p class="font-semibold text-gray-900">
                                        {{ $record->student?->name ?? '-' }}
                                    </p>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ $record->surah?->name_latin ?? '-' }}
                                        · Ayat {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ $record->submitted_at ? \Carbon\Carbon::parse($record->submitted_at)->format('d M Y') : '-' }}
                                        · {{ $record->status ?? '-' }}
                                    </p>
                                </div>
                            @empty
                                <div class="p-5 text-sm text-gray-500">
                                    Belum ada hafalan.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-5 py-4">
                            <h3 class="text-base font-semibold text-gray-900">
                                Murajaah Terbaru
                            </h3>
                        </div>

                        <div class="divide-y divide-gray-100">
                            @forelse ($latestMurajaahRecords as $record)
                                <div class="p-5">
                                    <p class="font-semibold text-gray-900">
                                        {{ $record->student?->name ?? '-' }}
                                    </p>
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ $record->surah?->name_latin ?? '-' }}
                                        · Ayat {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ $record->reviewed_at ? \Carbon\Carbon::parse($record->reviewed_at)->format('d M Y') : '-' }}
                                        · {{ $record->status ?? '-' }}
                                    </p>
                                </div>
                            @empty
                                <div class="p-5 text-sm text-gray-500">
                                    Belum ada murajaah.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>