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
                <div class="rounded-xl border border-amber-200 dark:border-amber-500/20 bg-amber-50 dark:bg-amber-950/20 p-5 text-sm text-amber-800 dark:text-amber-300">
                    Profil orangtua belum terhubung dengan akun ini.
                </div>
            @else
                {{-- 🌟 SYSTEM APRESIASI & HIGHLIGHTS DECK UNTUK ORANG TUA 🌟 --}}
                <div class="rounded-2xl bg-white dark:bg-zinc-900 p-6 shadow-md space-y-4 relative overflow-hidden">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="space-y-1.5">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 text-[11px] font-bold uppercase tracking-wider">
                                <x-heroicon-o-sparkles class="w-4 h-4 text-emerald-600 dark:text-emerald-300" /> Highlights Capaian Anak
                            </span>
                            <h3 class="text-xl sm:text-2xl font-black text-zinc-900 dark:text-white">
                                Assalamu'alaikum, Ayah / Bunda {{ $parent->user?->name ?? '' }}!
                            </h3>
                            <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 max-w-2xl leading-relaxed font-semibold">
                                Alhamdulillah, Ananda terus melangkah dalam menjaga hafalan Al-Qur'an. Mari senantiasa berikan motivasi &amp; apresiasi terbaik untuk setiap capaian ananda.
                            </p>
                        </div>

                        @if ($childrenProgress->isNotEmpty())
                            @php
                                $firstChild = $childrenProgress->first();
                                $lastSetoran = $latestHafalanRecords->first();
                            @endphp
                            <div class="rounded-2xl bg-emerald-50/80 dark:bg-emerald-950/50 p-4 min-w-[260px] text-left md:text-right shrink-0 shadow-xs">
                                <p class="text-[10px] font-extrabold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">Setoran Hafalan Terbaru Pekan Ini</p>
                                <p class="text-sm font-black text-zinc-900 dark:text-white mt-1">
                                    {{ $lastSetoran?->surah?->name_latin ?? 'Belum ada setoran' }} 
                                    {{ $lastSetoran ? '(Ayat '.$lastSetoran->ayah_start.'-'.$lastSetoran->ayah_end.')' : '' }}
                                </p>
                                <p class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400 mt-1">
                                    Murid: {{ $lastSetoran?->student?->name ?? data_get($firstChild, 'student_name', '-') }}
                                    {{ $lastSetoran?->submitted_at ? '· '.$lastSetoran->submitted_at->format('d M Y') : '' }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl bg-white dark:bg-zinc-900 p-5 shadow-md hover:shadow-lg transition-shadow duration-200">
                        <p class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Total Anak</p>
                        <p class="mt-2 text-3xl font-black text-zinc-900 dark:text-white">
                            {{ number_format(data_get($stats, 'total_children', $children->count())) }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-zinc-900 p-5 shadow-md hover:shadow-lg transition-shadow duration-200">
                        <p class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Target Aktif</p>
                        <p class="mt-2 text-3xl font-black text-zinc-900 dark:text-white">
                            {{ number_format(data_get($stats, 'active_targets', 0)) }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-zinc-900 p-5 shadow-md hover:shadow-lg transition-shadow duration-200">
                        <p class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Target Terlambat</p>
                        <p class="mt-2 text-3xl font-black text-red-650 dark:text-red-400">
                            {{ number_format(data_get($stats, 'overdue_targets', 0)) }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-zinc-900 p-5 shadow-md hover:shadow-lg transition-shadow duration-200">
                        <p class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Aktivitas Terbaru</p>
                        <p class="mt-2 text-3xl font-black text-zinc-900 dark:text-white">
                            {{ number_format($latestHafalanRecords->count() + $latestMurajaahRecords->count()) }}
                        </p>
                    </div>
                </div>

                {{-- ═══════════════ TAB SELECTOR & TARGET VS CAPAIAN PER ANAK ═══════════════ --}}
                <div x-data="{ activeChild: 0 }" class="space-y-6">

                    @if ($childrenProgress->count() > 1)
                        {{-- Tab Selector for multiple children --}}
                        <div class="flex items-center gap-2 overflow-x-auto border-b border-zinc-200 dark:border-zinc-800 pb-2">
                            <span class="text-xs font-extrabold uppercase tracking-wider text-zinc-600 dark:text-zinc-400 mr-2 flex items-center gap-1">
                                <x-heroicon-o-user-group class="w-4 h-4 text-zinc-500" /> Pilih Anak:
                            </span>
                            @foreach ($childrenProgress as $idx => $row)
                                <button @click="activeChild = {{ $idx }}"
                                        :class="activeChild === {{ $idx }} ? 'bg-emerald-600 text-white shadow-sm font-black' : 'bg-white dark:bg-zinc-900 text-zinc-700 dark:text-zinc-300 border border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800 font-bold'"
                                        class="flex items-center gap-2 rounded-xl px-4 py-2 text-sm transition">
                                    <x-heroicon-o-user class="w-4 h-4" />
                                    <span>{{ data_get($row, 'student_name', 'Anak '.($idx+1)) }}</span>
                                    <span class="text-xs font-normal opacity-80">({{ data_get($row, 'class_room_name', '-') }})</span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Cards for each child --}}
                    @forelse ($childrenProgress as $idx => $row)
                        @php
                            $student = data_get($row, 'student');
                            $className = data_get($row, 'class_room_name', $student?->classRoom?->name ?? '');
                            $classLevel = $student?->classRoom?->level ?? '';
                            $isGrade10Class = (bool) (
                                (preg_match('/\bX\b/i', $className) && !preg_match('/\b(XI|XII)\b/i', $className))
                                || preg_match('/\b10\b/i', $className)
                                || preg_match('/^X[-_\s]?E/i', $className)
                                || preg_match('/kelas\s*(X|10)/i', $className)
                                || (preg_match('/\bX\b/i', $classLevel) && !preg_match('/\b(XI|XII)\b/i', $classLevel))
                                || preg_match('/\b10\b/i', $classLevel)
                            ) && !preg_match('/\b(XI|XII|11|12)\b/i', $className);

                            $isUmmi = data_get($row, 'is_ummi_program', false) || $isGrade10Class;
                            $statusColor = data_get($row, 'status_color', 'emerald');
                            $statusLabel = data_get($row, 'status_label', 'On-Track / Tuntas');
                            $statusIcon = data_get($row, 'status_icon', '🟢');
                            $progressPercent = (float) data_get($row, 'progress_percent', 0);
                            $progressWidth = min(100, max(0, $progressPercent));
                        @endphp

                        <div x-show="activeChild === {{ $idx }}" x-transition class="rounded-2xl bg-white dark:bg-zinc-900 p-6 shadow-md space-y-5">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800 pb-4">
                                <div class="flex items-center gap-3">
                                    <span class="text-3xl">{{ $isUmmi ? '📗' : '📘' }}</span>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-lg font-extrabold text-zinc-900 dark:text-white">
                                                {{ data_get($row, 'student_name', $student?->name ?? '-') }}
                                            </h3>
                                            <span class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                                Kelas {{ data_get($row, 'class_room_name', '-') }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 font-medium">
                                            Program: {{ $isUmmi ? 'Ummi & Tahsin (Kelas X)' : 'Reguler Tahfizh (Kelas XI / XII)' }} · Nis: {{ data_get($row, 'student_number', '-') }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    @if ($statusColor === 'emerald')
                                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                                            <span>{{ $statusIcon }}</span> {{ $statusLabel }}
                                        </span>
                                    @elseif ($statusColor === 'amber')
                                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300">
                                            <span>{{ $statusIcon }}</span> {{ $statusLabel }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-bold bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300">
                                            <span>{{ $statusIcon }}</span> {{ $statusLabel }}
                                        </span>
                                    @endif

                                    @if ($student && Route::has('progress.show') && auth()->user()?->hasAnyRole(['super_admin', 'admin', 'teacher', 'coordinator_tahfizh', 'tanse']))
                                        <a href="{{ route('progress.show', $student) }}" class="inline-flex items-center justify-center gap-1 rounded-xl bg-zinc-900 dark:bg-zinc-100 px-3.5 py-1.5 text-xs font-bold text-white dark:text-zinc-900 hover:bg-zinc-800 transition">
                                            Detail Rapor →
                                        </a>
                                    @endif
                                </div>
                            </div>

                            @if ($isUmmi)
                                {{-- PROGRAM UMMI DETAILS --}}
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="rounded-xl bg-teal-50/50 dark:bg-teal-950/20 p-4">
                                        <p class="text-xs font-bold text-teal-800 dark:text-teal-300 uppercase tracking-wider mb-1 flex items-center gap-1">
                                            <x-heroicon-o-book-open class="w-4 h-4 text-teal-600 dark:text-teal-400" /> Jilid &amp; Halaman Saat Ini
                                        </p>
                                        <p class="text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($row, 'ummi_jilid_str', 'Jilid 1') }}</p>
                                        <p class="text-xs text-teal-700 dark:text-teal-400 mt-0.5 font-semibold">Halaman {{ data_get($row, 'ummi_halaman', '-') }} · Tatap Muka #{{ data_get($row, 'ummi_tatap_muka', '-') }}</p>
                                    </div>

                                    <div class="rounded-xl bg-zinc-50/50 dark:bg-zinc-800/30 p-4">
                                        <p class="text-xs font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider mb-1 flex items-center gap-1">
                                            <x-heroicon-o-check-badge class="w-4 h-4 text-emerald-600 dark:text-emerald-400" /> Target Metode Ummi (Kelas 10)
                                        </p>
                                        <p class="text-lg font-black text-zinc-900 dark:text-white">
                                            @if(data_get($row, 'ummi_target.ummi_jilid'))
                                                {{ data_get($row, 'ummi_target.ummi_jilid') }}
                                            @elseif(data_get($row, 'ummi_target.surah.name_latin'))
                                                {{ data_get($row, 'ummi_target.surah.name_latin') }}
                                            @else
                                                Target Sesuai Jilid
                                            @endif
                                        </p>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5 font-semibold">
                                            @if(data_get($row, 'ummi_target'))
                                                @if(data_get($row, 'ummi_target.halaman_peraga') || data_get($row, 'ummi_target.halaman_buku'))
                                                    Peraga: {{ data_get($row, 'ummi_target.halaman_peraga', '-') }} · Buku: {{ data_get($row, 'ummi_target.halaman_buku', '-') }}
                                                @elseif(data_get($row, 'ummi_target.ayah_start'))
                                                    Ayat {{ data_get($row, 'ummi_target.ayah_start') }} - {{ data_get($row, 'ummi_target.ayah_end') }}
                                                @else
                                                    Tahsin &amp; Hafalan Ummi
                                                @endif
                                            @else
                                                Tahsin &amp; Hafalan Ummi
                                            @endif
                                        </p>
                                    </div>

                                    <div class="rounded-xl bg-emerald-50/50 dark:bg-emerald-950/20 p-4">
                                        <p class="text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider mb-1 flex items-center gap-1">
                                            <x-heroicon-o-trophy class="w-4 h-4 text-emerald-600 dark:text-emerald-400" /> Nilai Munaqasyah / Penguji
                                        </p>
                                        <p class="text-2xl font-black text-zinc-900 dark:text-white">
                                            {{ data_get($row, 'ummi_munaqasyah_score') !== null ? number_format((float) data_get($row, 'ummi_munaqasyah_score'), 1) : '-' }}
                                        </p>
                                        <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-0.5 font-semibold">Evaluasi kelancaran &amp; tajwid</p>
                                    </div>
                                </div>

                                <div class="pt-2">
                                    <div class="flex items-center justify-between text-xs font-bold text-zinc-600 dark:text-zinc-400 mb-1.5">
                                        <span>Progress Ketercapaian Jilid Ummi</span>
                                        <span>{{ data_get($row, 'ummi_jilid_percent', 0) }}% (Jilid {{ data_get($row, 'ummi_jilid_num', 1) }} / 3 Dewasa)</span>
                                    </div>
                                    <div class="h-3 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <div class="h-3 rounded-full bg-teal-600 transition-all duration-300" style="width: {{ data_get($row, 'ummi_jilid_percent', 0) }}%"></div>
                                    </div>
                                </div>
                            @else
                                {{-- PROGRAM REGULER DETAILS --}}
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="rounded-xl bg-indigo-50/50 dark:bg-indigo-950/20 p-4">
                                        <p class="text-xs font-bold text-indigo-800 dark:text-indigo-300 uppercase tracking-wider mb-1 flex items-center gap-1">
                                            <x-heroicon-o-check-badge class="w-4 h-4 text-indigo-600 dark:text-indigo-400" /> Target Baris Harian
                                        </p>
                                        <p class="text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($row, 'level_baris', 5) }} Baris / Hari</p>
                                        <p class="text-xs text-indigo-700 dark:text-indigo-400 mt-0.5 font-semibold">Level: {{ ucfirst(data_get($row, 'tahfizh_level', 'reguler')) }}</p>
                                    </div>

                                    <div class="rounded-xl bg-emerald-50/50 dark:bg-emerald-950/20 p-4">
                                        <p class="text-xs font-bold text-emerald-800 dark:text-emerald-300 uppercase tracking-wider mb-1 flex items-center gap-1">
                                            <x-heroicon-o-chart-bar class="w-4 h-4 text-emerald-600 dark:text-emerald-400" /> Capaian Baris Bulan Ini
                                        </p>
                                        <p class="text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($row, 'capaian_baris_month', 0) }} / {{ data_get($row, 'target_baris_month', 100) }} Baris</p>
                                        <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-0.5 font-semibold">Ketercapaian: {{ data_get($row, 'reguler_baris_percent', 0) }}%</p>
                                    </div>

                                    <div class="rounded-xl bg-purple-50/50 dark:bg-purple-950/20 p-4">
                                        <p class="text-xs font-bold text-purple-800 dark:text-purple-300 uppercase tracking-wider mb-1 flex items-center gap-1">
                                            <x-heroicon-o-trophy class="w-4 h-4 text-purple-600 dark:text-purple-400" /> Total Hafalan Lengkap
                                        </p>
                                        <p class="text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($row, 'completed_juz_count', 0) }} Juz</p>
                                        <p class="text-xs text-purple-700 dark:text-purple-400 mt-0.5 font-semibold">{{ data_get($row, 'completed_juz_list', 'Belum ada Juz lengkap') }}</p>
                                    </div>
                                </div>

                                <div class="pt-2">
                                    <div class="flex items-center justify-between text-xs font-bold text-zinc-600 dark:text-zinc-400 mb-1.5">
                                        <span>Progress Ketercapaian Baris Setoran Bulan Ini</span>
                                        <span>{{ data_get($row, 'reguler_baris_percent', 0) }}%</span>
                                    </div>
                                    <div class="h-3 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <div class="h-3 rounded-full bg-emerald-600 transition-all duration-300" style="width: {{ data_get($row, 'reguler_baris_percent', 0) }}%"></div>
                                    </div>
                                </div>
                            @endif

                            {{-- ═══════════════ PETA PERJALANAN HAFALAN ANAK (4 TERM X 3 KELAS) ═══════════════ --}}
                            <div class="mt-4">
                                <x-student-hafalan-journey :milestones="data_get($row, 'term_milestones', [])" />
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl bg-white dark:bg-zinc-900 p-8 text-center text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-800">
                            Belum ada data anak.
                        </div>
                    @endforelse

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
                        <div class="rounded-2xl bg-white dark:bg-zinc-900 shadow-sm p-6 text-center text-sm text-zinc-500 dark:text-zinc-400 lg:col-span-2">
                            Belum ada data motivasi anak.
                        </div>
                    @endforelse
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="rounded-2xl bg-white dark:bg-zinc-900 shadow-sm overflow-hidden">
                        <div class="border-b border-zinc-100 dark:border-zinc-800/80 px-5 py-4">
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                                Target Terbaru
                            </h3>
                        </div>

                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                            @forelse ($latestTargets as $target)
                                @php
                                    $targetClass = $target->student?->classRoom?->name ?? '';
                                    $targetIsUmmi = (bool) $target->ummi_jilid || (bool) (
                                        (preg_match('/\bX\b/i', $targetClass) && !preg_match('/\b(XI|XII)\b/i', $targetClass))
                                        || preg_match('/\b10\b/i', $targetClass)
                                    ) && !preg_match('/\b(XI|XII|11|12)\b/i', $targetClass);
                                @endphp
                                <div class="p-5">
                                    <p class="font-semibold text-zinc-900 dark:text-white">
                                        {{ $target->student?->name ?? '-' }}
                                        <span class="text-xs text-zinc-400 font-normal">({{ $targetClass ?: '-' }})</span>
                                    </p>
                                    @if ($targetIsUmmi)
                                        <p class="mt-1 text-sm font-bold text-teal-800 dark:text-teal-300 flex items-center gap-1">
                                            <x-heroicon-o-book-open class="w-4 h-4 text-teal-600 dark:text-teal-400" /> {{ $target->ummi_jilid ?? 'Target Ummi' }}
                                            @if($target->halaman_peraga || $target->halaman_buku)
                                                <span class="text-xs font-normal text-teal-600 dark:text-teal-400 block sm:inline">(Peraga: {{ $target->halaman_peraga ?? '-' }}, Buku: {{ $target->halaman_buku ?? '-' }})</span>
                                            @endif
                                        </p>
                                        @if($target->surah)
                                            <p class="text-xs text-teal-700 dark:text-teal-400 mt-0.5 font-medium">
                                                Surah {{ $target->surah->name_latin }} (Ayat {{ $target->ayah_start ?? 1 }}-{{ $target->ayah_end ?? '-' }})
                                            </p>
                                        @endif
                                    @else
                                        <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300 font-semibold flex items-center gap-1">
                                            <x-heroicon-o-book-open class="w-4 h-4 text-indigo-600 dark:text-indigo-400" /> {{ $target->surah?->name_latin ?? '-' }}
                                            · Ayat {{ $target->ayah_start }} - {{ $target->ayah_end }}
                                        </p>
                                    @endif
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 font-medium">
                                        Target: {{ $target->target_date ? \Carbon\Carbon::parse($target->target_date)->format('d M Y') : '-' }}
                                    </p>
                                </div>
                            @empty
                                <div class="p-5 text-sm text-zinc-500 dark:text-zinc-400">
                                    Belum ada target.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-zinc-900 shadow-md overflow-hidden">
                        <div class="border-b border-zinc-100 dark:border-zinc-800/80 px-5 py-4">
                            <h3 class="text-base font-extrabold text-zinc-900 dark:text-white">
                                Hafalan Terbaru
                            </h3>
                        </div>

                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                            @forelse ($latestHafalanRecords as $record)
                                <div class="p-5">
                                    <p class="font-extrabold text-zinc-900 dark:text-white">
                                        {{ $record->student?->name ?? '-' }}
                                    </p>
                                    <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300 font-semibold">
                                        {{ $record->surah?->name_latin ?? '-' }}
                                        · Ayat {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                    </p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 font-medium">
                                        {{ $record->submitted_at ? \Carbon\Carbon::parse($record->submitted_at)->format('d M Y') : '-' }}
                                        · Status: {{ $record->status ?? '-' }}
                                    </p>
                                </div>
                            @empty
                                <div class="p-5 text-sm text-zinc-500 dark:text-zinc-400">
                                    Belum ada hafalan.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white dark:bg-zinc-900 shadow-md overflow-hidden">
                        <div class="border-b border-zinc-100 dark:border-zinc-800/80 px-5 py-4">
                            <h3 class="text-base font-extrabold text-zinc-900 dark:text-white">
                                Murajaah Terbaru
                            </h3>
                        </div>

                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                            @forelse ($latestMurajaahRecords as $record)
                                <div class="p-5">
                                    <p class="font-extrabold text-zinc-900 dark:text-white">
                                        {{ $record->student?->name ?? '-' }}
                                    </p>
                                    <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300 font-semibold">
                                        {{ $record->surah?->name_latin ?? '-' }}
                                        · Ayat {{ $record->ayah_start }} - {{ $record->ayah_end }}
                                    </p>
                                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400 font-medium">
                                        {{ $record->reviewed_at ? \Carbon\Carbon::parse($record->reviewed_at)->format('d M Y') : '-' }}
                                        · Status: {{ $record->status ?? '-' }}
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