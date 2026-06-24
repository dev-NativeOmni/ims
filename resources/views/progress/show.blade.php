<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-900">
                    Detail Progress — {{ $student->name }}
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    {{ $student->student_number ?? '-' }}

                    @if ($student->classRoom)
                        · {{ $student->classRoom->name }}
                    @endif

                    @if ($student->classRoom?->program)
                        · {{ $student->classRoom->program->name }}
                    @endif
                </p>
            </div>

            @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'teacher']) || (auth()->user()->hasRole('parent') && auth()->user()->parentProfile?->students()->count() > 1))
                <a href="{{ route('progress.index') }}"
                   class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                    Kembali ke Progress
                </a>
            @endif
        </div>
    </x-slot>

    @php
        $progressPercent = (float) ($progress['progress_percent'] ?? 0);
        $progressBarWidth = min(100, max(0, $progressPercent));
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-base font-semibold text-gray-900">
                        Profil Santri
                    </h3>

                    <dl class="mt-4 space-y-3 text-sm">
                        <div>
                            <dt class="text-gray-500">Nama</dt>
                            <dd class="font-semibold text-gray-900">
                                {{ $student->name }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Nomor Santri</dt>
                            <dd class="font-semibold text-gray-900">
                                {{ $student->student_number ?? '-' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Program</dt>
                            <dd class="font-semibold text-gray-900">
                                {{ $student->classRoom?->program?->name ?? '-' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Kelas</dt>
                            <dd class="font-semibold text-gray-900">
                                {{ $student->classRoom?->name ?? '-' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Guru Pembimbing</dt>
                            <dd class="font-semibold text-gray-900">
                                {{ $student->teacher?->user?->name ?? '-' }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-gray-500">Status</dt>
                            <dd class="font-semibold text-gray-900">
                                {{ ucfirst((string) ($student->status ?? '-')) }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">
                                Ringkasan Progress Hafalan
                            </h3>

                            <p class="mt-1 text-sm text-gray-600">
                                Progress dihitung dari setoran hafalan berstatus lulus dan tidak menghitung ayat yang overlap dua kali.
                            </p>
                        </div>

                        <div class="text-left sm:text-right">
                            <p class="text-4xl font-bold text-gray-900">
                                {{ number_format($progressPercent, 2) }}%
                            </p>

                            <p class="mt-1 text-sm text-gray-500">
                                {{ number_format($progress['memorized_ayahs'] ?? 0) }}
                                /
                                {{ number_format($progress['total_quran_ayahs'] ?? 0) }}
                                ayat
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 h-3 w-full overflow-hidden rounded-full bg-gray-100">
                        <div class="h-3 rounded-full bg-emerald-600"
                             style="width: {{ $progressBarWidth }}%">
                        </div>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                            <p class="text-sm text-gray-500">
                                Ayat Hafal
                            </p>

                            <p class="mt-1 text-2xl font-bold text-gray-900">
                                {{ number_format($progress['memorized_ayahs'] ?? 0) }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                            <p class="text-sm text-gray-500">
                                Sisa Ayat
                            </p>

                            <p class="mt-1 text-2xl font-bold text-gray-900">
                                {{ number_format($progress['remaining_ayahs'] ?? 0) }}
                            </p>
                        </div>

                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                            <p class="text-sm text-gray-500">
                                Target Terlambat
                            </p>

                            <p class="mt-1 text-2xl font-bold text-red-600">
                                {{ number_format($progress['overdue_targets'] ?? 0) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Total Setoran Hafalan
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ number_format($progress['total_hafalan_records'] ?? 0) }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500">
                        Lulus {{ number_format($progress['passed_hafalan_records'] ?? 0) }},
                        perlu ulang {{ number_format($progress['repeat_hafalan_records'] ?? 0) }}
                    </p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Total Murajaah
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ number_format($progress['total_murajaah_records'] ?? 0) }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500">
                        Rata-rata nilai {{ number_format((float) ($progress['average_murajaah_score'] ?? 0), 2) }}
                    </p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Target Aktif
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ number_format($progress['active_targets'] ?? 0) }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500">
                        Selesai {{ number_format($progress['completed_targets'] ?? 0) }}
                    </p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">
                        Rata-rata Nilai Hafalan
                    </p>

                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ number_format((float) ($progress['average_hafalan_score'] ?? 0), 2) }}
                    </p>

                    <p class="mt-1 text-xs text-gray-500">
                        Dari setoran yang memiliki nilai.
                    </p>
                </div>
            </div>

            @includeIf('progress.partials.motivation', [
                'motivation' => $motivation ?? [],
            ])

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-2">
                    <div class="flex flex-col gap-2 border-b border-gray-100 pb-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">
                                Progress Per Surah
                            </h3>

                            <p class="mt-1 text-sm text-gray-500">
                                Hanya menampilkan surah yang sudah memiliki setoran hafalan lulus.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Surah</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Ayat Hafal</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Progress</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Rentang Ayat</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($surahProgressRows as $row)
                                    @php
                                        $surahProgressPercent = (float) ($row['progress_percent'] ?? 0);
                                        $surahBarWidth = min(100, max(0, $surahProgressPercent));
                                    @endphp

                                    <tr>
                                        <td class="px-4 py-3 align-top">
                                            <div class="font-semibold text-gray-900">
                                                {{ $row['surah']->number ?? '-' }}.
                                                {{ $row['surah']->name_latin ?? $row['surah']->name ?? '-' }}
                                            </div>

                                            <div class="text-xs text-gray-500">
                                                {{ $row['surah']->name_ar ?? '-' }}
                                            </div>
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 align-top text-gray-700">
                                            <span class="font-semibold text-gray-900">
                                                {{ number_format($row['memorized_ayahs'] ?? 0) }}
                                            </span>
                                            /
                                            {{ number_format($row['total_ayahs'] ?? 0) }}
                                        </td>

                                        <td class="px-4 py-3 align-top">
                                            <div class="mb-1 text-xs font-semibold text-gray-700">
                                                {{ number_format($surahProgressPercent, 2) }}%
                                            </div>

                                            <div class="h-2 w-44 overflow-hidden rounded-full bg-gray-100">
                                                <div class="h-2 rounded-full bg-emerald-600"
                                                     style="width: {{ $surahBarWidth }}%">
                                                </div>
                                            </div>
                                        </td>

                                        <td class="min-w-48 px-4 py-3 align-top text-gray-700">
                                            {{ $row['ranges'] ?: '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                            Belum ada hafalan lulus untuk santri ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="text-base font-semibold text-gray-900">
                        Timeline Terbaru
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Gabungan aktivitas hafalan, murajaah, dan target.
                    </p>

                    <div class="mt-5 space-y-4">
                        @forelse ($timelineRows as $item)
                            @php
                                $badgeClass = match ($item['type'] ?? null) {
                                    'hafalan' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                                    'murajaah' => 'border-blue-200 bg-blue-50 text-blue-700',
                                    'target' => 'border-amber-200 bg-amber-50 text-amber-700',
                                    default => 'border-gray-200 bg-gray-50 text-gray-700',
                                };

                                $statusLabel = match ($item['status'] ?? null) {
                                    'passed' => 'Lulus',
                                    'repeat' => 'Ulang',
                                    'needs_improvement' => 'Perlu Perbaikan',
                                    'active' => 'Aktif',
                                    'planned' => 'Direncanakan',
                                    'in_progress' => 'Berjalan',
                                    'completed' => 'Selesai',
                                    'missed' => 'Terlewat',
                                    'cancelled' => 'Dibatalkan',
                                    default => $item['status'] ?? '-',
                                };

                                $timelineDate = $item['date'] ?? null;
                            @endphp

                            <div class="rounded-xl border border-gray-200 p-4">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold {{ $badgeClass }}">
                                        {{ $item['label'] ?? '-' }}
                                    </span>

                                    <span class="text-xs text-gray-500">
                                        {{ $timelineDate ? \Carbon\Carbon::parse($timelineDate)->format('d M Y') : '-' }}
                                    </span>
                                </div>

                                <div class="mt-3 font-semibold text-gray-900">
                                    {{ $item['title'] ?? '-' }}
                                    ·
                                    Ayat {{ $item['range'] ?? '-' }}
                                </div>

                                <div class="mt-1 text-sm text-gray-600">
                                    Status: {{ $statusLabel }}

                                    @if (($item['score'] ?? null) !== null)
                                        · Nilai: {{ number_format((float) $item['score'], 2) }}
                                    @endif
                                </div>

                                @if (! empty($item['teacher']))
                                    <div class="mt-1 text-xs text-gray-500">
                                        Guru: {{ $item['teacher'] }}
                                    </div>
                                @endif

                                @if (! empty($item['notes']))
                                    <div class="mt-2 rounded-lg bg-gray-50 px-3 py-2 text-xs leading-5 text-gray-600">
                                        {{ $item['notes'] }}
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                                Belum ada timeline aktivitas.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900">
                        Riwayat Hafalan
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Data setoran hafalan santri.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Tanggal</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Surah</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Ayat</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Jenis</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Status</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Nilai</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Guru</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Catatan</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($hafalanRecords as $record)
                                @php
                                    $statusLabel = match ($record->status) {
                                        'passed' => 'Lulus',
                                        'repeat' => 'Ulang',
                                        'needs_improvement' => 'Perlu Perbaikan',
                                        default => ucfirst((string) $record->status),
                                    };

                                    $submissionTypeLabel = $record->submission_type_label
                                        ?? match ($record->submission_type) {
                                            'new' => 'Baru',
                                            'continuation' => 'Lanjutan',
                                            'revision' => 'Perbaikan',
                                            default => ucfirst((string) $record->submission_type),
                                        };
                                @endphp

                                <tr>
                                    <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                        {{ $record->submitted_at ? \Carbon\Carbon::parse($record->submitted_at)->format('d M Y') : '-' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 font-semibold text-gray-900">
                                        {{ $record->surah?->name_latin ?? $record->surah?->name ?? '-' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                        {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                        {{ $submissionTypeLabel }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                        {{ $statusLabel }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                        {{ $record->score !== null ? number_format((float) $record->score, 2) : '-' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                        {{ $record->teacher?->user?->name ?? '-' }}
                                    </td>

                                    <td class="min-w-64 px-5 py-3 text-gray-700">
                                        {{ $record->notes ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-8 text-center text-gray-500">
                                        Belum ada riwayat hafalan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($hafalanRecords->hasPages())
                    <div class="border-t border-gray-100 px-5 py-4">
                        {{ $hafalanRecords->links() }}
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900">
                        Riwayat Murajaah
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Data pengulangan hafalan santri.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Tanggal</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Surah</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Ayat</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Kelancaran</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Tajwid</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Makhraj</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Overall</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Status</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Guru</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Catatan</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($murajaahRecords as $record)
                                @php
                                    $statusLabel = match ($record->status) {
                                        'passed' => 'Lulus',
                                        'repeat' => 'Ulang',
                                        'needs_improvement' => 'Perlu Perbaikan',
                                        default => ucfirst((string) $record->status),
                                    };
                                @endphp

                                <tr>
                                    <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                        {{ $record->reviewed_at ? \Carbon\Carbon::parse($record->reviewed_at)->format('d M Y') : '-' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 font-semibold text-gray-900">
                                        {{ $record->surah?->name_latin ?? $record->surah?->name ?? '-' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                        {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                        {{ $record->fluency_score !== null ? number_format((float) $record->fluency_score, 2) : '-' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                        {{ $record->tajwid_score !== null ? number_format((float) $record->tajwid_score, 2) : '-' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                        {{ $record->makhraj_score !== null ? number_format((float) $record->makhraj_score, 2) : '-' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                        {{ $record->overall_score !== null ? number_format((float) $record->overall_score, 2) : '-' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                        {{ $statusLabel }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                        {{ $record->teacher?->user?->name ?? '-' }}
                                    </td>

                                    <td class="min-w-64 px-5 py-3 text-gray-700">
                                        {{ $record->notes ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-5 py-8 text-center text-gray-500">
                                        Belum ada riwayat murajaah.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($murajaahRecords->hasPages())
                    <div class="border-t border-gray-100 px-5 py-4">
                        {{ $murajaahRecords->links() }}
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h3 class="text-base font-semibold text-gray-900">
                        Target Hafalan
                    </h3>

                    <p class="mt-1 text-sm text-gray-500">
                        Target hafalan aktif, selesai, terlewat, atau dibatalkan.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Tanggal Target</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Surah</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Ayat</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Status</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Selesai Pada</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Guru</th>
                                <th class="px-5 py-3 text-left font-semibold text-gray-600">Catatan</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($targets as $target)
                                @php
                                    $statusLabel = $target->status_label
                                        ?? match ($target->status) {
                                            'active' => 'Aktif',
                                            'planned' => 'Direncanakan',
                                            'in_progress' => 'Berjalan',
                                            'completed' => 'Selesai',
                                            'missed' => 'Terlewat',
                                            'cancelled' => 'Dibatalkan',
                                            default => ucfirst((string) $target->status),
                                        };

                                    $isOverdue = $target->is_overdue ?? (
                                        $target->status === 'active'
                                        && $target->target_date
                                        && \Carbon\Carbon::parse($target->target_date)->lt(today())
                                    );
                                @endphp

                                <tr>
                                    <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                        {{ $target->target_date ? \Carbon\Carbon::parse($target->target_date)->format('d M Y') : '-' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 font-semibold text-gray-900">
                                        {{ $target->surah?->name_latin ?? $target->surah?->name ?? '-' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                        {{ $target->ayah_start }} - {{ $target->ayah_end }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3">
                                        <span class="{{ $isOverdue ? 'font-semibold text-red-600' : 'text-gray-700' }}">
                                            {{ $statusLabel }}

                                            @if ($isOverdue)
                                                · Terlambat
                                            @endif
                                        </span>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                        {{ $target->completed_at ? \Carbon\Carbon::parse($target->completed_at)->format('d M Y H:i') : '-' }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-3 text-gray-700">
                                        {{ $target->teacher?->user?->name ?? '-' }}
                                    </td>

                                    <td class="min-w-64 px-5 py-3 text-gray-700">
                                        {{ $target->notes ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-gray-500">
                                        Belum ada target hafalan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($targets->hasPages())
                    <div class="border-t border-gray-100 px-5 py-4">
                        {{ $targets->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>