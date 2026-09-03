<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-bold leading-tight text-zinc-900 dark:text-white">
                Dashboard Orang Tua
            </h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Pusat monitoring terpadu perkembangan Tahfizh, Adab, dan Kedisiplinan Ananda.
            </p>
        </div>
    </x-slot>

    @php
        $parent = data_get($stats, 'parent');
        $children = collect(data_get($stats, 'children', []));
        $childrenProgress = collect(data_get($stats, 'children_progress', []));
        $childrenMotivation = collect(data_get($stats, 'children_motivation', []));
    @endphp

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (! $parent)
                <div class="rounded-2xl border border-amber-200 dark:border-amber-500/20 bg-amber-50 dark:bg-amber-950/20 p-5 text-sm text-amber-800 dark:text-amber-300">
                    Profil orang tua belum terhubung dengan akun ini. Silakan hubungi admin sekolah.
                </div>
            @elseif ($childrenProgress->isEmpty())
                <div class="rounded-2xl bg-white dark:bg-zinc-900 p-8 text-center border border-zinc-200 dark:border-zinc-800 shadow-sm">
                    <p class="text-zinc-500 dark:text-zinc-400 font-medium text-sm">
                        Belum ada data murid yang ditautkan ke akun Anda.
                    </p>
                </div>
            @else
                {{-- 🌟 APPRECIATION & HIGHLIGHTS BANNER (SCENE 1 & 2) 🌟 --}}
                <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-6 sm:p-7 shadow-sm relative overflow-hidden">
                    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
                        <div class="space-y-2">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 text-xs font-bold uppercase tracking-wider">
                                <x-heroicon-o-sparkles class="w-4 h-4 text-emerald-600 dark:text-emerald-300" /> Portal Khusus Wali Murid
                            </span>
                            <h3 class="text-xl sm:text-2xl font-black text-zinc-900 dark:text-white">
                                Assalamu'alaikum, Ayah / Bunda {{ $parent->user?->name ?? '' }}!
                            </h3>
                            <p class="text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 max-w-2xl leading-relaxed font-medium">
                                Ruang sinergi antara sekolah dan orang tua untuk memantau setiap jejak langkah ananda dalam menghafal Al-Qur'an, menumbuhkan adab islami, dan mengukir prestasi terbaiknya.
                            </p>
                        </div>

                        <div class="flex items-center gap-3 shrink-0">
                            <div class="rounded-xl bg-emerald-50 dark:bg-zinc-800/80 px-4 py-3 border border-emerald-100 dark:border-zinc-700/60 text-center">
                                <p class="text-[10px] uppercase font-bold text-emerald-700 dark:text-emerald-300 tracking-wider">Total Ananda</p>
                                <p class="text-2xl font-black text-zinc-900 dark:text-white mt-0.5">{{ $childrenProgress->count() }} Murid</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═══════════════ MULTI-CHILD MONITORING HUB (SCENE 2) ═══════════════ --}}
                <div x-data="{ activeChild: 0 }" class="space-y-6">

                    @if ($childrenProgress->count() > 1)
                        {{-- Tab Selector for multiple children --}}
                        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-2.5 rounded-2xl shadow-sm flex items-center gap-2 overflow-x-auto scrollbar-none">
                            <span class="text-xs font-black uppercase tracking-wider text-zinc-400 dark:text-zinc-500 px-3 flex items-center gap-1.5 shrink-0">
                                <x-heroicon-o-user-group class="w-4 h-4" /> Pilih Ananda:
                            </span>
                            @foreach ($childrenProgress as $idx => $row)
                                <button @click="activeChild = {{ $idx }}"
                                        :class="activeChild === {{ $idx }} ? 'bg-emerald-600 text-white shadow-md font-extrabold' : 'bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 font-semibold'"
                                        class="flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs sm:text-sm transition-all duration-150 shrink-0 cursor-pointer">
                                    <x-heroicon-o-user class="w-4 h-4" />
                                    <span>{{ data_get($row, 'student_name', 'Ananda '.($idx+1)) }}</span>
                                    <span class="text-[11px] opacity-80">({{ data_get($row, 'class_room_name', '-') }})</span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    {{-- Individual Full Profile Cards per Child --}}
                    @foreach ($childrenProgress as $idx => $row)
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

                            $adabData = data_get($row, 'adab', []);
                            $tanseData = data_get($row, 'tanse', []);
                            $studentHafalan = collect(data_get($row, 'student_hafalan', []));
                            $studentMurajaah = collect(data_get($row, 'student_murajaah', []));
                            $studentTargets = collect(data_get($row, 'student_targets', []));
                        @endphp

                        <div x-show="activeChild === {{ $idx }}" x-transition class="space-y-6" x-data="{ childTab: 'tahfizh' }">
                            
                            {{-- Header Ananda --}}
                            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-zinc-100 dark:border-zinc-800 pb-4">
                                    <div class="flex items-center gap-3.5">
                                        <div class="w-12 h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-black text-xl shrink-0">
                                            {{ substr(data_get($row, 'student_name', 'A'), 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h3 class="text-lg sm:text-xl font-black text-zinc-900 dark:text-white">
                                                    {{ data_get($row, 'student_name', $student?->name ?? '-') }}
                                                </h3>
                                                <span class="px-2.5 py-0.5 rounded-lg text-xs font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                                    Kelas {{ data_get($row, 'class_room_name', '-') }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 font-medium">
                                                NIS: <strong class="text-zinc-700 dark:text-zinc-300">{{ data_get($row, 'student_number', '-') }}</strong> · 
                                                Program: <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $isUmmi ? 'Metode Ummi & Tahsin (Kelas 10)' : 'Reguler Tahfizh Al-Qur\'an' }}</span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            <span>{{ $statusIcon }}</span> {{ $statusLabel }}
                                        </span>
                                        <a href="{{ route('quran.mushaf') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 font-bold text-xs transition border border-zinc-200 dark:border-zinc-700">
                                            <span>📖 Mushaf Digital</span>
                                        </a>
                                        <a href="{{ route('progress.show', $student) }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs transition shadow-sm">
                                            <span>📊 Arsip Rapor Lengkap</span> →
                                        </a>
                                    </div>
                                </div>

                                {{-- Quick 3-Category Status Row --}}
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    {{-- Tahfizh Pill --}}
                                    <div class="rounded-xl bg-indigo-50/60 dark:bg-indigo-950/30 p-3.5 border border-indigo-100 dark:border-indigo-900/40 flex items-center justify-between">
                                        <div>
                                            <p class="text-[10px] font-black uppercase text-indigo-700 dark:text-indigo-400">📖 Tahfizh Al-Qur'an</p>
                                            <p class="text-base font-black text-zinc-900 dark:text-white mt-0.5">
                                                {{ $isUmmi ? data_get($row, 'ummi_jilid_str', 'Jilid 1') : data_get($row, 'completed_juz_count', 0).' Juz Lengkap' }}
                                            </p>
                                        </div>
                                        <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-white dark:bg-zinc-800 px-2 py-1 rounded-lg shadow-xs">
                                            {{ number_format((float) data_get($row, 'progress_percent', 0), 1) }}% Target
                                        </span>
                                    </div>

                                    {{-- Adab Pill --}}
                                    <div class="rounded-xl bg-teal-50/60 dark:bg-teal-950/30 p-3.5 border border-teal-100 dark:border-teal-900/40 flex items-center justify-between">
                                        <div>
                                            <p class="text-[10px] font-black uppercase text-teal-700 dark:text-teal-400">🕌 Karakter &amp; Adab</p>
                                            <p class="text-base font-black text-zinc-900 dark:text-white mt-0.5">
                                                Nilai: {{ data_get($adabData, 'final_score', '-') }} (Grade {{ data_get($adabData, 'grade', '-') }})
                                            </p>
                                        </div>
                                        <span class="text-xs font-bold {{ data_get($adabData, 'today_record') ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950' : 'text-amber-600 bg-amber-50 dark:bg-amber-950' }} px-2 py-1 rounded-lg border border-current/20">
                                            {{ data_get($adabData, 'today_record') ? '✓ Terisi Hari Ini' : 'Belum Terisi' }}
                                        </span>
                                    </div>

                                    {{-- Tanse / Poin Pill --}}
                                    <div class="rounded-xl bg-purple-50/60 dark:bg-purple-950/30 p-3.5 border border-purple-100 dark:border-purple-900/40 flex items-center justify-between">
                                        <div>
                                            <p class="text-[10px] font-black uppercase text-purple-700 dark:text-purple-400">⭐ Kedisiplinan &amp; Prestasi</p>
                                            <p class="text-base font-black text-zinc-900 dark:text-white mt-0.5">
                                                {{ data_get($tanseData, 'reward_points', 0) }} Poin Reward
                                            </p>
                                        </div>
                                        <span class="text-xs font-bold {{ data_get($tanseData, 'violation_points', 0) > 0 ? 'text-rose-600 bg-rose-50 dark:bg-rose-950' : 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950' }} px-2 py-1 rounded-lg border border-current/20">
                                            {{ data_get($tanseData, 'violation_points', 0) }} Pelanggaran
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Navigation Sub-Tabs per Child --}}
                            <div class="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-800 pb-1">
                                <button @click="childTab = 'tahfizh'"
                                        :class="childTab === 'tahfizh' ? 'bg-emerald-600 text-white font-black shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 font-bold'"
                                        class="px-4 py-2.5 rounded-xl text-xs sm:text-sm transition flex items-center gap-1.5 cursor-pointer">
                                    <span>📖</span> Tahfizh &amp; Target
                                </button>
                                <button @click="childTab = 'adab'"
                                        :class="childTab === 'adab' ? 'bg-emerald-600 text-white font-black shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 font-bold'"
                                        class="px-4 py-2.5 rounded-xl text-xs sm:text-sm transition flex items-center gap-1.5 cursor-pointer">
                                    <span>🕌</span> Catatan Adab
                                </button>
                                <button @click="childTab = 'tanse'"
                                        :class="childTab === 'tanse' ? 'bg-emerald-600 text-white font-black shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 font-bold'"
                                        class="px-4 py-2.5 rounded-xl text-xs sm:text-sm transition flex items-center gap-1.5 cursor-pointer">
                                    <span>⭐</span> Kedisiplinan &amp; Prestasi
                                </button>
                            </div>

                            {{-- ═══════════════ TAB 1: TAHFIZH & TARGET ═══════════════ --}}
                            <div x-show="childTab === 'tahfizh'" x-transition class="space-y-6">
                                
                                @if ($isUmmi)
                                    {{-- PROGRAM UMMI DETAILS --}}
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-5 shadow-sm">
                                            <p class="text-xs font-bold text-teal-700 dark:text-teal-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                                <x-heroicon-o-book-open class="w-4 h-4" /> Jilid &amp; Halaman Saat Ini
                                            </p>
                                            <p class="text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($row, 'ummi_jilid_str', 'Jilid 1') }}</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 font-semibold">Halaman {{ data_get($row, 'ummi_halaman', '-') }} · Tatap Muka #{{ data_get($row, 'ummi_tatap_muka', '-') }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-5 shadow-sm">
                                            <p class="text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                                <x-heroicon-o-check-badge class="w-4 h-4 text-emerald-600" /> Target Metode Ummi
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
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 font-semibold">
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

                                        <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-5 shadow-sm">
                                            <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                                <x-heroicon-o-trophy class="w-4 h-4" /> Nilai Munaqasyah
                                            </p>
                                            <p class="text-2xl font-black text-zinc-900 dark:text-white">
                                                {{ data_get($row, 'ummi_munaqasyah_score') !== null ? number_format((float) data_get($row, 'ummi_munaqasyah_score'), 1) : '-' }}
                                            </p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 font-semibold">Evaluasi kelancaran &amp; tajwid</p>
                                        </div>
                                    </div>
                                @else
                                    {{-- PROGRAM REGULER DETAILS --}}
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-5 shadow-sm">
                                            <p class="text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                                <x-heroicon-o-check-badge class="w-4 h-4" /> Target Baris Harian
                                            </p>
                                            <p class="text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($row, 'level_baris', 5) }} Baris / Hari</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 font-semibold">Tingkat: {{ ucfirst(data_get($row, 'tahfizh_level', 'reguler')) }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-5 shadow-sm">
                                            <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                                <x-heroicon-o-chart-bar class="w-4 h-4" /> Capaian Baris Bulan Ini
                                            </p>
                                            <p class="text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($row, 'capaian_baris_month', 0) }} / {{ data_get($row, 'target_baris_month', 100) }} Baris</p>
                                            <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1 font-semibold">Ketercapaian: {{ data_get($row, 'reguler_baris_percent', 0) }}%</p>
                                        </div>

                                        <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-5 shadow-sm">
                                            <p class="text-xs font-bold text-purple-700 dark:text-purple-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                                <x-heroicon-o-trophy class="w-4 h-4" /> Total Hafalan Lengkap
                                            </p>
                                            <p class="text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($row, 'completed_juz_count', 0) }} Juz</p>
                                            <p class="text-xs text-purple-600 dark:text-purple-400 mt-1 font-semibold">{{ data_get($row, 'completed_juz_list', 'Belum ada Juz lengkap') }}</p>
                                        </div>
                                    </div>
                                @endif

                                {{-- Kurikulum Progress Bar --}}
                                <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-5 shadow-sm space-y-3">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-xs font-black uppercase tracking-wider text-zinc-800 dark:text-zinc-200">🎯 Target Kurikulum Hafalan</span>
                                                @if (data_get($row, 'target_juz_label'))
                                                    <span class="px-2 py-0.5 rounded-md text-xs font-black bg-emerald-600 text-white shadow-xs">
                                                        {{ data_get($row, 'target_juz_label') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                                Ketercapaian hafalan sesuai standar kurikulum target sekolah.
                                            </p>
                                        </div>
                                        <div class="text-left sm:text-right">
                                            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400">
                                                {{ number_format((float) data_get($row, 'progress_percent', 0), 1) }}%
                                            </p>
                                            <p class="text-[11px] font-bold text-zinc-500 dark:text-zinc-400">
                                                {{ number_format(data_get($row, 'memorized_ayahs', 0)) }} / {{ number_format(data_get($row, 'target_total_ayahs', data_get($row, 'total_quran_ayahs', 6236))) }} ayat
                                            </p>
                                        </div>
                                    </div>
                                    <div class="h-3 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <div class="h-3 rounded-full bg-emerald-600 transition-all duration-300"
                                             style="width: {{ min(100, max(0, (float) data_get($row, 'progress_percent', 0))) }}%"></div>
                                    </div>
                                </div>

                                {{-- Peta Perjalanan Milestone --}}
                                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                                    <h4 class="text-xs font-black uppercase text-zinc-400 dark:text-zinc-500 tracking-wider mb-3">
                                        🗺️ Peta Perjalanan Target (4 Term / Tahun Ajaran)
                                    </h4>
                                    <x-student-hafalan-journey :milestones="data_get($row, 'term_milestones', [])" />
                                </div>

                                {{-- Riwayat Terakhir Hafalan & Murajaah --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Hafalan Terakhir --}}
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm space-y-3">
                                        <h4 class="text-xs font-black uppercase text-zinc-800 dark:text-zinc-200 tracking-wider flex items-center gap-1.5">
                                            <span>📖</span> Setoran Hafalan Terakhir
                                        </h4>
                                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                                            @forelse ($studentHafalan as $h)
                                                <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between text-xs">
                                                    <div>
                                                        <p class="font-bold text-zinc-800 dark:text-zinc-200">
                                                            {{ $h->surah?->name_latin }} (Ayat {{ $h->ayah_start }}-{{ $h->ayah_end }})
                                                        </p>
                                                        <p class="text-[11px] text-zinc-400 mt-0.5">
                                                            {{ $h->submitted_at ? $h->submitted_at->format('d M Y') : '-' }}
                                                        </p>
                                                    </div>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold {{ $h->status === 'passed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-800' }}">
                                                        {{ $h->status === 'passed' ? 'Lulus' : 'Ulang' }}
                                                    </span>
                                                </div>
                                            @empty
                                                <p class="text-xs text-zinc-400 py-3 text-center">Belum ada riwayat setoran.</p>
                                            @endforelse
                                        </div>
                                    </div>

                                    {{-- Murajaah Terakhir --}}
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm space-y-3">
                                        <h4 class="text-xs font-black uppercase text-zinc-800 dark:text-zinc-200 tracking-wider flex items-center gap-1.5">
                                            <span>🔄</span> Setoran Muraja'ah Terakhir
                                        </h4>
                                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                                            @forelse ($studentMurajaah as $m)
                                                <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between text-xs">
                                                    <div>
                                                        <p class="font-bold text-zinc-800 dark:text-zinc-200">
                                                            {{ $m->surah?->name_latin }} (Ayat {{ $m->ayah_start }}-{{ $m->ayah_end }})
                                                        </p>
                                                        <p class="text-[11px] text-zinc-400 mt-0.5">
                                                            {{ $m->reviewed_at ? $m->reviewed_at->format('d M Y') : '-' }}
                                                        </p>
                                                    </div>
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                                                        Nilai: {{ $m->overall_score ?? '-' }}
                                                    </span>
                                                </div>
                                            @empty
                                                <p class="text-xs text-zinc-400 py-3 text-center">Belum ada riwayat muraja'ah.</p>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- ═══════════════ TAB 2: CATATAN ADAB & KARAKTER ═══════════════ --}}
                            <div x-show="childTab === 'adab'" x-transition class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                                        <p class="text-xs font-bold text-teal-700 dark:text-teal-400 uppercase tracking-wider mb-1">Skor Adab Bulan Ini</p>
                                        <p class="text-3xl font-black text-zinc-900 dark:text-white">{{ data_get($adabData, 'final_score', 0) }}</p>
                                        <p class="text-xs text-zinc-500 mt-1 font-semibold">Predikat: <strong class="text-teal-600 dark:text-teal-400">{{ data_get($adabData, 'grade_label', '-') }} (Grade {{ data_get($adabData, 'grade', '-') }})</strong></p>
                                    </div>

                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                                        <p class="text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider mb-1">Tingkat Pengisian Harian (40%)</p>
                                        <p class="text-3xl font-black text-zinc-900 dark:text-white">{{ data_get($adabData, 'attendance_rate', 0) }}%</p>
                                        <p class="text-xs text-zinc-500 mt-1 font-semibold">Kedisiplinan pengisian kuisioner</p>
                                    </div>

                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                                        <p class="text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider mb-1">Nilai Pendamping Adab (60%)</p>
                                        <p class="text-3xl font-black text-zinc-900 dark:text-white">{{ data_get($adabData, 'mentor_score') !== null ? data_get($adabData, 'mentor_score') : '-' }}</p>
                                        <p class="text-xs text-zinc-500 mt-1 font-semibold">Evaluasi karakter oleh pembimbing</p>
                                    </div>
                                </div>

                                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                    <div>
                                        <h4 class="text-sm font-bold text-zinc-900 dark:text-white">Buku Mutaba'ah Adab &amp; Karakter Ananda</h4>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                            Lihat detail evaluasi sholat berjamaah, dhuha, rawatib, tilawah, dan pembiasaan adab lainnya.
                                        </p>
                                    </div>
                                    <a href="{{ route('adab.show', $student) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs transition shadow-sm shrink-0">
                                        <span>Buka Lembar Mutaba'ah Adab</span> →
                                    </a>
                                </div>
                            </div>

                            {{-- ═══════════════ TAB 3: KEDISIPLINAN & PRESTASI (TANSE) ═══════════════ --}}
                            <div x-show="childTab === 'tanse'" x-transition class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                                        <p class="text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-1">Total Poin Penghargaan (Reward)</p>
                                        <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400">+{{ data_get($tanseData, 'reward_points', 0) }}</p>
                                        <p class="text-xs text-zinc-500 mt-1 font-semibold">Akumulasi prestasi &amp; kebaikan ananda</p>
                                    </div>

                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                                        <p class="text-xs font-bold text-rose-700 dark:text-rose-400 uppercase tracking-wider mb-1">Total Poin Pelanggaran</p>
                                        <p class="text-3xl font-black text-rose-600 dark:text-rose-400">-{{ data_get($tanseData, 'violation_points', 0) }}</p>
                                        <p class="text-xs text-zinc-500 mt-1 font-semibold">Catatan kedisiplinan tata tertib</p>
                                    </div>

                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                                        <p class="text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider mb-1">Selisih Kebaikan (Net Poin)</p>
                                        <p class="text-3xl font-black {{ data_get($tanseData, 'net_points', 0) >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-amber-600' }}">
                                            {{ data_get($tanseData, 'net_points', 0) > 0 ? '+' : '' }}{{ data_get($tanseData, 'net_points', 0) }}
                                        </p>
                                        <p class="text-xs text-zinc-500 mt-1 font-semibold">Poin prestasi dikurangi pelanggaran</p>
                                    </div>
                                </div>

                                {{-- Riwayat Catatan Poin Ananda --}}
                                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm space-y-4">
                                    <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-3">
                                        <h4 class="text-xs font-black uppercase text-zinc-800 dark:text-zinc-200 tracking-wider">
                                            Catatan Kedisiplinan &amp; Penghargaan Terkini
                                        </h4>
                                        <a href="{{ route('student-points.index') }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                            Lihat Semua Riwayat →
                                        </a>
                                    </div>

                                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                                        @forelse (data_get($tanseData, 'recent_points', []) as $pt)
                                            @php $isV = \App\Models\StudentPoint::isViolationType($pt->type); @endphp
                                            <div class="py-3 first:pt-0 last:pb-0 flex items-start justify-between gap-3 text-xs">
                                                <div class="space-y-1">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="font-bold text-zinc-900 dark:text-white">{{ $pt->title }}</span>
                                                        <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase {{ $isV ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' }}">
                                                            {{ \App\Models\StudentPoint::getTypeLabel($pt->type) }}
                                                        </span>
                                                    </div>
                                                    @if($pt->description)
                                                        <p class="text-zinc-500 dark:text-zinc-400">{{ $pt->description }}</p>
                                                    @endif
                                                    <p class="text-[11px] text-zinc-400">
                                                        Tanggal: {{ $pt->date ? $pt->date->format('d M Y') : '-' }}
                                                        @if($pt->sanction)
                                                            · <strong class="text-amber-600">Sanksi: {{ $pt->sanction }}</strong>
                                                        @endif
                                                    </p>
                                                </div>
                                                <span class="text-sm font-black {{ $isV ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }} shrink-0">
                                                    {{ $isV ? '-' : '+' }}{{ $pt->points }} Poin
                                                </span>
                                            </div>
                                        @empty
                                            <p class="text-xs text-zinc-400 py-4 text-center">
                                                Alhamdulillah, belum ada catatan pelanggaran tata tertib ananda.
                                            </p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            {{-- ═══════════════ SCENE 6: FITUR PENDUKUNG (MUSHAF & RAPOR DIGITAL) ═══════════════ --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-950/30 dark:to-teal-950/20 border border-emerald-200/80 dark:border-emerald-800/50 rounded-2xl p-5 shadow-sm flex flex-col justify-between gap-4">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-2 text-emerald-800 dark:text-emerald-300 font-extrabold text-sm">
                                            <span>📖</span> Mushaf Al-Qur'an Digital
                                        </div>
                                        <p class="text-xs text-emerald-900/80 dark:text-emerald-200/80 leading-relaxed">
                                            Buka mushaf Al-Qur'an digital interaktif kapan saja untuk mendampingi dan menyimak muraja'ah Ananda di rumah dengan nyaman.
                                        </p>
                                    </div>
                                    <div>
                                        <a href="{{ route('quran.mushaf') }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl transition shadow-sm">
                                            <span>Buka Mushaf Online</span> →
                                        </a>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/20 border border-indigo-200/80 dark:border-indigo-800/50 rounded-2xl p-5 shadow-sm flex flex-col justify-between gap-4">
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-2 text-indigo-800 dark:text-indigo-300 font-extrabold text-sm">
                                            <span>📊</span> Rekapitulasi Rapor Digital
                                        </div>
                                        <p class="text-xs text-indigo-900/80 dark:text-indigo-200/80 leading-relaxed">
                                            Akses rekapitulasi capaian hafalan Al-Qur'an, riwayat ujian tahfizh, dan rapor perkembangan berkala Ananda secara lengkap.
                                        </p>
                                    </div>
                                    <div>
                                        <a href="{{ route('progress.show', $student) }}" class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition shadow-sm">
                                            <span>Buka Arsip Rapor Lengkap</span> →
                                        </a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    @endforeach

                </div>
            @endif

        </div>
    </div>
</x-app-layout>