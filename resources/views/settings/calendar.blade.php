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
        $todayStr = date('Y-m-d');
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
                this.selectedHolidays = this.selectedHolidays.filter(d => d !== dateStr);
                delete this.selectedClassHolidays[dateStr];
            } else if (this.modal.type === 'global') {
                if (!this.selectedHolidays.includes(dateStr)) {
                    this.selectedHolidays.push(dateStr);
                }
                delete this.selectedClassHolidays[dateStr];
            } else if (this.modal.type === 'partial') {
                this.selectedHolidays = this.selectedHolidays.filter(d => d !== dateStr);
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

            <!-- Google Calendar Top Control Toolbar -->
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                
                <!-- Left Section: Navigation & Title -->
                <div class="flex items-center gap-3">
                    <!-- Today Button -->
                    <a href="{{ route('academic-calendar.index', ['year' => date('Y'), 'month' => date('m')]) }}" class="px-4 py-2 border border-gray-300 dark:border-zinc-700 text-gray-700 dark:text-zinc-200 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                        <span>🗓️</span> Hari Ini
                    </a>

                    <!-- Prev/Next Month Arrows -->
                    <div class="flex items-center bg-gray-100 dark:bg-zinc-800/80 rounded-xl p-0.5 border border-gray-200 dark:border-zinc-700/60">
                        <a href="{{ route('academic-calendar.index', ['year' => $prevYear, 'month' => $prevMonth]) }}" title="Bulan Sebelumnya" class="w-8 h-8 flex items-center justify-center text-gray-700 dark:text-zinc-300 hover:bg-white dark:hover:bg-zinc-700 rounded-lg text-base font-bold transition">
                            ‹
                        </a>
                        <a href="{{ route('academic-calendar.index', ['year' => $nextYear, 'month' => $nextMonth]) }}" title="Bulan Berikutnya" class="w-8 h-8 flex items-center justify-center text-gray-700 dark:text-zinc-300 hover:bg-white dark:hover:bg-zinc-700 rounded-lg text-base font-bold transition">
                            ›
                        </a>
                    </div>

                    <!-- Current Month & Year Display Title -->
                    <h3 class="text-lg md:text-xl font-black text-gray-900 dark:text-white tracking-tight ml-2">
                        {{ $monthsList[$month] }} {{ $year }}
                    </h3>
                </div>

                <!-- Right Section: Quick Month/Year Dropdown Selectors -->
                <form method="GET" action="{{ route('academic-calendar.index') }}" class="flex items-center gap-2">
                    <select name="month" id="month" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white text-xs font-semibold py-2 px-3 shadow-xs focus:ring-indigo-500">
                        @foreach ($monthsList as $num => $name)
                            <option value="{{ $num }}" {{ $num === $month ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>

                    <select name="year" id="year" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-[#09090b]/40 dark:text-white text-xs font-semibold py-2 px-3 shadow-xs focus:ring-indigo-500">
                        @foreach ($yearsList as $yr)
                            <option value="{{ $yr }}" {{ $yr === $year ? 'selected' : '' }}>{{ $yr }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow transition cursor-pointer">
                        🔍 Filter
                    </button>
                </form>
            </div>

            <!-- Academic Calendar Form -->
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

                <!-- Main Calendar Grid Container (Explicit 7-Column Layout Inline Style for 100% Compatibility) -->
                <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-3xl overflow-hidden shadow-sm">
                    
                    <!-- 7-Column Day Names Header (Sunday to Saturday) -->
                    <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); text-align: center;" class="border-b border-gray-200 dark:border-zinc-800 bg-gray-50/80 dark:bg-zinc-850/60">
                        @foreach (['MINGGU', 'SENIN', 'SELASA', 'RABU', 'KAMIS', 'JUMAT', 'SABTU'] as $idx => $dayName)
                            <div class="py-3 text-[11px] font-black tracking-wider uppercase {{ $idx === 0 || $idx === 6 ? 'text-rose-600 dark:text-rose-400' : 'text-gray-500 dark:text-zinc-400' }}">
                                {{ $dayName }}
                            </div>
                        @endforeach
                    </div>

                    <!-- 7-Column Date Cells Grid -->
                    <div style="display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); border-left: 1px solid #e5e7eb; border-top: 1px solid #e5e7eb;" class="dark:border-zinc-800 bg-gray-200 dark:bg-zinc-800 gap-[1px]">
                        @foreach ($gridDates as $day)
                            @php
                                $dateStr = $day['date']->toDateString();
                                $dayNum = $day['date']->day;
                                $dayOfWeek = $day['date']->dayOfWeek; // 0=Sunday, 6=Saturday
                                $isWeekend = ($dayOfWeek === 0 || $dayOfWeek === 6);
                                $isToday = ($dateStr === $todayStr);
                            @endphp

                            @if ($day['isCurrentMonth'])
                                <!-- Current Month Active Date Cell -->
                                <div 
                                    @click="openModal('{{ $dateStr }}', {{ $dayNum }})"
                                    :class="selectedHolidays.includes('{{ $dateStr }}') 
                                        ? 'bg-rose-50/80 dark:bg-rose-950/20' 
                                        : (selectedClassHolidays['{{ $dateStr }}'] && selectedClassHolidays['{{ $dateStr }}'].length > 0)
                                            ? 'bg-amber-50/80 dark:bg-amber-950/20'
                                            : '{{ $isWeekend ? 'bg-gray-50/80 dark:bg-zinc-900/90' : 'bg-white dark:bg-zinc-900' }} hover:bg-indigo-50/50 dark:hover:bg-zinc-800/60'"
                                    style="min-height: 120px;"
                                    class="p-2.5 flex flex-col justify-between cursor-pointer select-none transition duration-150 group"
                                >
                                    <!-- Top Row: Date Number & Today Circle Badge -->
                                    <div class="flex justify-between items-start">
                                        @if ($isToday)
                                            <!-- Google Calendar Blue Circle Badge for Today -->
                                            <span class="w-6 h-6 rounded-full bg-blue-600 text-white font-black flex items-center justify-center text-xs shadow-md shadow-blue-500/30">
                                                {{ $dayNum }}
                                            </span>
                                        @else
                                            <span class="font-bold text-xs {{ $isWeekend ? 'text-rose-600 dark:text-rose-400' : 'text-gray-900 dark:text-zinc-100' }} px-1 py-0.5">
                                                {{ $dayNum }}
                                            </span>
                                        @endif

                                        <!-- Corner Weekend / Off Indicator -->
                                        @if ($isWeekend)
                                            <span class="text-[9px] text-rose-500/80 dark:text-rose-400/80 font-bold uppercase tracking-wider">
                                                Off
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Event Pills / Holiday Tags Slot (Solid Google Calendar Style Bars) -->
                                    <div class="mt-2 space-y-1">
                                        <!-- Global Total Holiday Solid Event Pill -->
                                        <template x-if="selectedHolidays.includes('{{ $dateStr }}')">
                                            <div class="bg-emerald-600 text-white rounded-md px-2 py-1 text-[11px] font-medium truncate shadow-xs">
                                                🚨 Libur Total
                                            </div>
                                        </template>

                                        <!-- Partial Class Holiday Solid Event Pill -->
                                        <template x-if="selectedClassHolidays['{{ $dateStr }}'] && selectedClassHolidays['{{ $dateStr }}'].length > 0">
                                            <div class="bg-amber-600 text-white rounded-md px-2 py-1 text-[11px] font-medium truncate shadow-xs">
                                                ⚠️ Libur <span x-text="selectedClassHolidays['{{ $dateStr }}'].length"></span> Kelas
                                            </div>
                                        </template>

                                        <!-- Default Active School Day Pill on Hover -->
                                        <template x-if="!selectedHolidays.includes('{{ $dateStr }}') && (!selectedClassHolidays['{{ $dateStr }}'] || selectedClassHolidays['{{ $dateStr }}'].length === 0)">
                                            <div class="opacity-0 group-hover:opacity-100 transition duration-150 bg-gray-200 dark:bg-zinc-700 text-gray-700 dark:text-zinc-200 rounded-md px-2 py-1 text-[10px] font-semibold truncate">
                                                {{ $isWeekend ? 'Akhir Pekan' : 'Hari Sekolah' }}
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            @else
                                <!-- Non-Current Month Padded Date Cell (Muted Gray) -->
                                <div style="min-height: 120px;" class="p-2.5 bg-gray-50/50 dark:bg-zinc-950/40 opacity-40 select-none flex flex-col justify-between">
                                    <span class="font-semibold text-xs text-gray-400 dark:text-zinc-600 px-1 py-0.5">
                                        {{ $dayNum }}
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>

                </div>
            </form>

        </div>

        <!-- Alpine.js Date Configuration Modal Popup -->
        <div x-show="modal.isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <!-- Modal Backdrop Blur -->
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs"></div>

            <!-- Modal Content Wrapper -->
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div class="relative bg-white dark:bg-zinc-900 rounded-3xl max-w-md w-full border border-gray-200 dark:border-zinc-800 p-6 shadow-2xl space-y-5" @click.away="modal.isOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-95 opacity-0">
                    
                    <!-- Header -->
                    <div class="flex justify-between items-center border-b dark:border-zinc-800 pb-3">
                        <div>
                            <h3 class="text-base font-extrabold text-gray-900 dark:text-white flex items-center gap-2">
                                📅 Atur Tanggal <span x-text="modal.dayNum" class="text-blue-600 dark:text-blue-400"></span>
                            </h3>
                            <p class="text-xs text-gray-500 mt-0.5" x-text="modal.dateStr"></p>
                        </div>
                        <button type="button" @click="modal.isOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-zinc-300 font-bold text-xl cursor-pointer">
                            &times;
                        </button>
                    </div>

                    <!-- Configuration Options -->
                    <div class="space-y-4">
                        <label class="block text-xs font-bold text-gray-400 dark:text-zinc-400 uppercase tracking-wider">Status Hari Ini</label>
                        
                        <div class="grid grid-cols-3 gap-2.5">
                            <!-- Hari Sekolah -->
                            <label class="border dark:border-zinc-800 rounded-2xl p-3 flex flex-col items-center gap-1.5 text-center cursor-pointer transition select-none hover:bg-gray-50 dark:hover:bg-zinc-850/50" :class="modal.type === 'school' ? 'border-emerald-500 bg-emerald-50/30 text-emerald-800 dark:text-emerald-400 dark:border-emerald-800 shadow-xs' : 'bg-transparent text-gray-700 dark:text-zinc-300'">
                                <input type="radio" x-model="modal.type" value="school" class="sr-only">
                                <span class="text-lg">🏫</span>
                                <span class="text-[11px] font-bold">Hari Sekolah</span>
                            </label>

                            <!-- Libur Total -->
                            <label class="border dark:border-zinc-800 rounded-2xl p-3 flex flex-col items-center gap-1.5 text-center cursor-pointer transition select-none hover:bg-gray-50 dark:hover:bg-zinc-850/50" :class="modal.type === 'global' ? 'border-rose-500 bg-rose-50/30 text-rose-800 dark:text-rose-400 dark:border-rose-800 shadow-xs' : 'bg-transparent text-gray-700 dark:text-zinc-300'">
                                <input type="radio" x-model="modal.type" value="global" class="sr-only">
                                <span class="text-lg">🚨</span>
                                <span class="text-[11px] font-bold">Libur Total</span>
                            </label>

                            <!-- Libur Sebagian -->
                            <label class="border dark:border-zinc-800 rounded-2xl p-3 flex flex-col items-center gap-1.5 text-center cursor-pointer transition select-none hover:bg-gray-50 dark:hover:bg-zinc-850/50" :class="modal.type === 'partial' ? 'border-amber-500 bg-amber-50/30 text-amber-800 dark:text-amber-400 dark:border-amber-800 shadow-xs' : 'bg-transparent text-gray-700 dark:text-zinc-300'">
                                <input type="radio" x-model="modal.type" value="partial" class="sr-only">
                                <span class="text-lg">⚠️</span>
                                <span class="text-[11px] font-bold">Libur Sebagian</span>
                            </label>
                        </div>
                    </div>

                    <!-- Classroom Checklist (Shows only when Libur Sebagian is selected) -->
                    <div x-show="modal.type === 'partial'" x-transition class="space-y-3 pt-3 border-t dark:border-zinc-800 max-h-56 overflow-y-auto">
                        <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400">Centang kelas yang LIBUR pada hari ini:</label>
                        
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($classRooms as $class)
                                <label class="flex items-center gap-2.5 p-2 rounded-xl border border-gray-100 dark:border-zinc-800/80 bg-gray-50/40 hover:bg-gray-50 dark:hover:bg-zinc-850 cursor-pointer transition select-none text-xs">
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
                    <div class="flex justify-end gap-2.5 border-t dark:border-zinc-800 pt-4 mt-6">
                        <button type="button" @click="modal.isOpen = false" class="px-4 py-2 border border-gray-300 dark:border-zinc-700 hover:bg-gray-100 dark:hover:bg-zinc-800 text-gray-700 dark:text-zinc-300 rounded-xl text-xs font-bold transition cursor-pointer">
                            Batal
                        </button>
                        <button type="button" @click="saveModal()" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow transition cursor-pointer">
                            Terapkan
                        </button>
                    </div>

                </div>
            </div>
        </div>

    </div>
</x-app-layout>
