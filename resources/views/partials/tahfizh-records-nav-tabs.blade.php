@props(['activeTab' => 'hafalan'])

<div class="mb-5 sm:mb-6">
    <div class="glass-liquid-card rounded-2xl sm:rounded-3xl p-3 sm:p-4 shadow-sm relative overflow-hidden border border-emerald-500/20">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 sm:gap-4">
            {{-- Header Title --}}
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-gradient-to-tr from-emerald-600 to-teal-500 text-white flex items-center justify-center shrink-0 shadow-sm shadow-emerald-500/25">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-black text-zinc-900 dark:text-white tracking-tight flex items-center gap-2">
                        <span>Riwayat & Data Setoran</span>
                    </h2>
                    <p class="text-[11px] sm:text-xs text-zinc-500 dark:text-zinc-400">
                        Pantau seluruh log setoran hafalan baru dan muraja'ah santri
                    </p>
                </div>
            </div>

            {{-- Tab Switcher & Quick Action --}}
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1 sm:gap-1.5 p-1 rounded-2xl glass-liquid-inner border border-zinc-200/60 dark:border-white/10 shrink-0">
                    {{-- Tab 1: Riwayat Hafalan --}}
                    <a href="{{ route('hafalan-records.index') }}"
                       class="px-3.5 py-2 rounded-xl text-xs flex items-center gap-2 font-bold transition-all duration-200 shrink-0 select-none {{ $activeTab === 'hafalan' 
                           ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md shadow-emerald-500/25 ring-2 ring-emerald-500/30' 
                           : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-white/10' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        <span>Riwayat Hafalan</span>
                    </a>

                    {{-- Tab 2: Riwayat Muraja'ah --}}
                    <a href="{{ route('murajaah-records.index') }}"
                       class="px-3.5 py-2 rounded-xl text-xs flex items-center gap-2 font-bold transition-all duration-200 shrink-0 select-none {{ $activeTab === 'murajaah' 
                           ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md shadow-emerald-500/25 ring-2 ring-emerald-500/30' 
                           : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-white/10' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H17" />
                        </svg>
                        <span>Riwayat Muraja'ah</span>
                    </a>
                </div>

                {{-- Fast Input Button --}}
                <a href="{{ route('spreadsheet-input.index') }}"
                   class="inline-flex items-center justify-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white shadow-md shadow-indigo-500/20 active:scale-95 transition-all">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>+ Input Setoran & Muraja'ah</span>
                </a>
            </div>
        </div>
    </div>
</div>
