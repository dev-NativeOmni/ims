<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                    Kalender Akademik & Hari Libur
                </h2>
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    Klik pada tanggal kalender untuk mengatur status hari sekolah, libur total, atau libur sebagian per kelas.
                </p>
            </div>
            <div>
                <button type="submit" form="calendar-form" class="inline-flex items-center gap-2 rounded-xl bg-teal-600 hover:bg-teal-700 px-5 py-3 text-sm font-semibold text-white shadow shadow-teal-500/20 transition cursor-pointer">
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
        selectedClassHolidays: {{ json_encode($classHolidays ?: (object)[]) }},
        
        modal: {
            isOpen: false,
            dateStr: '',
            dayNum: '',
            type: 'school', // 'school', 'global', 'partial'
            selectedClasses: []
        },

        openModal(dateStr, dayNum) {
            this.modal.dateStr = dateStr;
            this.modal.dayNum = dayNum;
            
            if (this.selectedHolidays.includes(dateStr)) {
                this.modal.type = 'global';
                this.modal.selectedClasses = [];
            } else if (this.selectedClassHolidays[dateStr] && this.selectedClassHolidays[dateStr].length > 0) {
                this.modal.type = 'partial';
                this.modal.selectedClasses = [...this.selectedClassHolidays[dateStr]];
            } else {
                this.modal.type = 'school';
                this.modal.selectedClasses = [];
            }
            this.modal.isOpen = true;
        },

        saveModal() {
            const dateStr = this.modal.dateStr;
            if (this.modal.type === 'school') {
                // Remove from global holidays
                this.selectedHolidays = this.selectedHolidays.filter(d => d !== dateStr);
                // Remove from class holidays
                delete this.selectedClassHolidays[dateStr];
            } else if (this.modal.type === 'global') {
                // Add to global holidays
                if (!this.selectedHolidays.includes(dateStr)) {
                    this.selectedHolidays.push(dateStr);
                }
                // Remove from class holidays
                delete this.selectedClassHolidays[dateStr];
            } else if (this.modal.type === 'partial') {
                // Remove from global holidays
                this.selectedHolidays = this.selectedHolidays.filter(d => d !== dateStr);
                // Set class holidays
                if (this.modal.selectedClasses.length > 0) {
                    this.selectedClassHolidays[dateStr] = [...this.modal.selectedClasses];
                } else {
                    delete this.selectedClassHolidays[dateStr];
                }
            }
            this.modal.isOpen = false;
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

                <!-- Hidden inputs for global holidays -->
                <template x-for="date in selectedHolidays" :key="date">
                    <input type="hidden" name="holidays[]" :value="date">
                </template>

                <!-- Hidden inputs for class-specific holidays -->
                <template x-for="(classIds, dateStr) in selectedClassHolidays" :key="dateStr">
                    <template x-for="classId in classIds" :key="classId">
                        <input type="hidden" :name="'class_holidays[' + dateStr + '][]'" :value="classId">
                    </template>
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
                                    @click="openModal('{{ $dateStr }}', {{ $dayNum }})"
                                    :class="selectedHolidays.includes('{{ $dateStr }}') 
                                        ? 'bg-rose-50 dark:bg-rose-950/20 border-rose-300 dark:border-rose-900' 
                                        : (selectedClassHolidays['{{ $dateStr }}'] && selectedClassHolidays['{{ $dateStr }}'].length > 0)
                                            ? 'bg-amber-50 dark:bg-amber-950/20 border-amber-300 dark:border-amber-900'
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
                                                ? 'bg-rose-100 dark:bg-rose-950/80 text-rose-700 dark:text-rose-450' 
                                                : (selectedClassHolidays['{{ $dateStr }}'] && selectedClassHolidays['{{ $dateStr }}'].length > 0)
                                                    ? 'bg-amber-100 dark:bg-amber-950/80 text-amber-700 dark:text-amber-450'
                                                    : '{{ $isWeekend ? 'bg-zinc-200 dark:bg-zinc-800 text-zinc-650 dark:text-zinc-400' : 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-450' }}'"
                                            class="text-[9px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider transition"
                                            x-text="selectedHolidays.includes('{{ $dateStr }}') ? 'Libur' : ((selectedClassHolidays['{{ $dateStr }}'] && selectedClassHolidays['{{ $dateStr }}'].length > 0) ? 'Sebagian' : '{{ $isWeekend ? 'Off' : 'Aktif' }}')"
                                        ></span>
                                    </div>

                                    <!-- Bottom Label / Icon -->
                                    <div class="mt-4 flex items-center gap-1">
                                        <template x-if="selectedHolidays.includes('{{ $dateStr }}')">
                                            <span class="text-[10px] text-rose-600 dark:text-rose-400 font-bold flex items-center gap-1">
                                                🚨 Libur Total
                                            </span>
                                        </template>
                                        <template x-if="selectedClassHolidays['{{ $dateStr }}'] && selectedClassHolidays['{{ $dateStr }}'].length > 0">
                                            <span class="text-[10px] text-amber-600 dark:text-amber-450 font-bold flex items-center gap-1">
                                                ⚠️ Libur <span x-text="selectedClassHolidays['{{ $dateStr }}'].length"></span> Kelas
                                            </span>
                                        </template>
                                        <template x-if="!selectedHolidays.includes('{{ $dateStr }}') && (!selectedClassHolidays['{{ $dateStr }}'] || selectedClassHolidays['{{ $dateStr }}'].length === 0)">
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

        <!-- Alpine.js Date Configuration Modal -->
        <div x-show="modal.isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <!-- Modal Backdrop Blur -->
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs"></div>

            <!-- Modal Content Wrapper -->
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div class="relative bg-white dark:bg-zinc-900 rounded-3xl max-w-lg w-full border border-gray-200 dark:border-zinc-800 p-6 shadow-2xl space-y-5" @click.away="modal.isOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-95 opacity-0">
                    
                    <!-- Header -->
                    <div class="flex justify-between items-center border-b dark:border-zinc-800 pb-3">
                        <div>
                            <h3 class="text-base font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                                📅 Atur Kalender Tanggal <span x-text="modal.dayNum" class="text-indigo-650 dark:text-indigo-400"></span>
                            </h3>
                            <p class="text-xs text-gray-500 mt-1" x-text="modal.dateStr"></p>
                        </div>
                        <button type="button" @click="modal.isOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-zinc-300 font-bold text-xl cursor-pointer">
                            &times;
                        </button>
                    </div>

                    <!-- Configuration Options -->
                    <div class="space-y-4">
                        <label class="block text-xs font-bold text-gray-400 dark:text-zinc-400 uppercase tracking-wider">Status Tanggal Ini</label>
                        
                        <div class="grid grid-cols-3 gap-3">
                            <!-- Hari Sekolah -->
                            <label class="border dark:border-zinc-800 rounded-2xl p-3 flex flex-col items-center gap-2 text-center cursor-pointer transition select-none hover:bg-gray-50 dark:hover:bg-zinc-850/50" :class="modal.type === 'school' ? 'border-emerald-500 bg-emerald-50/20 text-emerald-800 dark:text-emerald-400 dark:border-emerald-800' : 'bg-transparent text-gray-700 dark:text-zinc-300'">
                                <input type="radio" x-model="modal.type" value="school" class="sr-only">
                                <span class="text-lg">🏫</span>
                                <span class="text-xs font-bold">Hari Sekolah</span>
                            </label>

                            <!-- Libur Total -->
                            <label class="border dark:border-zinc-800 rounded-2xl p-3 flex flex-col items-center gap-2 text-center cursor-pointer transition select-none hover:bg-gray-50 dark:hover:bg-zinc-850/50" :class="modal.type === 'global' ? 'border-rose-500 bg-rose-50/20 text-rose-800 dark:text-rose-455 dark:border-rose-800' : 'bg-transparent text-gray-700 dark:text-zinc-300'">
                                <input type="radio" x-model="modal.type" value="global" class="sr-only">
                                <span class="text-lg">🚨</span>
                                <span class="text-xs font-bold">Libur Total</span>
                            </label>

                            <!-- Libur Sebagian -->
                            <label class="border dark:border-zinc-800 rounded-2xl p-3 flex flex-col items-center gap-2 text-center cursor-pointer transition select-none hover:bg-gray-50 dark:hover:bg-zinc-850/50" :class="modal.type === 'partial' ? 'border-amber-500 bg-amber-50/20 text-amber-800 dark:text-amber-450 dark:border-amber-800' : 'bg-transparent text-gray-700 dark:text-zinc-300'">
                                <input type="radio" x-model="modal.type" value="partial" class="sr-only">
                                <span class="text-lg">⚠️</span>
                                <span class="text-xs font-bold">Libur Sebagian</span>
                            </label>
                        </div>
                    </div>

                    <!-- Classroom Checklist (Shows only when Libur Sebagian is selected) -->
                    <div x-show="modal.type === 'partial'" x-transition class="space-y-3 pt-3 border-t dark:border-zinc-800 max-h-56 overflow-y-auto">
                        <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400">Centang kelas yang LIBUR (Tidak ada pelajaran) pada hari ini:</label>
                        
                        <div class="grid grid-cols-2 gap-2.5">
                            @foreach ($classRooms as $class)
                                <label class="flex items-center gap-3 p-2.5 rounded-xl border border-gray-100 dark:border-zinc-800/80 bg-gray-50/30 hover:bg-gray-50 dark:hover:bg-zinc-850 cursor-pointer transition select-none text-xs">
                                    <input 
                                        type="checkbox" 
                                        value="{{ $class->id }}" 
                                        :checked="modal.selectedClasses.includes({{ $class->id }})"
                                        @change="if ($el.checked) { if (!modal.selectedClasses.includes({{ $class->id }})) modal.selectedClasses.push({{ $class->id }}) } else { modal.selectedClasses = modal.selectedClasses.filter(id => id !== {{ $class->id }}) }"
                                        class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                    >
                                    <div>
                                        <span class="font-bold text-gray-800 dark:text-zinc-200 block">{{ $class->name }}</span>
                                        <span class="text-[9px] text-gray-400 dark:text-zinc-500 uppercase font-semibold">{{ $class->program?->name }}</span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex justify-end gap-3 border-t dark:border-zinc-800 pt-4 mt-6">
                        <button type="button" @click="modal.isOpen = false" class="px-4 py-2 border border-gray-300 dark:border-zinc-700 hover:bg-gray-100 dark:hover:bg-zinc-800 text-gray-700 dark:text-zinc-300 rounded-xl text-xs font-bold transition cursor-pointer">
                            Batal
                        </button>
                        <button type="button" @click="saveModal()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow transition cursor-pointer">
                            Terapkan
                        </button>
                    </div>

                </div>
            </div>
        </div>

    </div>
</x-app-layout>
