<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-0.5">
            <h2 class="font-bold text-lg sm:text-xl text-zinc-900 dark:text-white leading-tight">
                Dashboard Orang Tua
            </h2>
            <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400">
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

    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-5 sm:space-y-6">

            @if (! $parent)
                <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-5 text-xs sm:text-sm text-amber-800 dark:text-amber-300 font-semibold">
                    Profil orang tua belum terhubung dengan akun ini. Silakan hubungi admin sekolah.
                </div>
            @elseif ($childrenProgress->isEmpty())
                <div class="glass-liquid-card rounded-[1.75rem] p-6 sm:p-8 text-center">
                    <p class="text-zinc-500 dark:text-zinc-400 font-medium text-xs sm:text-sm">
                        Belum ada data murid yang ditautkan ke akun Anda.
                    </p>
                </div>
            @else
                {{-- 🌟 APPRECIATION & HIGHLIGHTS BANNER 🌟 --}}
                <div class="glass-liquid-card rounded-[1.75rem] p-5 sm:p-7 shadow-sm relative overflow-hidden">
                    <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-5">
                        <div class="space-y-1 sm:space-y-1.5">
                            <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-teal-500/15 border border-teal-500/20 text-teal-700 dark:text-teal-300 text-[10px] sm:text-xs font-bold uppercase tracking-wider">
                                <x-heroicon-o-sparkles class="w-3.5 h-3.5 text-teal-600 dark:text-teal-400" /> Portal Wali Murid
                            </span>
                            <h3 class="text-base sm:text-xl font-black text-zinc-900 dark:text-white">
                                Assalamu'alaikum, Ayah / Bunda {{ $parent->user?->name ?? '' }}!
                            </h3>
                            <p class="text-xs text-zinc-600 dark:text-zinc-400 max-w-2xl leading-relaxed font-medium">
                                Ruang sinergi pemantauan jejak langkah ananda dalam hafalan Al-Qur'an, adab islami, dan prestasi.
                            </p>
                        </div>

                        <div class="flex items-center gap-2 sm:gap-3 shrink-0 self-start sm:self-auto">
                            <div class="rounded-2xl glass-liquid-inner px-4 py-2.5 sm:px-5 sm:py-3 text-center">
                                <p class="text-[9px] sm:text-[10px] uppercase font-bold text-teal-700 dark:text-teal-300 tracking-wider">Total Ananda</p>
                                <p class="text-lg sm:text-2xl font-black text-zinc-900 dark:text-white mt-0.5">{{ $childrenProgress->count() }} Murid</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═══════════════ MULTI-CHILD MONITORING HUB ═══════════════ --}}
                <div x-data="{ activeChild: 0 }" class="space-y-4 sm:space-y-6">

                    @if ($childrenProgress->count() > 1)
                        {{-- Tab Selector for multiple children (Touch-scrollable) --}}
                        <div class="glass-liquid-card p-2 sm:p-2.5 rounded-2xl flex items-center gap-1.5 sm:gap-2 overflow-x-auto scrollbar-none">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 px-2 sm:px-3 flex items-center gap-1 shrink-0">
                                <x-heroicon-o-user-group class="w-3.5 h-3.5" /> Ananda:
                            </span>
                            @foreach ($childrenProgress as $idx => $row)
                                <button @click="activeChild = {{ $idx }}"
                                        :class="activeChild === {{ $idx }} ? 'bg-teal-600 text-white shadow-md font-black' : 'glass-liquid-inner text-zinc-700 dark:text-zinc-300 hover:bg-white/60 dark:hover:bg-white/10 font-semibold'"
                                        class="flex items-center gap-1.5 rounded-xl px-3.5 py-2 sm:px-4 sm:py-2.5 text-xs sm:text-sm transition-all shrink-0 cursor-pointer">
                                    <x-heroicon-o-user class="w-3.5 h-3.5" />
                                    <span>{{ data_get($row, 'student_name', 'Ananda '.($idx+1)) }}</span>
                                    <span class="text-[10px] sm:text-[11px] opacity-80">({{ data_get($row, 'class_room_name', '-') }})</span>
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

                        <div x-show="activeChild === {{ $idx }}" x-transition class="space-y-4 sm:space-y-6" x-data="{ childTab: 'tahfizh' }">
                            
                            {{-- Header Ananda --}}
                            <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-2xl p-4 sm:p-6 shadow-sm space-y-3 sm:space-y-4">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-zinc-100 dark:border-zinc-800 pb-3 sm:pb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-black text-lg sm:text-xl shrink-0">
                                            {{ substr(data_get($row, 'student_name', 'A'), 0, 1) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                                                <h3 class="text-base sm:text-xl font-black text-zinc-900 dark:text-white truncate">
                                                    {{ data_get($row, 'student_name', $student?->name ?? '-') }}
                                                </h3>
                                                <span class="px-2 py-0.5 rounded-lg text-[10px] sm:text-xs font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300">
                                                    Kelas {{ data_get($row, 'class_room_name', '-') }}
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5 font-medium truncate">
                                                NIS: <strong class="text-zinc-700 dark:text-zinc-300">{{ data_get($row, 'student_number', '-') }}</strong> · 
                                                <span>{{ $isUmmi ? 'Metode Ummi (Kls 10)' : 'Reguler Tahfizh' }}</span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[10px] sm:text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                            <span>{{ $statusIcon }}</span> {{ $statusLabel }}
                                        </span>
                                        <a href="{{ route('quran.mushaf') }}" class="inline-flex items-center gap-1 px-3 py-1 rounded-xl bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-800 dark:text-zinc-200 font-bold text-[11px] sm:text-xs transition border border-zinc-200 dark:border-zinc-700">
                                            <span>📖 Mushaf</span>
                                        </a>
                                        <a href="{{ route('progress.show', $student) }}" class="inline-flex items-center gap-1 px-3 py-1 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] sm:text-xs transition shadow-sm">
                                            <span>📊 Rapor</span> →
                                        </a>
                                    </div>
                                </div>

                                {{-- Quick 3-Category Status Row (Responsive 1-col on mobile, 3-col on desktop) --}}
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-3">
                                    {{-- Tahfizh Pill --}}
                                    <div class="rounded-xl bg-indigo-50/60 dark:bg-indigo-950/30 p-3 sm:p-3.5 border border-indigo-100 dark:border-indigo-900/40 flex items-center justify-between">
                                        <div>
                                            <p class="text-[9px] sm:text-[10px] font-black uppercase text-indigo-700 dark:text-indigo-400">📖 Tahfizh Al-Qur'an</p>
                                            <p class="text-sm sm:text-base font-black text-zinc-900 dark:text-white mt-0.5">
                                                {{ $isUmmi ? data_get($row, 'ummi_jilid_str', 'Jilid 1') : data_get($row, 'completed_juz_count', 0).' Juz Lengkap' }}
                                            </p>
                                        </div>
                                        <span class="text-[10px] sm:text-xs font-bold text-indigo-600 dark:text-indigo-400 bg-white dark:bg-zinc-800 px-2 py-0.5 rounded-lg shadow-xs">
                                            {{ number_format((float) data_get($row, 'progress_percent', 0), 1) }}% Target
                                        </span>
                                    </div>

                                    {{-- Adab Pill --}}
                                    <div class="rounded-xl bg-teal-50/60 dark:bg-teal-950/30 p-3 sm:p-3.5 border border-teal-100 dark:border-teal-900/40 flex items-center justify-between">
                                        <div>
                                            <p class="text-[9px] sm:text-[10px] font-black uppercase text-teal-700 dark:text-teal-400">🕌 Karakter &amp; Adab</p>
                                            <p class="text-sm sm:text-base font-black text-zinc-900 dark:text-white mt-0.5">
                                                Nilai: {{ data_get($adabData, 'final_score', '-') }} (Grade {{ data_get($adabData, 'grade', '-') }})
                                            </p>
                                        </div>
                                        <span class="text-[10px] sm:text-xs font-bold {{ data_get($adabData, 'today_record') ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950' : 'text-amber-600 bg-amber-50 dark:bg-amber-950' }} px-2 py-0.5 rounded-lg border border-current/20">
                                            {{ data_get($adabData, 'today_record') ? '✓ Terisi' : 'Belum Terisi' }}
                                        </span>
                                    </div>

                                    {{-- Tanse / Poin Pill --}}
                                    <div class="rounded-xl bg-purple-50/60 dark:bg-purple-950/30 p-3 sm:p-3.5 border border-purple-100 dark:border-purple-900/40 flex items-center justify-between">
                                        <div>
                                            <p class="text-[9px] sm:text-[10px] font-black uppercase text-purple-700 dark:text-purple-400">⭐ Kedisiplinan &amp; Prestasi</p>
                                            <p class="text-sm sm:text-base font-black text-zinc-900 dark:text-white mt-0.5">
                                                +{{ data_get($tanseData, 'reward_points', 0) }} Poin Reward
                                            </p>
                                        </div>
                                        <span class="text-[10px] sm:text-xs font-bold {{ data_get($tanseData, 'violation_points', 0) > 0 ? 'text-rose-600 bg-rose-50 dark:bg-rose-950' : 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950' }} px-2 py-0.5 rounded-lg border border-current/20">
                                            {{ data_get($tanseData, 'violation_points', 0) }} Pelanggaran
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Navigation Sub-Tabs per Child (Horizontal swipeable on mobile) --}}
                            <div class="flex items-center gap-1.5 sm:gap-2 border-b border-zinc-200 dark:border-zinc-800 pb-1 overflow-x-auto scrollbar-none">
                                <button @click="childTab = 'tahfizh'"
                                        :class="childTab === 'tahfizh' ? 'bg-emerald-600 text-white font-black shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 font-bold'"
                                        class="px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-xl text-xs sm:text-sm transition flex items-center gap-1.5 shrink-0 cursor-pointer">
                                    <span>📖</span> Tahfizh &amp; Target
                                </button>
                                <button @click="childTab = 'adab'"
                                        :class="childTab === 'adab' ? 'bg-emerald-600 text-white font-black shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 font-bold'"
                                        class="px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-xl text-xs sm:text-sm transition flex items-center gap-1.5 shrink-0 cursor-pointer">
                                    <span>🕌</span> Catatan Adab
                                </button>
                                <button @click="childTab = 'tanse'"
                                        :class="childTab === 'tanse' ? 'bg-emerald-600 text-white font-black shadow-sm' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800 font-bold'"
                                        class="px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-xl text-xs sm:text-sm transition flex items-center gap-1.5 shrink-0 cursor-pointer">
                                    <span>⭐</span> Kedisiplinan &amp; Prestasi
                                </button>
                            </div>

                            {{-- ═══════════════ TAB 1: TAHFIZH & TARGET ═══════════════ --}}
                            <div x-show="childTab === 'tahfizh'" x-transition class="space-y-4 sm:space-y-6">
                                
                                @if ($isUmmi)
                                    {{-- PROGRAM UMMI DETAILS --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-4">
                                        <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-3.5 sm:p-5 shadow-sm">
                                            <p class="text-[10px] sm:text-xs font-bold text-teal-700 dark:text-teal-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                                <x-heroicon-o-book-open class="w-3.5 h-3.5" /> Jilid &amp; Halaman
                                            </p>
                                            <p class="text-lg sm:text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($row, 'ummi_jilid_str', 'Jilid 1') }}</p>
                                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5 font-semibold">Halaman {{ data_get($row, 'ummi_halaman', '-') }} · Tatap Muka #{{ data_get($row, 'ummi_tatap_muka', '-') }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-3.5 sm:p-5 shadow-sm">
                                            <p class="text-[10px] sm:text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                                <x-heroicon-o-check-badge class="w-3.5 h-3.5 text-emerald-600" /> Target Metode Ummi
                                            </p>
                                            <p class="text-sm sm:text-lg font-black text-zinc-900 dark:text-white truncate">
                                                @if(data_get($row, 'ummi_target.ummi_jilid'))
                                                    {{ data_get($row, 'ummi_target.ummi_jilid') }}
                                                @elseif(data_get($row, 'ummi_target.surah.name_latin'))
                                                    {{ data_get($row, 'ummi_target.surah.name_latin') }}
                                                @else
                                                    Target Sesuai Jilid
                                                @endif
                                            </p>
                                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5 font-semibold truncate">
                                                @if(data_get($row, 'ummi_target'))
                                                    @if(data_get($row, 'ummi_target.halaman_peraga') || data_get($row, 'ummi_target.halaman_buku'))
                                                        Peraga: {{ data_get($row, 'ummi_target.halaman_peraga', '-') }} · Buku: {{ data_get($row, 'ummi_target.halaman_buku', '-') }}
                                                    @elseif(data_get($row, 'ummi_target.ayah_start'))
                                                        Ayat {{ data_get($row, 'ummi_target.ayah_start') }} - {{ data_get($row, 'ummi_target.ayah_end') }}
                                                    @else
                                                        Tahsin &amp; Hafalan
                                                    @endif
                                                @else
                                                    Tahsin &amp; Hafalan Ummi
                                                @endif
                                            </p>
                                        </div>

                                        <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-3.5 sm:p-5 shadow-sm">
                                            <p class="text-[10px] sm:text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                                <x-heroicon-o-trophy class="w-3.5 h-3.5" /> Nilai Munaqasyah
                                            </p>
                                            <p class="text-lg sm:text-2xl font-black text-zinc-900 dark:text-white">
                                                {{ data_get($row, 'ummi_munaqasyah_score') !== null ? number_format((float) data_get($row, 'ummi_munaqasyah_score'), 1) : '-' }}
                                            </p>
                                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5 font-semibold">Evaluasi kelancaran &amp; tajwid</p>
                                        </div>
                                    </div>
                                @else
                                    {{-- PROGRAM REGULER DETAILS --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-4">
                                        <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-3.5 sm:p-5 shadow-sm">
                                            <p class="text-[10px] sm:text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                                <x-heroicon-o-check-badge class="w-3.5 h-3.5" /> Target Baris Harian
                                            </p>
                                            <p class="text-lg sm:text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($row, 'level_baris', 5) }} Baris / Hari</p>
                                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5 font-semibold">Tingkat: {{ ucfirst(data_get($row, 'tahfizh_level', 'reguler')) }}</p>
                                        </div>

                                        <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-3.5 sm:p-5 shadow-sm">
                                            <p class="text-[10px] sm:text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                                <x-heroicon-o-chart-bar class="w-3.5 h-3.5" /> Capaian Bulan Ini
                                            </p>
                                            <p class="text-lg sm:text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($row, 'capaian_baris_month', 0) }} / {{ data_get($row, 'target_baris_month', 100) }} Baris</p>
                                            <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5 font-semibold">Ketercapaian: {{ data_get($row, 'reguler_baris_percent', 0) }}%</p>
                                        </div>

                                        <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-3.5 sm:p-5 shadow-sm">
                                            <p class="text-[10px] sm:text-xs font-bold text-purple-700 dark:text-purple-400 uppercase tracking-wider mb-1 flex items-center gap-1">
                                                <x-heroicon-o-trophy class="w-3.5 h-3.5" /> Total Hafalan Lengkap
                                            </p>
                                            <p class="text-lg sm:text-2xl font-black text-zinc-900 dark:text-white">{{ data_get($row, 'completed_juz_count', 0) }} Juz</p>
                                            <p class="text-[11px] text-purple-600 dark:text-purple-400 mt-0.5 font-semibold truncate">{{ data_get($row, 'completed_juz_list', 'Belum ada Juz lengkap') }}</p>
                                        </div>
                                    </div>
                                @endif

                                {{-- Kurikulum Progress Bar --}}
                                <div class="rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 p-4 sm:p-5 shadow-sm space-y-2.5 sm:space-y-3">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <div>
                                            <div class="flex items-center gap-1.5 sm:gap-2">
                                                <span class="text-[11px] sm:text-xs font-black uppercase tracking-wider text-zinc-800 dark:text-zinc-200">🎯 Target Kurikulum Hafalan</span>
                                                @if (data_get($row, 'target_juz_label'))
                                                    <span class="px-2 py-0.5 rounded-md text-[10px] sm:text-xs font-black bg-emerald-600 text-white shadow-xs">
                                                        {{ data_get($row, 'target_juz_label') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-[10px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                                Ketercapaian hafalan sesuai standar kurikulum target sekolah.
                                            </p>
                                        </div>
                                        <div class="text-left sm:text-right">
                                            <p class="text-xl sm:text-2xl font-black text-emerald-600 dark:text-emerald-400">
                                                {{ number_format((float) data_get($row, 'progress_percent', 0), 1) }}%
                                            </p>
                                            <p class="text-[10px] sm:text-[11px] font-bold text-zinc-500 dark:text-zinc-400">
                                                {{ number_format(data_get($row, 'memorized_ayahs', 0)) }} / {{ number_format(data_get($row, 'target_total_ayahs', data_get($row, 'total_quran_ayahs', 6236))) }} ayat
                                            </p>
                                        </div>
                                    </div>
                                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-300"
                                             style="width: {{ min(100, max(0, (float) data_get($row, 'progress_percent', 0))) }}%"></div>
                                    </div>
                                </div>

                                {{-- Peta Perjalanan Milestone --}}
                                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-2xl p-4 sm:p-5 shadow-sm">
                                    <h4 class="text-[11px] sm:text-xs font-black uppercase text-zinc-400 dark:text-zinc-500 tracking-wider mb-2 sm:mb-3">
                                        🗺️ Peta Perjalanan Target (4 Term / Tahun Ajaran)
                                    </h4>
                                    <x-student-hafalan-journey :milestones="data_get($row, 'term_milestones', [])" />
                                </div>

                                {{-- Riwayat Terakhir Hafalan & Murajaah --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                                    {{-- Hafalan Terakhir --}}
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-2xl p-4 sm:p-5 shadow-sm space-y-2.5 sm:space-y-3">
                                        <h4 class="text-xs font-black uppercase text-zinc-800 dark:text-zinc-200 tracking-wider flex items-center gap-1.5">
                                            <span>📖</span> Setoran Hafalan Terakhir
                                        </h4>
                                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                            @forelse ($studentHafalan as $h)
                                                <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between text-xs">
                                                    <div class="min-w-0">
                                                        <p class="font-bold text-zinc-900 dark:text-white truncate">
                                                            {{ $h->surah?->name_latin }} (Ayat {{ $h->ayah_start }}-{{ $h->ayah_end }})
                                                        </p>
                                                        <p class="text-[10px] text-zinc-400 mt-0.5">
                                                            {{ $h->submitted_at ? $h->submitted_at->format('d M Y') : '-' }}
                                                        </p>
                                                    </div>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $h->status === 'passed' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 text-rose-700 dark:bg-rose-950 dark:text-rose-300 border border-rose-200 dark:border-rose-800' }} shrink-0">
                                                        {{ $h->status === 'passed' ? 'Lulus' : 'Ulang' }}
                                                    </span>
                                                </div>
                                            @empty
                                                <p class="text-xs text-zinc-400 py-3 text-center">Belum ada riwayat setoran.</p>
                                            @endforelse
                                        </div>
                                    </div>

                                    {{-- Murajaah Terakhir --}}
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-2xl p-4 sm:p-5 shadow-sm space-y-2.5 sm:space-y-3">
                                        <h4 class="text-xs font-black uppercase text-zinc-800 dark:text-zinc-200 tracking-wider flex items-center gap-1.5">
                                            <span>🔄</span> Setoran Muraja'ah Terakhir
                                        </h4>
                                        <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                            @forelse ($studentMurajaah as $m)
                                                <div class="py-2.5 first:pt-0 last:pb-0 flex items-center justify-between text-xs">
                                                    <div class="min-w-0">
                                                        <p class="font-bold text-zinc-900 dark:text-white truncate">
                                                            {{ $m->surah?->name_latin }} (Ayat {{ $m->ayah_start }}-{{ $m->ayah_end }})
                                                        </p>
                                                        <p class="text-[10px] text-zinc-400 mt-0.5">
                                                            {{ $m->reviewed_at ? $m->reviewed_at->format('d M Y') : '-' }}
                                                        </p>
                                                    </div>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 shrink-0">
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
                            <div x-show="childTab === 'adab'" x-transition class="space-y-4 sm:space-y-6">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-4">
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-2xl p-3.5 sm:p-5 shadow-sm">
                                        <p class="text-[10px] sm:text-xs font-bold text-teal-700 dark:text-teal-400 uppercase tracking-wider mb-1">Skor Adab Bulan Ini</p>
                                        <p class="text-xl sm:text-3xl font-black text-zinc-900 dark:text-white">{{ data_get($adabData, 'final_score', 0) }}</p>
                                        <p class="text-[11px] text-zinc-500 mt-0.5 font-semibold">Predikat: <strong class="text-teal-600 dark:text-teal-400">{{ data_get($adabData, 'grade_label', '-') }} (Grade {{ data_get($adabData, 'grade', '-') }})</strong></p>
                                    </div>

                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-2xl p-3.5 sm:p-5 shadow-sm">
                                        <p class="text-[10px] sm:text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider mb-1">Tingkat Pengisian Harian</p>
                                        <p class="text-xl sm:text-3xl font-black text-zinc-900 dark:text-white">{{ data_get($adabData, 'attendance_rate', 0) }}%</p>
                                        <p class="text-[11px] text-zinc-500 mt-0.5 font-semibold">Kedisiplinan kuisioner</p>
                                    </div>

                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-2xl p-3.5 sm:p-5 shadow-sm">
                                        <p class="text-[10px] sm:text-xs font-bold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider mb-1">Nilai Pembimbing</p>
                                        <p class="text-xl sm:text-3xl font-black text-zinc-900 dark:text-white">{{ data_get($adabData, 'mentor_score') !== null ? data_get($adabData, 'mentor_score') : '-' }}</p>
                                        <p class="text-[11px] text-zinc-500 mt-0.5 font-semibold">Evaluasi karakter</p>
                                    </div>
                                </div>

                                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-2xl p-4 sm:p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                                    <div>
                                        <h4 class="text-xs sm:text-sm font-bold text-zinc-900 dark:text-white">Buku Mutaba'ah Adab &amp; Karakter Ananda</h4>
                                        <p class="text-[11px] sm:text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                            Detail sholat berjamaah, dhuha, rawatib, tilawah, dan pembiasaan adab.
                                        </p>
                                    </div>
                                    <a href="{{ route('adab.show', $student) }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs transition shadow-sm shrink-0">
                                        <span>Buka Lembar Adab</span> →
                                    </a>
                                </div>
                            </div>

                            {{-- ═══════════════ TAB 3: KEDISIPLINAN & PRESTASI (TANSE) ═══════════════ --}}
                            <div x-show="childTab === 'tanse'" x-transition class="space-y-4 sm:space-y-6">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 sm:gap-4">
                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-2xl p-3.5 sm:p-5 shadow-sm">
                                        <p class="text-[10px] sm:text-xs font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-1">Poin Penghargaan (Reward)</p>
                                        <p class="text-xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400">+{{ data_get($tanseData, 'reward_points', 0) }}</p>
                                        <p class="text-[11px] text-zinc-500 mt-0.5 font-semibold">Prestasi &amp; kebaikan ananda</p>
                                    </div>

                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-2xl p-3.5 sm:p-5 shadow-sm">
                                        <p class="text-[10px] sm:text-xs font-bold text-rose-700 dark:text-rose-400 uppercase tracking-wider mb-1">Poin Pelanggaran</p>
                                        <p class="text-xl sm:text-3xl font-black text-rose-600 dark:text-rose-400">-{{ data_get($tanseData, 'violation_points', 0) }}</p>
                                        <p class="text-[11px] text-zinc-500 mt-0.5 font-semibold">Kedisiplinan tata tertib</p>
                                    </div>

                                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-2xl p-3.5 sm:p-5 shadow-sm">
                                        <p class="text-[10px] sm:text-xs font-bold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider mb-1">Net Poin</p>
                                        <p class="text-xl sm:text-3xl font-black {{ data_get($tanseData, 'net_points', 0) >= 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-amber-600' }}">
                                            {{ data_get($tanseData, 'net_points', 0) > 0 ? '+' : '' }}{{ data_get($tanseData, 'net_points', 0) }}
                                        </p>
                                        <p class="text-[11px] text-zinc-500 mt-0.5 font-semibold">Prestasi dikurangi pelanggaran</p>
                                    </div>
                                </div>

                                {{-- Riwayat Catatan Poin Ananda --}}
                                <div class="bg-white dark:bg-zinc-900 border border-zinc-200/70 dark:border-zinc-800/80 rounded-2xl p-4 sm:p-5 shadow-sm space-y-3">
                                    <div class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-800 pb-2.5">
                                        <h4 class="text-xs font-black uppercase text-zinc-800 dark:text-zinc-200 tracking-wider">
                                            Catatan Kedisiplinan &amp; Prestasi Terkini
                                        </h4>
                                        <a href="{{ route('student-points.index') }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline">
                                            Lihat Semua →
                                        </a>
                                    </div>

                                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                        @forelse (data_get($tanseData, 'recent_points', []) as $pt)
                                            @php $isV = \App\Models\StudentPoint::isViolationType($pt->type); @endphp
                                            <div class="py-2.5 first:pt-0 last:pb-0 flex items-start justify-between gap-2 text-xs">
                                                <div class="space-y-0.5 min-w-0">
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <span class="font-bold text-zinc-900 dark:text-white truncate">{{ $pt->title }}</span>
                                                        <span class="px-2 py-0.5 rounded text-[9px] font-black uppercase {{ $isV ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' }}">
                                                            {{ \App\Models\StudentPoint::getTypeLabel($pt->type) }}
                                                        </span>
                                                    </div>
                                                    @if($pt->description)
                                                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ $pt->description }}</p>
                                                    @endif
                                                    <p class="text-[10px] text-zinc-400">
                                                        {{ $pt->date ? $pt->date->format('d M Y') : '-' }}
                                                        @if($pt->sanction)
                                                            · <strong class="text-amber-600">Sanksi: {{ $pt->sanction }}</strong>
                                                        @endif
                                                    </p>
                                                </div>
                                                <span class="text-xs sm:text-sm font-black {{ $isV ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }} shrink-0">
                                                    {{ $isV ? '-' : '+' }}{{ $pt->points }} Poin
                                                </span>
                                            </div>
                                        @empty
                                            <p class="text-xs text-zinc-400 py-3 text-center">
                                                Alhamdulillah, belum ada catatan pelanggaran tata tertib ananda.
                                            </p>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            {{-- ═══════════════ FITUR PENDUKUNG (MUSHAF & RAPOR) ═══════════════ --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 pt-1">
                                <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-950/30 dark:to-teal-950/20 border border-emerald-200/80 dark:border-emerald-800/50 rounded-2xl p-4 sm:p-5 shadow-sm flex flex-col justify-between gap-3">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 text-emerald-800 dark:text-emerald-300 font-extrabold text-xs sm:text-sm">
                                            <span>📖</span> Mushaf Al-Qur'an Digital
                                        </div>
                                        <p class="text-[11px] sm:text-xs text-emerald-900/80 dark:text-emerald-200/80 leading-relaxed">
                                            Dampingi dan simak hafalan ananda dari rumah dengan mushaf digital interaktif.
                                        </p>
                                    </div>
                                    <div>
                                        <a href="{{ route('quran.mushaf') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl transition shadow-sm">
                                            <span>Buka Mushaf</span> →
                                        </a>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-br from-indigo-50 to-purple-50 dark:from-indigo-950/30 dark:to-purple-950/20 border border-indigo-200/80 dark:border-indigo-800/50 rounded-2xl p-4 sm:p-5 shadow-sm flex flex-col justify-between gap-3">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 text-indigo-800 dark:text-indigo-300 font-extrabold text-xs sm:text-sm">
                                            <span>📊</span> Rekapitulasi Rapor Digital
                                        </div>
                                        <p class="text-[11px] sm:text-xs text-indigo-900/80 dark:text-indigo-200/80 leading-relaxed">
                                            Akses riwayat capaian hafalan, ujian tahfizh, dan rapor perkembangan berkala.
                                        </p>
                                    </div>
                                    <div>
                                        <a href="{{ route('progress.show', $student) }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl transition shadow-sm">
                                            <span>Buka Arsip Rapor</span> →
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