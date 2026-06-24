<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Laporan Santri: {{ $student->name }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $student->classRoom?->name ?? 'Tanpa kelas' }}
                    @if ($student->classRoom?->program)
                        · {{ $student->classRoom->program->name }}
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'teacher']) || (auth()->user()->hasRole('parent') && auth()->user()->parentProfile?->students()->count() > 1))
                    <a href="{{ route('reports.index') }}"
                       class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                        Kembali
                    </a>
                @endif

                <a href="{{ route('reports.student.export.csv', $student) }}"
                   class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                    Export CSV
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

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
                    <p class="text-sm font-medium text-gray-600">Target Selesai</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($summary['completed_targets'] ?? 0) }}</p>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Profil Santri</h3>

                <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-600">Nomor Induk</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $student->student_number ?? '-' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-600">Kelas</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $student->classRoom?->name ?? '-' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-600">Program</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $student->classRoom?->program?->name ?? '-' }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm font-medium text-gray-600">Guru</dt>
                        <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $student->teacher?->user?->name ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Riwayat Hafalan</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Tanggal</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Surah</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Ayat</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Nilai</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($hafalanRecords as $record)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ filled($record->submitted_at) ? \Illuminate\Support\Carbon::parse($record->submitted_at)->format('d M Y') : '-' }}
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
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ $record->notes ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500">
                                        Belum ada riwayat hafalan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Riwayat Murajaah</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Tanggal</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Surah</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Ayat</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Nilai</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($murajaahRecords as $record)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ filled($record->reviewed_at) ? \Illuminate\Support\Carbon::parse($record->reviewed_at)->format('d M Y') : '-' }}
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
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ $record->notes ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-500">
                                        Belum ada riwayat murajaah.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Tanggal Target</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Surah</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Ayat</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wide text-gray-600">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($hafalanTargets as $target)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm text-gray-700">
                                        {{ filled($target->target_date) ? \Illuminate\Support\Carbon::parse($target->target_date)->format('d M Y') : '-' }}
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
                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ $target->notes ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500">
                                        Belum ada target hafalan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>