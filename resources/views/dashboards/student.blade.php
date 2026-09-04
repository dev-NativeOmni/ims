<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-0.5">
            <h2 class="font-bold text-lg sm:text-xl text-zinc-900 dark:text-white leading-tight">
                Dashboard Murid
            </h2>
            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">
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

    <div class="py-4 sm:py-8">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">

            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/30 p-3.5 text-xs sm:text-sm text-emerald-800 dark:text-emerald-300">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-rose-200 dark:border-rose-800 bg-rose-50 dark:bg-rose-950/30 p-3.5 text-xs sm:text-sm text-rose-800 dark:text-rose-300">
                    {{ session('error') }}
                </div>
            @endif

            @if (! $student)
                <div class="rounded-xl border border-amber-200 dark:border-amber-500/20 bg-amber-50 dark:bg-amber-950/20 p-4 text-xs sm:text-sm text-amber-800 dark:text-amber-300">
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
                <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-4 sm:p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3 sm:pb-4">
                        <div class="flex items-center gap-2.5 sm:gap-3">
                            <span class="text-2xl sm:text-3xl">{{ $isUmmi ? '📗' : '📘' }}</span>
                            <div>
                                <h3 class="text-sm sm:text-lg font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                    <span>Program {{ $isUmmi ? 'Ummi (Kelas X)' : 'Reguler (Kelas XI/XII)' }}</span>
                                </h3>
                                <p class="text-[11px] sm:text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $isUmmi ? 'Tahsin Ummi & Halaman Hafalan' : 'Target Baris & Hafalan Periodik' }}
                                </p>
                            </div>
                        </div>

                        <div>
                            @if ($statusColor === 'emerald')
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    <span>{{ $statusIcon }}</span> {{ $statusLabel }}
                                </span>
                            @elseif ($statusColor === 'amber')
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                    <span>{{ $statusIcon }}</span> {{ $statusLabel }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                    <span>{{ $statusIcon }}</span> {{ $statusLabel }}
                                </span>
                            @endif
                        </div>
                    </div>

                    @if ($isUmmi)
                        {{-- ─── PROGRAM UMMI UI (KELAS 10) ─── --}}
                        <div class="mt-3.5 sm:mt-5 grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-4">
                            <div class="rounded-xl bg-teal-50/60 dark:bg-teal-950/20 border border-teal-100 dark:border-teal-900/40 p-3 sm:p-4">
                                <p class="text-[10px] sm:text-xs font-bold text-teal-800 dark:text-teal-300 uppercase tracking-wider mb-1 flex items-center gap-1">
                                    <x-heroicon-o-book-open class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" /> Jilid &amp; Halaman
                                </p>
                                <p class="text-lg sm:text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($progress, 'ummi_jilid_str', 'Jilid 1') }}</p>
                                <p class="text-[11px] text-teal-700 dark:text-teal-400 mt-0.5 font-semibold">Halaman {{ data_get($progress, 'ummi_halaman', '-') }} · Pertemuan #{{ data_get($progress, 'ummi_tatap_muka', '-') }}</p>
                            </div>

                            <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800/40 border border-zinc-200/60 dark:border-zinc-700/50 p-3 sm:p-4">
                                <p class="text-[10px] sm:text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-1 flex items-center gap-1">
                                    <x-heroicon-o-check-badge class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" /> Target Metode Ummi
                                </p>
                                <p class="text-sm sm:text-lg font-black text-zinc-900 dark:text-white truncate">
                                    @if(data_get($progress, 'ummi_target.ummi_jilid'))
                                        {{ data_get($progress, 'ummi_target.ummi_jilid') }}
                                    @elseif(data_get($progress, 'ummi_target.surah.name_latin'))
                                        {{ data_get($progress, 'ummi_target.surah.name_latin') }}
                                    @else
                                        Target Jilid
                                    @endif
                                </p>
                                <p class="text-[11px] text-zinc-600 dark:text-zinc-400 mt-0.5 font-semibold truncate">
                                    @if(data_get($progress, 'ummi_target'))
                                        @if(data_get($progress, 'ummi_target.halaman_peraga') || data_get($progress, 'ummi_target.halaman_buku'))
                                            Peraga: {{ data_get($progress, 'ummi_target.halaman_peraga', '-') }} · Buku: {{ data_get($progress, 'ummi_target.halaman_buku', '-') }}
                                        @elseif(data_get($progress, 'ummi_target.ayah_start'))
                                            Ayat {{ data_get($progress, 'ummi_target.ayah_start') }} - {{ data_get($progress, 'ummi_target.ayah_end') }}
                                        @else
                                            Tahsin &amp; Hafalan
                                        @endif
                                    @else
                                        Mengikuti alur Jilid
                                    @endif
                                </p>
                            </div>

                            <div class="rounded-xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/40 p-3 sm:p-4">
                                <p class="text-[10px] sm:text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider mb-1">🏆 Nilai Munaqasyah</p>
                                <p class="text-lg sm:text-2xl font-black text-zinc-900 dark:text-white">
                                    {{ data_get($progress, 'ummi_munaqasyah_score') !== null ? number_format((float) data_get($progress, 'ummi_munaqasyah_score'), 1) : '-' }}
                                </p>
                                <p class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-0.5 font-semibold">Kelancaran &amp; tajwid</p>
                            </div>
                        </div>

                        <div class="mt-3 sm:mt-4 pt-2">
                            <div class="flex items-center justify-between text-[11px] sm:text-xs font-bold text-zinc-600 dark:text-zinc-400 mb-1.5">
                                <span>Ketercapaian Jilid Ummi</span>
                                <span>{{ data_get($progress, 'ummi_jilid_percent', 0) }}% (Jilid {{ data_get($progress, 'ummi_jilid_num', 1) }} / 3)</span>
                            </div>
                            <div class="h-2 sm:h-3 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full bg-teal-600 transition-all duration-300" style="width: {{ data_get($progress, 'ummi_jilid_percent', 0) }}%"></div>
                            </div>
                        </div>
                    @else
                        {{-- ─── PROGRAM REGULER UI (KELAS 11 & 12) ─── --}}
                        <div class="mt-3.5 sm:mt-5 grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-4">
                            <div class="rounded-xl bg-indigo-50/60 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/40 p-3 sm:p-4">
                                <p class="text-[10px] sm:text-xs font-bold text-indigo-800 dark:text-indigo-300 uppercase tracking-wider mb-1">🎯 Target Harian</p>
                                <p class="text-lg sm:text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($progress, 'level_baris', 5) }} Baris / Hari</p>
                                <p class="text-[11px] text-indigo-700 dark:text-indigo-400 mt-0.5 font-semibold">Level: {{ ucfirst(data_get($progress, 'tahfizh_level', 'reguler')) }}</p>
                            </div>

                            <div class="rounded-xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/40 p-3 sm:p-4">
                                <p class="text-[10px] sm:text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider mb-1">📊 Capaian Bulan Ini</p>
                                <p class="text-lg sm:text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($progress, 'capaian_baris_month', 0) }} / {{ data_get($progress, 'target_baris_month', 100) }} Baris</p>
                                <p class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-0.5 font-semibold">Ketercapaian: {{ data_get($progress, 'reguler_baris_percent', 0) }}%</p>
                            </div>

                            <div class="rounded-xl bg-purple-50/60 dark:bg-purple-950/20 border border-purple-100 dark:border-purple-900/40 p-3 sm:p-4">
                                <p class="text-[10px] sm:text-xs font-bold text-purple-800 dark:text-purple-300 uppercase tracking-wider mb-1">🏆 Total Hafalan Lengkap</p>
                                <p class="text-lg sm:text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($progress, 'completed_juz_count', 0) }} Juz</p>
                                <p class="text-[11px] text-purple-700 dark:text-purple-400 mt-0.5 font-semibold truncate">{{ data_get($progress, 'completed_juz_list', 'Belum ada Juz lengkap') }}</p>
                            </div>
                        </div>

                        <div class="mt-3 sm:mt-4 pt-2">
                            <div class="flex items-center justify-between text-[11px] sm:text-xs font-bold text-zinc-600 dark:text-zinc-400 mb-1.5">
                                <span>Progress Baris Bulan Ini</span>
                                <span>{{ data_get($progress, 'reguler_baris_percent', 0) }}%</span>
                            </div>
                            <div class="h-2 sm:h-3 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <div class="h-full rounded-full bg-emerald-600 transition-all duration-300" style="width: {{ data_get($progress, 'reguler_baris_percent', 0) }}%"></div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ═══════════════ PETA PERJALANAN HAFALAN ═══════════════ --}}
                <x-student-hafalan-journey :milestones="data_get($progress, 'term_milestones', [])" />

                {{-- ═══════════════ PROFIL & PROGRESS HAFALAN ═══════════════ --}}
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    {{-- Profil Murid --}}
                    <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-4 sm:p-5 shadow-sm">
                        <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white border-b border-zinc-100 dark:border-zinc-800 pb-2">
                            Profil Santri
                        </h3>

                        <dl class="mt-3 space-y-2.5 text-xs sm:text-sm">
                            <div class="flex justify-between items-center">
                                <dt class="text-zinc-500 dark:text-zinc-400">Nama</dt>
                                <dd class="font-bold text-zinc-900 dark:text-white truncate max-w-[180px]">{{ $student->name }}</dd>
                            </div>
                            <div class="flex justify-between items-center">
                                <dt class="text-zinc-500 dark:text-zinc-400">NIS</dt>
                                <dd class="font-bold text-zinc-900 dark:text-white">{{ $student->student_number ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between items-center">
                                <dt class="text-zinc-500 dark:text-zinc-400">Kelas</dt>
                                <dd class="font-bold text-zinc-900 dark:text-white">{{ $student->classRoom?->name ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between items-center">
                                <dt class="text-zinc-500 dark:text-zinc-400">Program</dt>
                                <dd class="font-bold text-zinc-900 dark:text-white">{{ $student->classRoom?->program?->name ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between items-center">
                                <dt class="text-zinc-500 dark:text-zinc-400">Guru</dt>
                                <dd class="font-bold text-zinc-900 dark:text-white truncate max-w-[180px]">{{ $student->teacher?->user?->name ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Progress Hafalan --}}
                    <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-4 sm:p-5 shadow-sm lg:col-span-2 space-y-3 sm:space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-zinc-100 dark:border-zinc-800 pb-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white">Progress Hafalan</h3>
                                    @if (!empty($progress['target_juz_label']))
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-950 px-2.5 py-0.5 text-[10px] sm:text-xs font-bold text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            Target: {{ $progress['target_juz_label'] }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-[11px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Setoran lulus sesuai target kurikulum program.
                                </p>
                            </div>

                            <div class="text-left sm:text-right">
                                <p class="text-2xl sm:text-4xl font-black text-zinc-900 dark:text-white leading-tight">
                                    {{ number_format($progressPercent, 2) }}%
                                </p>
                                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                    {{ number_format(data_get($progress, 'memorized_ayahs', 0)) }} / {{ number_format(data_get($progress, 'target_total_ayahs', data_get($progress, 'total_quran_ayahs', 6236))) }} ayat
                                </p>
                            </div>
                        </div>

                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-300" style="width: {{ $progressWidth }}%"></div>
                        </div>

                        {{-- 3 Mini Stat Boxes on Mobile --}}
                        <div class="grid grid-cols-3 gap-2 sm:gap-4 pt-1">
                            <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800/40 p-2.5 sm:p-3.5 text-center">
                                <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 font-semibold">Setoran</p>
                                <p class="text-base sm:text-2xl font-black text-zinc-900 dark:text-white mt-0.5">
                                    {{ number_format(data_get($progress, 'total_hafalan_records', 0)) }}
                                </p>
                            </div>

                            <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800/40 p-2.5 sm:p-3.5 text-center">
                                <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 font-semibold">Murajaah</p>
                                <p class="text-base sm:text-2xl font-black text-zinc-900 dark:text-white mt-0.5">
                                    {{ number_format(data_get($progress, 'total_murajaah_records', 0)) }}
                                </p>
                            </div>

                            <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800/40 p-2.5 sm:p-3.5 text-center">
                                <p class="text-[10px] sm:text-xs text-rose-600 dark:text-rose-400 font-semibold">Terlambat</p>
                                <p class="text-base sm:text-2xl font-black text-rose-600 dark:text-rose-400 mt-0.5">
                                    {{ number_format(data_get($progress, 'overdue_targets', $overdueTargets->count())) }}
                                </p>
                            </div>
                        </div>

                        @if (Route::has('progress.show'))
                            <div class="pt-1">
                                <a href="{{ route('progress.show', $student) }}"
                                   class="w-full inline-flex items-center justify-center rounded-xl bg-zinc-900 dark:bg-zinc-100 px-4 py-2.5 text-xs sm:text-sm font-bold text-white dark:text-zinc-900 hover:bg-zinc-800 dark:hover:bg-zinc-200 transition-colors shadow-sm">
                                    Lihat Detail Progress Lengkap &rarr;
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

                {{-- ═══════════════ QUICK ACCESS KUISIONER ADAB ═══════════════ --}}
                <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-4 sm:p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 text-xl sm:text-2xl shrink-0">
                                🕋
                            </div>
                            <div>
                                <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white">
                                    Kuisioner Adab Harian
                                </h3>
                                <p class="text-[11px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Pengisian mandiri adab &amp; karakter harian santri.
                                </p>
                            </div>
                        </div>

                        <div>
                            @if ($adabFilledToday)
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                    <span>✅</span> Sudah Diisi Hari Ini
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 animate-pulse">
                                    <span>⚠️</span> Belum Diisi Hari Ini
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-3.5 pt-3 border-t border-zinc-100 dark:border-zinc-800 flex flex-wrap items-center gap-2 sm:gap-3">
                        @if (! $adabFilledToday)
                            <a href="{{ route('adab.create', $student) }}" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 text-xs sm:text-sm font-bold text-white transition shadow-sm">
                                ✏️ Isi Kuisioner Sekarang
                            </a>
                        @endif

                        <a href="{{ route('adab.show', $student) }}" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 px-4 py-2.5 text-xs sm:text-sm font-semibold text-zinc-700 dark:text-zinc-300 transition">
                            📊 Laporan &amp; Grafik Adab
                        </a>
                    </div>
                </div>

                {{-- ═══════════════ TARGET AKTIF & TERLAMBAT ═══════════════ --}}
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 shadow-sm overflow-hidden">
                        <div class="border-b border-zinc-100 dark:border-zinc-800 px-4 py-3 sm:px-5 sm:py-3.5 flex items-center justify-between">
                            <h3 class="text-xs sm:text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                <span>🎯</span> Target Aktif
                            </h3>
                            <span class="text-[11px] font-semibold text-zinc-400">{{ $activeTargets->count() }} Target</span>
                        </div>

                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($activeTargets as $target)
                                <div class="p-3 sm:p-4 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            @if ($target->ummi_jilid || $isUmmi)
                                                <p class="font-bold text-xs sm:text-sm text-teal-800 dark:text-teal-300">
                                                    📗 {{ $target->ummi_jilid ?? 'Target Ummi' }}
                                                </p>
                                                @if($target->surah)
                                                    <p class="text-[11px] text-teal-700 dark:text-teal-400 mt-0.5">
                                                        QS. {{ $target->surah->name_latin }} (Ayat {{ $target->ayah_start ?? 1 }}-{{ $target->ayah_end ?? '-' }})
                                                    </p>
                                                @endif
                                            @else
                                                <p class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">
                                                    📘 QS. {{ $target->surah?->name_latin ?? '-' }} : {{ $target->ayah_start }} - {{ $target->ayah_end }}
                                                </p>
                                            @endif
                                            <p class="mt-0.5 text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400">
                                                Tenggat: {{ $target->target_date ? \Carbon\Carbon::parse($target->target_date)->format('d M Y') : '-' }}
                                            </p>
                                        </div>

                                        <span class="rounded-full bg-emerald-50 dark:bg-emerald-950 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 shrink-0">
                                            Aktif
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-center text-xs text-zinc-500">Belum ada target aktif.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 shadow-sm overflow-hidden">
                        <div class="border-b border-zinc-100 dark:border-zinc-800 px-4 py-3 sm:px-5 sm:py-3.5 flex items-center justify-between">
                            <h3 class="text-xs sm:text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                <span>⚠️</span> Target Terlambat
                            </h3>
                            <span class="text-[11px] font-semibold text-rose-500">{{ $overdueTargets->count() }} Target</span>
                        </div>

                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($overdueTargets as $target)
                                <div class="p-3 sm:p-4 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            @if ($target->ummi_jilid || $isUmmi)
                                                <p class="font-bold text-xs sm:text-sm text-teal-800 dark:text-teal-300">
                                                    📗 {{ $target->ummi_jilid ?? 'Target Ummi' }}
                                                </p>
                                            @else
                                                <p class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">
                                                    📘 QS. {{ $target->surah?->name_latin ?? '-' }} : {{ $target->ayah_start }} - {{ $target->ayah_end }}
                                                </p>
                                            @endif
                                            <p class="mt-0.5 text-[10px] sm:text-xs font-semibold text-rose-600 dark:text-rose-400">
                                                Lewat dari {{ $target->target_date ? \Carbon\Carbon::parse($target->target_date)->format('d M Y') : '-' }}
                                            </p>
                                        </div>
                                        <span class="rounded-full bg-rose-50 dark:bg-rose-950 px-2.5 py-0.5 text-[10px] font-bold text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 shrink-0">
                                            Terlambat
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-center text-xs text-zinc-500">Tidak ada target terlambat.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- ═══════════════ HAFALAN & MURAJAAH TERBARU ═══════════════ --}}
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 shadow-sm overflow-hidden">
                        <div class="border-b border-zinc-100 dark:border-zinc-800 px-4 py-3 sm:px-5 sm:py-3.5 flex items-center justify-between">
                            <h3 class="text-xs sm:text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                <span>📖</span> Hafalan Terbaru
                            </h3>
                            <a href="{{ route('progress.index') }}" class="text-xs text-emerald-600 dark:text-emerald-400 font-bold hover:underline">Semua</a>
                        </div>

                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($latestHafalanRecords as $record)
                                <div class="p-3 sm:p-4 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                    <div class="flex justify-between items-start gap-2">
                                        <div class="min-w-0">
                                            <p class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">
                                                QS. {{ $record->surah?->name_latin ?? '-' }} : {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                            </p>
                                            <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                                {{ $record->submitted_at ? \Carbon\Carbon::parse($record->submitted_at)->format('d M Y') : '-' }}
                                            </p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="px-2 py-0.5 rounded-full text-[9px] sm:text-[10px] font-bold bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                                                {{ $record->status ?? 'Lulus' }}
                                            </span>
                                            @if ($record->score !== null)
                                                <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">Nilai: {{ number_format((float) $record->score, 1) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-center text-xs text-zinc-500">Belum ada hafalan.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 shadow-sm overflow-hidden">
                        <div class="border-b border-zinc-100 dark:border-zinc-800 px-4 py-3 sm:px-5 sm:py-3.5 flex items-center justify-between">
                            <h3 class="text-xs sm:text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                <span>🔄</span> Murajaah Terbaru
                            </h3>
                            <a href="{{ route('progress.index') }}" class="text-xs text-amber-600 dark:text-amber-400 font-bold hover:underline">Semua</a>
                        </div>

                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($latestMurajaahRecords as $record)
                                <div class="p-3 sm:p-4 hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                                    <div class="flex justify-between items-start gap-2">
                                        <div class="min-w-0">
                                            <p class="font-bold text-xs sm:text-sm text-zinc-900 dark:text-white truncate">
                                                QS. {{ $record->surah?->name_latin ?? '-' }} : {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                            </p>
                                            <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                                {{ $record->reviewed_at ? \Carbon\Carbon::parse($record->reviewed_at)->format('d M Y') : '-' }}
                                            </p>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <span class="px-2 py-0.5 rounded-full text-[9px] sm:text-[10px] font-bold bg-amber-50 dark:bg-amber-950 text-amber-700 dark:text-amber-300">
                                                {{ $record->status ?? 'Lulus' }}
                                            </span>
                                            @if ($record->overall_score !== null)
                                                <p class="text-[10px] font-bold text-amber-600 dark:text-amber-400 mt-0.5">Nilai: {{ number_format((float) $record->overall_score, 1) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-4 text-center text-xs text-zinc-500">Belum ada murajaah.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>