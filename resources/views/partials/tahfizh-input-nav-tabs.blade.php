@props(['activeTab' => 'spreadsheet'])

<div class="mb-5 sm:mb-6">
    <div class="glass-liquid-card rounded-2xl sm:rounded-3xl p-3 sm:p-4 shadow-sm relative overflow-hidden border border-teal-500/20">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 sm:gap-4">
            {{-- Header Title --}}
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-gradient-to-tr from-teal-600 to-emerald-500 text-white flex items-center justify-center shrink-0 shadow-sm shadow-teal-500/25">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm sm:text-base font-black text-zinc-900 dark:text-white tracking-tight flex items-center gap-2">
                        <span>Pusat Input Setoran & Muraja'ah</span>
                    </h2>
                    <p class="text-[11px] sm:text-xs text-zinc-500 dark:text-zinc-400">
                        Pilih mode input yang paling sesuai dengan aktivitas bimbingan santri Anda
                    </p>
                </div>
            </div>

            {{-- 3 Tab Switcher Buttons --}}
            <div class="flex items-center gap-1.5 sm:gap-2 overflow-x-auto scrollbar-none p-1 rounded-2xl glass-liquid-inner border border-zinc-200/60 dark:border-white/10 shrink-0">
                
                {{-- Tab 1: Mode Spreadsheet --}}
                <a href="{{ route('spreadsheet-input.index') }}"
                   class="px-3.5 py-2 rounded-xl text-xs flex items-center gap-2 font-bold transition-all duration-200 shrink-0 select-none {{ $activeTab === 'spreadsheet' 
                       ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white shadow-md shadow-teal-500/25 ring-2 ring-teal-500/30' 
                       : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-white/10' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span>Mode Spreadsheet (Kelas)</span>
                </a>

                {{-- Tab 2: Mode Satuan --}}
                <a href="{{ route('hafalan-records.create') }}"
                   class="px-3.5 py-2 rounded-xl text-xs flex items-center gap-2 font-bold transition-all duration-200 shrink-0 select-none {{ $activeTab === 'single' 
                       ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white shadow-md shadow-teal-500/25 ring-2 ring-teal-500/30' 
                       : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-white/10' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>Mode Satuan (Per Santri)</span>
                </a>

                {{-- Tab 3: Mode Muraja'ah --}}
                <a href="{{ route('murajaah-records.fast-input') }}"
                   class="px-3.5 py-2 rounded-xl text-xs flex items-center gap-2 font-bold transition-all duration-200 shrink-0 select-none {{ $activeTab === 'murajaah' 
                       ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white shadow-md shadow-teal-500/25 ring-2 ring-teal-500/30' 
                       : 'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-white/50 dark:hover:bg-white/10' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Mode Muraja'ah</span>
                </a>

            </div>
        </div>
    </div>
</div>
