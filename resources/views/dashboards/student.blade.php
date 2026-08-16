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

            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/30 p-4 text-sm text-emerald-800 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/30 p-4 text-sm text-rose-800 dark:text-rose-300">
                    {{ session('error') }}
                </div>
            @endif

            @if (! $student)
                <div class="rounded-xl border border-amber-200 dark:border-amber-500/20 bg-amber-50 dark:bg-amber-950/20 p-5 text-sm text-amber-800 dark:text-amber-300">
                    Profil murid belum terhubung dengan akun ini. Silakan hubungi Administrator untuk menghubungkan data murid Anda.
                </div>
            @else
                @php
                    $className = data_get($progress, 'class_room_name', $student?->classRoom?->name ?? '');
                    $classLevel = $student?->classRoom?->level ?? '';
                    $isGrade10Class = (bool) (
                        (preg_match('/\bX\b/i', $className) && !preg_match('/\b(XI|XII)\b/i', $className))
                        || preg_match('/\b10\b/i', $className)
                        || preg_match('/^X[-_\s]?E/i', $className)
                        || preg_match('/kelas\s*(X|10)/i', $className)
                        || (preg_match('/\bX\b/i', $classLevel) && !preg_match('/\b(XI|XII)\b/i', $classLevel))
                        || preg_match('/\b10\b/i', $classLevel)
                    ) && !preg_match('/\b(XI|XII|11|12)\b/i', $className);

                    $isUmmi = data_get($progress, 'is_ummi_program', false) || $isGrade10Class;
                    $statusColor = data_get($progress, 'status_color', 'emerald');
                    $statusLabel = data_get($progress, 'status_label', 'On-Track / Tuntas');
                    $statusIcon = data_get($progress, 'status_icon', '🟢');
                @endphp

                {{-- ═══════════════ TARGET & CAPAIAN PROGRAM HERO CARD ═══════════════ --}}
                <div class="rounded-2xl bg-white dark:bg-zinc-900 p-6 shadow-sm hover:shadow-md transition-shadow duration-200 border border-zinc-200 dark:border-zinc-800">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800 pb-4">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl">{{ $isUmmi ? '📗' : '📘' }}</span>
                            <div>
                                <h3 class="text-lg font-extrabold text-zinc-900 dark:text-white flex items-center gap-2">
                                    <span>Target &amp; Capaian Program {{ $isUmmi ? 'Ummi (Kelas X)' : 'Reguler (Kelas XI / XII)' }}</span>
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $isUmmi ? 'Monitoring progres Tahsin Ummi, Halaman, dan Surah Hafalan' : 'Monitoring Target Baris Setoran & Ketercapaian Hafalan Periodik' }}
                                </p>
                            </div>
                        </div>

                        <div>
                            @if ($statusColor === 'emerald')
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    <span>{{ $statusIcon }}</span> {{ $statusLabel }}
                                </span>
                            @elseif ($statusColor === 'amber')
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                    <span>{{ $statusIcon }}</span> {{ $statusLabel }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                    <span>{{ $statusIcon }}</span> {{ $statusLabel }}
                                </span>
                            @endif
                        </div>
                    </div>

                    @if ($isUmmi)
                        {{-- ─── PROGRAM UMMI UI (KELAS 10) ─── --}}
                        <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="rounded-xl border border-teal-100 dark:border-teal-900/40 bg-teal-50/40 dark:bg-teal-950/20 p-4">
                                <p class="text-xs font-semibold text-teal-700 dark:text-teal-300 uppercase tracking-wider mb-1">📖 Jilid &amp; Halaman Saat Ini</p>
                                <p class="text-2xl font-extrabold text-teal-900 dark:text-teal-100">{{ data_get($progress, 'ummi_jilid_str', 'Jilid 1') }}</p>
                                <p class="text-xs text-teal-600 dark:text-teal-400 mt-0.5">Halaman {{ data_get($progress, 'ummi_halaman', '-') }} · Tatap Muka #{{ data_get($progress, 'ummi_tatap_muka', '-') }}</p>
                            </div>

                            <div class="rounded-xl border border-cyan-100 dark:border-cyan-900/40 bg-cyan-50/40 dark:bg-cyan-950/20 p-4">
                                <p class="text-xs font-semibold text-cyan-700 dark:text-cyan-300 uppercase tracking-wider mb-1">🎯 Target Surah Ummi</p>
                                <p class="text-lg font-bold text-cyan-900 dark:text-cyan-100">
                                    {{ data_get($progress, 'ummi_target.surah.name_latin', 'Belum Ada Target') }}
                                </p>
                                <p class="text-xs text-cyan-600 dark:text-cyan-400 mt-0.5">
                                    @if(data_get($progress, 'ummi_target'))
                                        Ayat {{ data_get($progress, 'ummi_target.ayah_start') }} - {{ data_get($progress, 'ummi_target.ayah_end') }}
                                    @else
                                        Mengikuti alur Jilid Ummi
                                    @endif
                                </p>
                            </div>

                            <div class="rounded-xl border border-emerald-100 dark:border-emerald-900/40 bg-emerald-50/40 dark:bg-emerald-950/20 p-4">
                                <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider mb-1">🏆 Nilai Munaqasyah / Penguji</p>
                                <p class="text-2xl font-extrabold text-emerald-900 dark:text-emerald-100">
                                    {{ data_get($progress, 'ummi_munaqasyah_score') !== null ? number_format((float) data_get($progress, 'ummi_munaqasyah_score'), 1) : '-' }}
                                </p>
                                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-0.5">Evaluasi kelancaran &amp; tajwid</p>
                            </div>
                        </div>

                        <div class="mt-4 pt-3">
                            <div class="flex items-center justify-between text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1.5">
                                <span>Progress Ketercapaian Jilid Ummi</span>
                                <span>{{ data_get($progress, 'ummi_jilid_percent', 0) }}% (Jilid {{ data_get($progress, 'ummi_jilid_num', 1) }} / 3 Dewasa)</span>
                            </div>
                            <div class="h-3 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-3 rounded-full bg-teal-600 transition-all duration-300" style="width: {{ data_get($progress, 'ummi_jilid_percent', 0) }}%"></div>
                            </div>
                        </div>
                    @else
                        {{-- ─── PROGRAM REGULER UI (KELAS 11 & 12) ─── --}}
                        <div class="mt-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="rounded-xl border border-indigo-100 dark:border-indigo-900/40 bg-indigo-50/40 dark:bg-indigo-950/20 p-4">
                                <p class="text-xs font-semibold text-indigo-700 dark:text-indigo-300 uppercase tracking-wider mb-1">🎯 Target Baris Harian</p>
                                <p class="text-2xl font-extrabold text-indigo-900 dark:text-indigo-100">{{ data_get($progress, 'level_baris', 5) }} Baris / Hari</p>
                                <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-0.5">Level: {{ ucfirst(data_get($progress, 'tahfizh_level', 'reguler')) }}</p>
                            </div>

                            <div class="rounded-xl border border-emerald-100 dark:border-emerald-900/40 bg-emerald-50/40 dark:bg-emerald-950/20 p-4">
                                <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider mb-1">📊 Capaian Baris Bulan Ini</p>
                                <p class="text-2xl font-extrabold text-emerald-900 dark:text-emerald-100">{{ data_get($progress, 'capaian_baris_month', 0) }} / {{ data_get($progress, 'target_baris_month', 100) }} Baris</p>
                                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-0.5">Ketercapaian: {{ data_get($progress, 'reguler_baris_percent', 0) }}%</p>
                            </div>

                            <div class="rounded-xl border border-purple-100 dark:border-purple-900/40 bg-purple-50/40 dark:bg-purple-950/20 p-4">
                                <p class="text-xs font-semibold text-purple-700 dark:text-purple-300 uppercase tracking-wider mb-1">🏆 Total Juz Lengkap</p>
                                <p class="text-2xl font-extrabold text-purple-900 dark:text-purple-100">{{ data_get($progress, 'completed_juz_count', 0) }} Juz</p>
                                <p class="text-xs text-purple-600 dark:text-purple-400 mt-0.5">{{ data_get($progress, 'completed_juz_list', 'Belum ada Juz lengkap') }}</p>
                            </div>
                        </div>

                        <div class="mt-4 pt-3">
                            <div class="flex items-center justify-between text-xs font-semibold text-zinc-600 dark:text-zinc-400 mb-1.5">
                                <span>Progress Ketercapaian Baris Setoran Bulan Ini</span>
                                <span>{{ data_get($progress, 'reguler_baris_percent', 0) }}% ({{ data_get($progress, 'capaian_baris_month', 0) }} Baris)</span>
                            </div>
                            <div class="h-3 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-3 rounded-full bg-emerald-600 transition-all duration-300" style="width: {{ data_get($progress, 'reguler_baris_percent', 0) }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ═══════════════ PETA PERJALANAN HAFALAN (4 TERM X 3 KELAS) ═══════════════ --}}
                <x-student-hafalan-journey :milestones="data_get($progress, 'term_milestones', [])" />

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

                @php
                    $todayDate = now()->toDateString();
                    $adabFilledToday = \App\Models\AdabRecord::where('student_id', $student->id)->where('assessment_date', $todayDate)->exists();
                @endphp

                {{-- Quick Access Card for Adab Questionnaire --}}
                <div class="rounded-2xl bg-white dark:bg-zinc-900 p-6 shadow-sm hover:shadow-md transition-shadow duration-200 border border-emerald-100 dark:border-emerald-900/30">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 text-2xl flex-shrink-0">
                                🕋
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                    <span>Kuisioner Adab Harian</span>
                                </h3>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Pengisian mandiri adab, ketakwaan, dan pembinaan karakter harian.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            @if ($adabFilledToday)
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    <span>✅</span> Sudah Diisi Hari Ini
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                    <span>⚠️</span> Belum Diisi Hari Ini
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-t border-zinc-100 dark:border-zinc-800/80 flex flex-wrap items-center gap-3">
                        @if (! $adabFilledToday)
                            <a href="{{ route('adab.create', $student) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white transition shadow-sm">
                                ✏️ Isi Kuisioner Hari Ini
                            </a>
                        @endif

                        <a href="{{ route('adab.show', $student) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 px-4 py-2.5 text-sm font-semibold text-zinc-700 dark:text-zinc-300 transition">
                            📊 Lihat Laporan & Grafik Adab
                        </a>
                    </div>
                </div>

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