<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-0.5">
            <h2 class="text-lg sm:text-xl font-bold leading-tight text-gray-900 dark:text-zinc-100">
                Dashboard Koordinator Keagamaan
            </h2>
            <p class="text-xs sm:text-sm text-gray-500 dark:text-zinc-400">
                Pemantauan progres pengisian kuisioner adab & akhlak murid harian.
            </p>
        </div>
    </x-slot>

    <div class="py-4 sm:py-8">
        <div class="max-w-7xl mx-auto space-y-4 sm:space-y-6 px-3 sm:px-6 lg:px-8">
            
            <!-- Hari & Tanggal Widget -->
            <div class="bg-gradient-to-r from-teal-500 to-indigo-600 rounded-xl sm:rounded-2xl p-4 sm:p-6 text-white shadow-sm relative overflow-hidden">
                <div class="absolute right-0 bottom-0 opacity-10 transform translate-x-8 translate-y-8">
                    <svg class="h-32 sm:h-48 w-32 sm:w-48" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/>
                    </svg>
                </div>
                <div class="relative z-10">
                    <h3 class="text-xs sm:text-sm font-semibold opacity-90">Hari Ini</h3>
                    <p class="text-xl sm:text-3xl font-black mt-0.5">{{ \Carbon\Carbon::parse($today)->translatedFormat('l, d F Y') }}</p>
                    <p class="text-[11px] sm:text-xs text-teal-100 mt-1.5 line-clamp-2 sm:line-clamp-none">
                        Pastikan seluruh murid mengisi evaluasi adab mereka sebelum hari berganti untuk konsistensi catatan perkembangan karakter.
                    </p>
                </div>
            </div>

            <!-- Kartu Statistik (Bento Modern Grid) -->
            <div class="grid grid-cols-3 gap-3 sm:gap-5">
                
                <!-- Total Murid -->
                <div class="rounded-2xl bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-white/[0.08] p-4 sm:p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4">
                    <div class="p-2 sm:p-3 bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 rounded-xl">
                        <svg class="h-4 w-4 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] sm:text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Total Murid</p>
                        <h4 class="text-xl sm:text-3xl font-black text-zinc-900 dark:text-white tracking-tight mt-0.5">{{ $totalStudents }}</h4>
                    </div>
                </div>

                <!-- Sudah Mengisi -->
                <div class="rounded-2xl bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-white/[0.08] p-4 sm:p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4">
                    <div class="p-2 sm:p-3 bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 rounded-xl">
                        <svg class="h-4 w-4 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] sm:text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Sudah Isi</p>
                        <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-2 mt-0.5">
                            <h4 class="text-xl sm:text-3xl font-black text-emerald-600 dark:text-emerald-400 tracking-tight">{{ $filledCount }}</h4>
                            <span class="text-[10px] sm:text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                @if($totalStudents > 0)
                                    ({{ round(($filledCount / $totalStudents) * 100) }}%)
                                @else
                                    (0%)
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Belum Mengisi -->
                <div class="rounded-2xl bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/80 dark:border-white/[0.08] p-4 sm:p-5 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all duration-300 flex flex-col sm:flex-row items-start sm:items-center gap-3 sm:gap-4">
                    <div class="p-2 sm:p-3 bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 rounded-xl">
                        <svg class="h-4 w-4 sm:h-6 sm:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] sm:text-xs font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Belum Isi</p>
                        <div class="flex flex-col sm:flex-row sm:items-baseline gap-0.5 sm:gap-2 mt-0.5">
                            <h4 class="text-xl sm:text-3xl font-black text-rose-600 dark:text-rose-400 tracking-tight">{{ $notFilledCount }}</h4>
                            <span class="text-[10px] sm:text-xs font-bold text-rose-600 dark:text-rose-400">
                                @if($totalStudents > 0)
                                    ({{ round(($notFilledCount / $totalStudents) * 100) }}%)
                                @else
                                    (0%)
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 shadow-sm rounded-xl sm:rounded-2xl p-3.5 sm:p-6">
                <h3 class="text-xs sm:text-sm font-bold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider">Progres Pengisian Seluruh Murid</h3>
                @php
                    $percent = $totalStudents > 0 ? ($filledCount / $totalStudents) * 100 : 0;
                @endphp
                <div class="mt-2.5 sm:mt-4 w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-3 sm:h-4 overflow-hidden border border-zinc-200 dark:border-zinc-700">
                    <div class="bg-gradient-to-r from-teal-400 to-indigo-600 h-full rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                </div>
                <div class="flex justify-between items-center text-[11px] sm:text-xs text-zinc-400 mt-2">
                    <span>{{ $filledCount }} Selesai</span>
                    <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ round($percent, 1) }}% Terisi</span>
                </div>
            </div>

            <!-- Daftar Progres Pengisian Hari Ini -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-100 dark:border-zinc-800 shadow-sm rounded-xl sm:rounded-2xl overflow-hidden">
                <div class="px-3.5 sm:px-6 py-3 sm:py-5 border-b border-zinc-100 dark:border-zinc-800 flex justify-between items-center bg-zinc-50/50 dark:bg-zinc-900/50">
                    <h3 class="text-xs sm:text-base font-bold text-zinc-900 dark:text-white">Status Pengisian Hari Ini</h3>
                    <span class="text-xs text-zinc-400">Total: {{ $totalStudents }} Murid</span>
                </div>

                {{-- Mobile Card List --}}
                <div class="block sm:hidden divide-y divide-zinc-100 dark:divide-zinc-800/60 p-2 space-y-2">
                    @forelse ($students as $student)
                        @php
                            $hasRecord = $student->adabRecords->isNotEmpty();
                            $score = $hasRecord ? $student->adabRecords->first()->total_score : null;
                        @endphp
                        <div class="p-2.5 rounded-lg bg-zinc-50 dark:bg-zinc-800/40 space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h4 class="font-bold text-xs text-zinc-900 dark:text-white">{{ $student->name }}</h4>
                                    <p class="text-[10px] text-zinc-400">Kelas: {{ $student->classRoom?->name ?: '-' }} · NIS: {{ $student->student_number ?: '-' }}</p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    @if ($hasRecord)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30">
                                            ✓ Sudah
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-100 dark:border-rose-900/30">
                                            ✕ Belum
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center justify-between pt-1 border-t border-zinc-100 dark:border-zinc-800/60 text-xs">
                                <div>
                                    @if ($hasRecord)
                                        <span class="text-[11px] font-bold {{ $score >= 85 ? 'text-emerald-600 dark:text-emerald-400' : ($score >= 70 ? 'text-indigo-600 dark:text-indigo-400' : 'text-zinc-700 dark:text-zinc-200') }}">
                                            Skor: {{ $score }} <span class="text-[10px] text-zinc-400 font-normal">/ 100</span>
                                        </span>
                                    @else
                                        <span class="text-[10px] text-zinc-400 italic">Belum ada skor</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <a href="{{ route('adab.show', $student) }}" class="px-2 py-1 text-[11px] font-semibold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-2xs">
                                        Rincian
                                    </a>
                                    @if (!$hasRecord)
                                        <a href="{{ route('adab.create', $student) }}" class="px-2 py-1 text-[11px] font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow-2xs">
                                            Bantu Isi
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-zinc-400">Tidak ada data murid ditemukan.</div>
                    @endforelse
                </div>

                {{-- Desktop Table View --}}
                <div class="hidden sm:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-250 dark:divide-zinc-800">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/50 text-left text-xs font-semibold text-zinc-550 dark:text-zinc-400 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-4">Nama Murid</th>
                                <th class="px-6 py-4">Kelas</th>
                                <th class="px-6 py-4 text-center">Status Pengisian</th>
                                <th class="px-6 py-4 text-center">Skor Adab Hari Ini</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($students as $student)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-white/[0.01] transition duration-150">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-zinc-900 dark:text-white">{{ $student->name }}</div>
                                        <div class="text-xs text-zinc-400 dark:text-zinc-550 mt-0.5">NIS: {{ $student->student_number ?: '-' }}</div>
                                    </td>
                                    
                                    <td class="px-6 py-4 text-zinc-700 dark:text-zinc-300">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200">
                                            {{ $student->classRoom?->name ?: '-' }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        @if ($student->adabRecords->isNotEmpty())
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Sudah Mengisi
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-455 border border-rose-100 dark:border-rose-900/30">
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                Belum Mengisi
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        @if ($student->adabRecords->isNotEmpty())
                                            @php
                                                $score = $student->adabRecords->first()->total_score;
                                                $badgeColor = 'text-zinc-800 dark:text-zinc-200';
                                                if ($score >= 85) {
                                                    $badgeColor = 'text-emerald-600 dark:text-emerald-450';
                                                } elseif ($score >= 70) {
                                                    $badgeColor = 'text-indigo-600 dark:text-indigo-400';
                                                }
                                            @endphp
                                            <span class="font-extrabold text-sm {{ $badgeColor }}">
                                                {{ $score }} <span class="text-xs text-zinc-400 font-normal">/ 100</span>
                                            </span>
                                        @else
                                            <span class="text-xs text-zinc-400 dark:text-zinc-600 italic">-</span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-right space-x-1">
                                        <a href="{{ route('adab.show', $student) }}" class="btn-action-detail">
                                            Rincian & Riwayat
                                        </a>
                                        @if ($student->adabRecords->isEmpty())
                                            <a href="{{ route('adab.create', $student) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 rounded-lg shadow-sm hover:scale-[1.02] transition duration-150">
                                                Bantu Isi
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-zinc-400 dark:text-zinc-500">
                                        Tidak ada data murid ditemukan.
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
