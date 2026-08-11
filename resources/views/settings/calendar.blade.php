<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                    Kalender Akademik & Hari Libur
                </h2>
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    Klik pada tanggal kalender untuk menandai hari libur sekolah, acara khusus, atau tanggal merah. Hari libur otomatis disembunyikan dari lembar input spreadsheet guru dan mengurangi target hafalan.
                </p>
            </div>
            <div>
                <button type="submit" form="calendar-form" class="inline-flex items-center gap-2 rounded-xl bg-teal-650 hover:bg-teal-700 px-5 py-3 text-sm font-semibold text-white shadow shadow-teal-500/20 transition cursor-pointer">
                    💾 Simpan Kalender
                </button>
            </div>
        </div>
    </x-slot>

    @php
        $monthsList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $currentYear = (int)date('Y');
        $yearsList = range($currentYear - 2, $currentYear + 3);
    @endphp

    <div class="py-8" x-data="{
        selectedHolidays: {{ json_encode($holidays) }},
        toggleHoliday(dateStr) {
            if (this.selectedHolidays.includes(dateStr)) {
                this.selectedHolidays = this.selectedHolidays.filter(d => d !== dateStr);
            } else {
                this.selectedHolidays.push(dateStr);
            }
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Success Alert Notification -->
            @if (session('success'))
                <div class="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/30 rounded-xl p-4 flex items-center gap-3">
                    <span class="text-emerald-600 dark:text-emerald-400 text-lg">✅</span>
                    <span class="text-sm text-emerald-800 dark:text-emerald-300 font-semibold">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Month & Year Filters -->
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 shadow-xs">
                <form method="GET" action="{{ route('academic-calendar.index') }}" class="flex flex-wrap items-end gap-4">
                    <div class="space-y-1.5 flex-1 min-w-[200px]">
                        <label for="month" class="block text-xs font-bold text-gray-700 dark:text-zinc-350 uppercase tracking-wider">Pilih Bulan</label>
                        <select name="month" id="month" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            @foreach ($monthsList as $num => $name)
                                <option value="{{ $num }}" {{ $num === $month ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1.5 flex-1 min-w-[150px]">
                        <label for="year" class="block text-xs font-bold text-gray-700 dark:text-zinc-355 uppercase tracking-wider">Pilih Tahun</label>
                        <select name="year" id="year" class="block w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            @foreach ($yearsList as $yr)
                                <option value="{{ $yr }}" {{ $yr === $year ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-semibold shadow transition cursor-pointer">
                        🔍 Tampilkan
                    </button>
                </form>
            </div>

            <!-- Calendar Academic Form -->
            <form id="calendar-form" method="POST" action="{{ route('academic-calendar.update') }}">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="month" value="{{ $month }}">

                <!-- Hidden inputs for selected holidays to be submitted -->
                <template x-for="date in selectedHolidays" :key="date">
                    <input type="hidden" name="holidays[]" :value="date">
                </template>

                <!-- Grid Calendar Container -->
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-3xl p-6 shadow-sm">
                    
                    <!-- Weekday Header -->
                    <div class="grid grid-cols-7 gap-3 mb-4 text-center">
                        @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $dayName)
                            <div class="font-extrabold text-xs text-gray-500 dark:text-zinc-450 uppercase tracking-wider py-2">
                                {{ $dayName }}
                            </div>
                        @endforeach
                    </div>

                    <!-- Monthly Days Grid -->
                    <div class="grid grid-cols-7 gap-3">
                        @foreach ($gridDates as $day)
                            @php
                                $dateStr = $day['date']->toDateString();
                                $dayNum = $day['date']->day;
                                $dayOfWeek = $day['date']->dayOfWeekIso;
                                $isWeekend = ($dayOfWeek >= 6);
                            @endphp

                            @if ($day['isCurrentMonth'])
                                <!-- Active Interactive Date Cell -->
                                <div 
                                    @click="toggleHoliday('{{ $dateStr }}')"
                                    :class="selectedHolidays.includes('{{ $dateStr }}') 
                                        ? 'bg-rose-50 dark:bg-rose-950/20 border-rose-300 dark:border-rose-900' 
                                        : '{{ $isWeekend ? 'bg-gray-50 dark:bg-zinc-850/50 border-gray-200 dark:border-zinc-800' : 'bg-white dark:bg-zinc-900 border-gray-200 dark:border-zinc-800' }} hover:border-indigo-400 dark:hover:border-indigo-800'"
                                    class="border rounded-2xl p-4 min-h-[90px] flex flex-col justify-between cursor-pointer select-none transition duration-150"
                                >
                                    <!-- Date Number -->
                                    <div class="flex justify-between items-start">
                                        <span class="font-black text-sm text-gray-900 dark:text-white">
                                            {{ $dayNum }}
                                        </span>
                                        <!-- Mini Badge Status -->
                                        <span 
                                            :class="selectedHolidays.includes('{{ $dateStr }}') 
                                                ? 'bg-rose-100 dark:bg-rose-950/80 text-rose-700 dark:text-rose-400' 
                                                : '{{ $isWeekend ? 'bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400' : 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400' }}'"
                                            class="text-[9px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider transition"
                                            x-text="selectedHolidays.includes('{{ $dateStr }}') ? 'Libur' : '{{ $isWeekend ? 'Off' : 'Aktif' }}'"
                                        ></span>
                                    </div>

                                    <!-- Bottom Label / Icon -->
                                    <div class="mt-4 flex items-center gap-1">
                                        <template x-if="selectedHolidays.includes('{{ $dateStr }}')">
                                            <span class="text-[10px] text-rose-600 dark:text-rose-400 font-bold flex items-center gap-1">
                                                🚨 Acara/Libur
                                            </span>
                                        </template>
                                        <template x-if="!selectedHolidays.includes('{{ $dateStr }}')">
                                            <span class="text-[10px] text-gray-400 dark:text-zinc-500 font-medium">
                                                {{ $isWeekend ? 'Akhir Pekan' : 'Hari Sekolah' }}
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            @else
                                <!-- Non-Current Month Padded Date Cell (Muted) -->
                                <div class="border border-gray-100 dark:border-zinc-850 bg-gray-50/30 dark:bg-zinc-950/20 opacity-30 rounded-2xl p-4 min-h-[90px] flex flex-col justify-between select-none">
                                    <span class="font-bold text-sm text-gray-400 dark:text-zinc-600">
                                        {{ $dayNum }}
                                    </span>
                                    <span class="text-[9px] text-gray-400 dark:text-zinc-600 font-medium">
                                        Luar Bulan
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>

                </div>
            </form>

        </div>
    </div>
</x-app-layout>
