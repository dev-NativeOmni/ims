<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-900">
                Dashboard Murid
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                Ringkasan progres hafalan, target, murajaah, dan motivasi.
            </p>
        </div>
    </x-slot>

    @php
        $student = data_get($stats, 'student');
        $progress = data_get($stats, 'progress', data_get($stats, 'summary', []));
        $motivation = data_get($stats, 'motivation', []);
        $activeTargets = collect(data_get($stats, 'active_targets', []));
        $overdueTargets = collect(data_get($stats, 'overdue_targets', []));
        $latestTargets = collect(data_get($stats, 'latest_targets', []));
        $latestHafalanRecords = collect(data_get($stats, 'latest_hafalan_records', []));
        $latestMurajaahRecords = collect(data_get($stats, 'latest_murajaah_records', []));

        $progressPercent = (float) data_get($progress, 'progress_percent', data_get($progress, 'progress_percentage', 0));
        $progressWidth = min(100, max(0, $progressPercent));
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (! $student)
                <div class="rounded-xl border border-amber-200 dark:border-amber-500/20 bg-amber-50 dark:bg-amber-950/20 p-5 text-sm text-amber-800 dark:text-amber-300">
                    Profil murid belum terhubung dengan akun ini.
                </div>
            @else
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="rounded-2xl bg-white dark:bg-zinc-900 p-5 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                            Profil Murid
                        </h3>

                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">Nama</dt>
                                <dd class="font-semibold text-zinc-900 dark:text-white">{{ $student->name }}</dd>
                            </div>

                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">Nomor Murid</dt>
                                <dd class="font-semibold text-zinc-900 dark:text-white">{{ $student->student_number ?? '-' }}</dd>
                            </div>

                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">Kelas</dt>
                                <dd class="font-semibold text-zinc-900 dark:text-white">{{ $student->classRoom?->name ?? '-' }}</dd>
                            </div>

                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">Program</dt>
                                <dd class="font-semibold text-zinc-900 dark:text-white">{{ $student->classRoom?->program?->name ?? '-' }}</dd>
                            </div>

                            <div>
                                <dt class="text-zinc-500 dark:text-zinc-400">Guru</dt>
                                <dd class="font-semibold text-zinc-900 dark:text-white">{{ $student->teacher?->user?->name ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-zinc-900 p-5 shadow-sm hover:shadow-md transition-shadow duration-200 lg:col-span-2">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                                    Progress Hafalan
                                </h3>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    Progress dihitung dari hafalan lulus.
                                </p>
                            </div>

                            <div class="text-left sm:text-right">
                                <p class="text-4xl font-bold text-zinc-900 dark:text-white">
                                    {{ number_format($progressPercent, 2) }}%
                                </p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ number_format(data_get($progress, 'memorized_ayahs', data_get($progress, 'memorized_ayah_count', 0))) }}
                                    /
                                    {{ number_format(data_get($progress, 'total_quran_ayahs', data_get($progress, 'total_ayah_count', 6236))) }}
                                    ayat
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 h-3 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-3 rounded-full bg-emerald-650"
                                 style="width: {{ $progressWidth }}%">
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="rounded-xl bg-zinc-50 dark:bg-zinc-950/40 p-4">
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Setoran Hafalan</p>
                                <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">
                                    {{ number_format(data_get($progress, 'total_hafalan_records', 0)) }}
                                </p>
                            </div>

                            <div class="rounded-xl bg-zinc-50 dark:bg-zinc-950/40 p-4">
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Murajaah</p>
                                <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">
                                    {{ number_format(data_get($progress, 'total_murajaah_records', 0)) }}
                                </p>
                            </div>

                            <div class="rounded-xl bg-zinc-50 dark:bg-zinc-950/40 p-4">
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Target Terlambat</p>
                                <p class="mt-1 text-2xl font-bold text-red-650 dark:text-red-400">
                                    {{ number_format(data_get($progress, 'overdue_targets', $overdueTargets->count())) }}
                                </p>
                            </div>
                        </div>

                        @if (Route::has('progress.show'))
                            <div class="mt-5">
                                <a href="{{ route('progress.show', $student) }}"
                                   class="inline-flex items-center justify-center rounded-lg bg-zinc-900 dark:bg-zinc-100 px-4 py-2 text-sm font-semibold text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors duration-150 shadow-sm">
                                    Lihat Detail Progress
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                @include('dashboards.partials.motivation-card', [
                    'student' => $student,
                    'progress' => $progress,
                    'motivation' => $motivation,
                    'showStudentName' => false,
                ])

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                        <div class="border-b border-zinc-100 dark:border-zinc-800/80 px-5 py-4">
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                                Target Aktif
                            </h3>
                        </div>

                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                            @forelse ($activeTargets as $target)
                                <div class="p-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-zinc-900 dark:text-white">
                                                {{ $target->surah?->name_latin ?? '-' }}
                                                · Ayat {{ $target->ayah_start }} - {{ $target->ayah_end }}
                                            </p>
                                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                                Target: {{ $target->target_date ? \Carbon\Carbon::parse($target->target_date)->format('d M Y') : '-' }}
                                            </p>
                                        </div>

                                        <span class="rounded-full bg-emerald-50 dark:bg-emerald-950/20 px-3 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-400 border border-emerald-200/40 dark:border-emerald-800/40">
                                            Aktif
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="p-5 text-sm text-zinc-500 dark:text-zinc-400">
                                    Belum ada target aktif.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                        <div class="border-b border-zinc-100 dark:border-zinc-800/80 px-5 py-4">
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                                Target Terlambat
                            </h3>
                        </div>

                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                            @forelse ($overdueTargets as $target)
                                <div class="p-5">
                                    <p class="font-semibold text-zinc-900 dark:text-white">
                                        {{ $target->surah?->name_latin ?? '-' }}
                                        · Ayat {{ $target->ayah_start }} - {{ $target->ayah_end }}
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-red-650 dark:text-red-450">
                                        Lewat dari {{ $target->target_date ? \Carbon\Carbon::parse($target->target_date)->format('d M Y') : '-' }}
                                    </p>
                                </div>
                            @empty
                                <div class="p-5 text-sm text-zinc-500 dark:text-zinc-400">
                                    Tidak ada target terlambat.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                        <div class="border-b border-zinc-100 dark:border-zinc-800/80 px-5 py-4">
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                                Hafalan Terbaru
                            </h3>
                        </div>

                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                            @forelse ($latestHafalanRecords as $record)
                                <div class="p-5">
                                    <p class="font-semibold text-zinc-900 dark:text-white">
                                        {{ $record->surah?->name_latin ?? '-' }}
                                        · Ayat {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                    </p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $record->submitted_at ? \Carbon\Carbon::parse($record->submitted_at)->format('d M Y') : '-' }}
                                        · Status: {{ $record->status ?? '-' }}
                                        · Nilai: {{ $record->score !== null ? number_format((float) $record->score, 2) : '-' }}
                                    </p>
                                </div>
                            @empty
                                <div class="p-5 text-sm text-zinc-500 dark:text-zinc-400">
                                    Belum ada hafalan.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                        <div class="border-b border-zinc-100 dark:border-zinc-800/80 px-5 py-4">
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                                Murajaah Terbaru
                            </h3>
                        </div>

                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                            @forelse ($latestMurajaahRecords as $record)
                                <div class="p-5">
                                    <p class="font-semibold text-zinc-900 dark:text-white">
                                        {{ $record->surah?->name_latin ?? '-' }}
                                        · Ayat {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                    </p>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $record->reviewed_at ? \Carbon\Carbon::parse($record->reviewed_at)->format('d M Y') : '-' }}
                                        · Status: {{ $record->status ?? '-' }}
                                        · Nilai: {{ $record->overall_score !== null ? number_format((float) $record->overall_score, 2) : '-' }}
                                    </p>
                                </div>
                            @empty
                                <div class="p-5 text-sm text-zinc-500 dark:text-zinc-400">
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