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

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-5 sm:space-y-6">

            @if (session('success'))
                <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-xs sm:text-sm text-emerald-800 dark:text-emerald-300 font-semibold shadow-2xs">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-2xl border border-rose-500/20 bg-rose-500/10 p-4 text-xs sm:text-sm text-rose-800 dark:text-rose-300 font-semibold shadow-2xs">
                    {{ session('error') }}
                </div>
            @endif

            @if (! $student)
                <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-5 text-xs sm:text-sm text-amber-800 dark:text-amber-300 font-semibold">
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
                <div class="glass-liquid-card rounded-[1.75rem] p-5 sm:p-7 relative overflow-hidden">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-200/70 dark:border-white/10 pb-4 sm:pb-5">
                        <div class="flex items-center gap-3">
                            <span class="text-3xl sm:text-4xl">{{ $isUmmi ? '📗' : '📘' }}</span>
                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white flex items-center gap-2">
                                    <span>Program {{ $isUmmi ? 'Ummi (Kelas X)' : 'Reguler (Kelas XI/XII)' }}</span>
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    {{ $isUmmi ? 'Tahsin Ummi & Halaman Hafalan' : 'Target Baris & Hafalan Periodik' }}
                                </p>
                            </div>
                        </div>

                        <div>
                            @if ($statusColor === 'emerald')
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                    <span>{{ $statusIcon }}</span> {{ $statusLabel }}
                                </span>
                            @elseif ($statusColor === 'amber')
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/20">
                                    <span>{{ $statusIcon }}</span> {{ $statusLabel }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/20">
                                    <span>{{ $statusIcon }}</span> {{ $statusLabel }}
                                </span>
                            @endif
                        </div>
                    </div>

                    @if ($isUmmi)
                        {{-- ─── PROGRAM UMMI UI (KELAS 10) ─── --}}
                        <div class="mt-4 sm:mt-5 grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                            <div class="rounded-2xl glass-liquid-inner p-4">
                                <p class="text-[10px] sm:text-xs font-bold text-teal-800 dark:text-teal-300 uppercase tracking-wider mb-1 flex items-center gap-1">
                                    <span>📖</span> Jilid Ummi Aktif
                                </p>
                                <p class="text-xl sm:text-2xl font-black text-teal-900 dark:text-teal-100">
                                    {{ data_get($progress, 'current_jilid', '-') }}
                                </p>
                            </div>
                            <div class="rounded-2xl glass-liquid-inner p-4">
                                <p class="text-[10px] sm:text-xs font-bold text-teal-800 dark:text-teal-300 uppercase tracking-wider mb-1 flex items-center gap-1">
                                    <span>📑</span> Target Halaman
                                </p>
                                <p class="text-xl sm:text-2xl font-black text-teal-900 dark:text-teal-100">
                                    {{ data_get($progress, 'current_halaman', '-') }}
                                </p>
                            </div>
                            <div class="rounded-2xl glass-liquid-inner p-4">
                                <p class="text-[10px] sm:text-xs font-bold text-teal-800 dark:text-teal-300 uppercase tracking-wider mb-1 flex items-center gap-1">
                                    <span>🏆</span> Status Kenaikan
                                </p>
                                <p class="text-sm sm:text-base font-bold text-teal-900 dark:text-teal-100 mt-1">
                                    {{ data_get($progress, 'ummi_notes', 'Sedang Bimbingan') }}
                                </p>
                            </div>
                        </div>
                    @else
                        {{-- ─── PROGRAM REGULER UI (KELAS 11 & 12) ─── --}}
                        <div class="mt-4 sm:mt-5 grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                            <div class="rounded-2xl glass-liquid-inner p-4">
                                <p class="text-[10px] sm:text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Capaian Ayat</p>
                                <p class="text-xl sm:text-2xl font-black text-zinc-900 dark:text-white">
                                    {{ number_format(data_get($progress, 'memorized_ayahs', 0)) }}
                                    <span class="text-xs font-normal text-zinc-400">/ {{ number_format(data_get($progress, 'target_total_ayahs', 6236)) }}</span>
                                </p>
                            </div>
                            <div class="rounded-2xl glass-liquid-inner p-4">
                                <p class="text-[10px] sm:text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Target Juz</p>
                                <p class="text-xl sm:text-2xl font-black text-zinc-900 dark:text-white">
                                    {{ data_get($progress, 'target_juz_label', 'Juz 30') }}
                                </p>
                            </div>
                            <div class="rounded-2xl glass-liquid-inner p-4">
                                <p class="text-[10px] sm:text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1">Progres Total</p>
                                <p class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400">
                                    {{ number_format($progressPercent, 1) }}%
                                </p>
                            </div>
                        </div>
                    @endif
                </div>

                <x-student-hafalan-journey :milestones="data_get($progress, 'term_milestones', [])" />

                {{-- ═══════════════ PROFIL & PROGRESS HAFALAN GRID ═══════════════ --}}
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    {{-- Profil Singkat --}}
                    <div class="glass-liquid-card rounded-[1.75rem] p-5 sm:p-6 space-y-4">
                        <div class="flex items-center gap-3 border-b border-zinc-200/70 dark:border-white/10 pb-4">
                            <div class="w-12 h-12 rounded-2xl bg-teal-500/15 text-teal-700 dark:text-teal-300 font-black text-lg flex items-center justify-center shrink-0 border border-teal-500/20 shadow-xs">
                                {{ substr($student->name, 0, 1) }}
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-base font-bold text-zinc-900 dark:text-white truncate">{{ $student->name }}</h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">NIS: {{ $student->student_number ?? '-' }}</p>
                            </div>
                        </div>

                        <dl class="space-y-2.5 text-xs">
                            <div class="flex justify-between items-center py-1 border-b border-zinc-200/40 dark:border-white/5">
                                <dt class="text-zinc-500 dark:text-zinc-400">Kelas</dt>
                                <dd class="font-bold text-zinc-900 dark:text-white">{{ $student->classRoom?->name ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-zinc-200/40 dark:border-white/5">
                                <dt class="text-zinc-500 dark:text-zinc-400">Program</dt>
                                <dd class="font-bold text-zinc-900 dark:text-white">{{ $student->classRoom?->program?->name ?? '-' }}</dd>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <dt class="text-zinc-500 dark:text-zinc-400">Guru Bimbingan</dt>
                                <dd class="font-bold text-zinc-900 dark:text-white truncate max-w-[170px]">{{ $student->teacher?->user?->name ?? '-' }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Progress Hafalan --}}
                    <div class="glass-liquid-card rounded-[1.75rem] p-5 sm:p-6 lg:col-span-2 space-y-4 border border-teal-500/20 shadow-md">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-zinc-200/70 dark:border-white/10 pb-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-base font-bold text-zinc-900 dark:text-white">Progress Hafalan Al-Qur'an</h3>
                                    @if (!empty($progress['target_juz_label']))
                                        <span class="inline-flex items-center rounded-full bg-teal-500/15 px-3 py-0.5 text-xs font-bold text-teal-700 dark:text-teal-300 border border-teal-500/20">
                                            Target: {{ $progress['target_juz_label'] }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Setoran lulus sesuai target kurikulum program.
                                </p>
                            </div>

                            <div>
                                <span class="px-3 py-1 rounded-xl text-xs font-black bg-emerald-500 text-white shadow-xs">
                                    {{ $statusLabel }}
                                </span>
                            </div>
                        </div>

                        {{-- Circular Ring & Key Stats --}}
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-center">
                            <div class="sm:col-span-5 flex items-center justify-center py-2">
                                <div class="relative w-36 h-36 flex items-center justify-center">
                                    @php
                                        $pctStudent = min(100, max(0, (float) $progressPercent));
                                        $dashStudent = 2 * 3.14159 * 44;
                                        $offsetStudent = $dashStudent - ($pctStudent / 100) * $dashStudent;
                                    @endphp
                                    <svg class="w-full h-full transform -rotate-90 glow-teal-ring" viewBox="0 0 100 100">
                                        <circle cx="50" cy="50" r="44" stroke="currentColor" stroke-width="8" class="text-zinc-200 dark:text-zinc-800" fill="none" />
                                        <circle cx="50" cy="50" r="44" stroke="url(#studentTealGrad)" stroke-width="8" stroke-dasharray="{{ $dashStudent }}" stroke-dashoffset="{{ $offsetStudent }}" stroke-linecap="round" fill="none" />
                                        <defs>
                                            <linearGradient id="studentTealGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#0d9488" />
                                                <stop offset="100%" stop-color="#10b981" />
                                            </linearGradient>
                                        </defs>
                                    </svg>
                                    <div class="absolute flex flex-col items-center justify-center text-center">
                                        <span class="text-3xl font-black text-zinc-900 dark:text-white tracking-tight">{{ round($pctStudent) }}%</span>
                                        <span class="text-[10px] font-bold text-teal-600 dark:text-teal-400 uppercase">Tuntas</span>
                                    </div>
                                </div>
                            </div>

                            <div class="sm:col-span-7 space-y-3">
                                <div class="grid grid-cols-3 gap-2.5">
                                    <div class="rounded-2xl glass-liquid-inner p-3 text-center">
                                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 font-semibold">Setoran</p>
                                        <p class="text-lg sm:text-xl font-black text-zinc-900 dark:text-white mt-0.5">
                                            {{ number_format(data_get($progress, 'total_hafalan_records', 0)) }}
                                        </p>
                                    </div>

                                    <div class="rounded-2xl glass-liquid-inner p-3 text-center">
                                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 font-semibold">Murajaah</p>
                                        <p class="text-lg sm:text-xl font-black text-zinc-900 dark:text-white mt-0.5">
                                            {{ number_format(data_get($progress, 'total_murajaah_records', 0)) }}
                                        </p>
                                    </div>

                                    <div class="rounded-2xl glass-liquid-inner p-3 text-center">
                                        <p class="text-[10px] text-rose-600 dark:text-rose-400 font-semibold">Terlambat</p>
                                        <p class="text-lg sm:text-xl font-black text-rose-600 dark:text-rose-400 mt-0.5">
                                            {{ number_format(data_get($progress, 'overdue_targets', $overdueTargets->count())) }}
                                        </p>
                                    </div>
                                </div>

                                <p class="text-xs text-zinc-600 dark:text-zinc-400 font-medium">
                                    Tercatat: <strong class="text-zinc-900 dark:text-white">{{ number_format(data_get($progress, 'memorized_ayahs', 0)) }}</strong> dari {{ number_format(data_get($progress, 'target_total_ayahs', data_get($progress, 'total_quran_ayahs', 6236))) }} ayat target.
                                </p>

                                @if (Route::has('progress.show'))
                                    <a href="{{ route('progress.show', $student) }}"
                                       class="w-full inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 px-4 py-2.5 text-xs font-bold text-white transition-all shadow-md shadow-emerald-600/20">
                                        Lihat Detail Rapor &amp; Progres Lengkap &rarr;
                                    </a>
                                @endif
                            </div>
                        </div>
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
                <div class="glass-liquid-card rounded-[1.75rem] p-5 sm:p-7 shadow-sm">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-2xl bg-teal-500/15 text-teal-600 dark:text-teal-400 flex items-center justify-center text-2xl shrink-0 shadow-2xs border border-teal-500/20">
                                🕋
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-zinc-900 dark:text-white">
                                    Kuisioner Adab Harian
                                </h3>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Pengisian mandiri adab &amp; karakter harian santri.
                                </p>
                            </div>
                        </div>

                        <div>
                            @if ($adabFilledToday)
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                    <span>✅</span> Sudah Diisi Hari Ini
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full text-xs font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/20 animate-pulse">
                                    <span>⚠️</span> Belum Diisi Hari Ini
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 pt-3.5 border-t border-zinc-200/70 dark:border-white/10 flex flex-wrap items-center gap-2.5 sm:gap-3">
                        @if (! $adabFilledToday)
                            <a href="{{ route('adab.create', $student) }}" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-2.5 text-xs sm:text-sm font-bold text-white transition shadow-sm">
                                ✏️ Isi Kuisioner Sekarang
                            </a>
                        @endif

                        <a href="{{ route('adab.show', $student) }}" class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 rounded-xl glass-liquid-inner hover:bg-white/60 dark:hover:bg-white/10 px-4 py-2.5 text-xs sm:text-sm font-semibold text-zinc-700 dark:text-zinc-300 transition">
                            📊 Laporan &amp; Grafik Adab &rarr;
                        </a>
                    </div>
                </div>

                {{-- ═══════════════ TARGET AKTIF & TERLAMBAT ═══════════════ --}}
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="glass-liquid-card rounded-[1.75rem] overflow-hidden">
                        <div class="border-b border-zinc-200/70 dark:border-white/10 px-5 py-4 flex items-center justify-between">
                            <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                <span>🎯</span> Target Aktif
                            </h3>
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-teal-500/10 text-teal-700 dark:text-teal-300 border border-teal-500/20">{{ $activeTargets->count() }} Target</span>
                        </div>

                        <div class="divide-y divide-zinc-200/60 dark:divide-white/5">
                            @forelse ($activeTargets as $target)
                                <div class="p-4 hover:bg-white/40 dark:hover:bg-white/[0.04] transition">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            @if ($target->ummi_jilid || $isUmmi)
                                                <p class="font-bold text-xs sm:text-sm text-teal-800 dark:text-teal-300">
                                                    📗 {{ $target->ummi_jilid ?? 'Target Ummi' }}
                                                </p>
                                                @if($target->surah)
                                                    <p class="text-xs text-teal-700 dark:text-teal-400 mt-0.5">
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

                                        <span class="rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 border border-emerald-500/20 shrink-0">
                                            Aktif
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 text-center text-xs text-zinc-500">Belum ada target aktif.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="glass-liquid-card rounded-[1.75rem] overflow-hidden">
                        <div class="border-b border-zinc-200/70 dark:border-white/10 px-5 py-4 flex items-center justify-between">
                            <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                <span>⚠️</span> Target Terlambat
                            </h3>
                            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-rose-500/15 text-rose-700 dark:text-rose-300 border border-rose-500/20">{{ $overdueTargets->count() }} Target</span>
                        </div>

                        <div class="divide-y divide-zinc-200/60 dark:divide-white/5">
                            @forelse ($overdueTargets as $target)
                                <div class="p-4 hover:bg-white/40 dark:hover:bg-white/[0.04] transition">
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
                                        <span class="rounded-full bg-rose-500/15 px-2.5 py-0.5 text-[10px] font-bold text-rose-700 dark:text-rose-300 border border-rose-500/20 shrink-0">
                                            Terlambat
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 text-center text-xs text-zinc-500">Tidak ada target terlambat.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- ═══════════════ HAFALAN & MURAJAAH TERBARU ═══════════════ --}}
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="glass-liquid-card rounded-[1.75rem] overflow-hidden">
                        <div class="border-b border-zinc-200/70 dark:border-white/10 px-5 py-4 flex items-center justify-between">
                            <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                <span>📖</span> Hafalan Terbaru
                            </h3>
                            <a href="{{ route('progress.index') }}" class="text-xs text-emerald-600 dark:text-emerald-400 font-bold hover:underline">Semua &rarr;</a>
                        </div>

                        <div class="divide-y divide-zinc-200/60 dark:divide-white/5">
                            @forelse ($latestHafalanRecords as $record)
                                <div class="p-4 hover:bg-white/40 dark:hover:bg-white/[0.04] transition">
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
                                            <span class="px-2.5 py-0.5 rounded-full text-[9px] sm:text-[10px] font-bold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border border-emerald-500/20">
                                                {{ $record->status ?? 'Lulus' }}
                                            </span>
                                            @if ($record->score !== null)
                                                <p class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 mt-1">Nilai: {{ number_format((float) $record->score, 1) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 text-center text-xs text-zinc-500">Belum ada hafalan.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="glass-liquid-card rounded-[1.75rem] overflow-hidden">
                        <div class="border-b border-zinc-200/70 dark:border-white/10 px-5 py-4 flex items-center justify-between">
                            <h3 class="text-sm sm:text-base font-bold text-zinc-900 dark:text-white flex items-center gap-1.5">
                                <span>🔄</span> Murajaah Terbaru
                            </h3>
                            <a href="{{ route('progress.index') }}" class="text-xs text-amber-600 dark:text-amber-400 font-bold hover:underline">Semua &rarr;</a>
                        </div>

                        <div class="divide-y divide-zinc-200/60 dark:divide-white/5">
                            @forelse ($latestMurajaahRecords as $record)
                                <div class="p-4 hover:bg-white/40 dark:hover:bg-white/[0.04] transition">
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
                                            <span class="px-2.5 py-0.5 rounded-full text-[9px] sm:text-[10px] font-bold bg-amber-500/15 text-amber-700 dark:text-amber-300 border border-amber-500/20">
                                                {{ $record->status ?? 'Lulus' }}
                                            </span>
                                            @if ($record->overall_score !== null)
                                                <p class="text-[10px] font-bold text-amber-600 dark:text-amber-400 mt-1">Nilai: {{ number_format((float) $record->overall_score, 1) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 text-center text-xs text-zinc-500">Belum ada murajaah.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>