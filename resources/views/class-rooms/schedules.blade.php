<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                    Jadwal Pelajaran Tahfizh Kelas
                </h2>
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    Atur hari pelajaran tahfizh masing-masing kelas halaqoh untuk sinkronisasi otomatis kalender input spreadsheet & target laporan bulanan.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        isOpen: false,
        activeDay: 1,
        activeDayName: 'Senin',
        selectedClasses: [],
        openModal(dayNum, dayName, currentClassIds) {
            this.activeDay = dayNum;
            this.activeDayName = dayName;
            this.selectedClasses = [...currentClassIds];
            this.isOpen = true;
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

            <!-- Kanban Board Grid -->
            <div class="grid grid-cols-1 md:grid-cols-7 gap-4 overflow-x-auto pb-4">
                @foreach ($daysOfWeek as $dayNum => $dayName)
                    @php
                        $dayData = $scheduleBoard[$dayNum];
                        $currentClassIds = $dayData['classRooms']->pluck('id')->toArray();
                    @endphp
                    <div class="bg-gray-50 dark:bg-zinc-900/60 border border-gray-200 dark:border-zinc-800 rounded-2xl p-4 flex flex-col justify-between min-h-[450px] w-full md:min-w-[160px] shadow-sm">
                        
                        <!-- Column Header -->
                        <div class="space-y-3">
                            <div class="flex justify-between items-center border-b dark:border-zinc-800 pb-2">
                                <h3 class="font-extrabold text-sm text-gray-800 dark:text-zinc-200 uppercase tracking-wider">
                                    {{ $dayName }}
                                </h3>
                                <span class="bg-indigo-100 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-400 text-xs px-2 py-0.5 rounded-full font-bold">
                                    {{ count($dayData['classRooms']) }}
                                </span>
                            </div>

                            <!-- Cards Container -->
                            <div class="space-y-3">
                                @forelse ($dayData['classRooms'] as $class)
                                    <div class="bg-white dark:bg-zinc-850 border border-gray-150 dark:border-zinc-800 rounded-xl p-3 shadow-xs transition duration-150 hover:shadow-sm">
                                        <div class="font-bold text-xs text-gray-900 dark:text-white">
                                            {{ $class->name }}
                                        </div>
                                        <div class="text-[10px] text-gray-555 dark:text-zinc-400 mt-1 uppercase tracking-wide font-medium">
                                            {{ $class->program?->name }}
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-8 text-[11px] text-gray-400 dark:text-zinc-500 font-medium italic border border-dashed border-gray-200 dark:border-zinc-800 rounded-xl">
                                        Tidak ada jadwal
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Add/Configure Button -->
                        <div class="pt-4 mt-auto">
                            <button type="button" @click="openModal({{ $dayNum }}, '{{ $dayName }}', {{ json_encode($currentClassIds) }})" class="w-full inline-flex items-center justify-center gap-1.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow transition cursor-pointer">
                                📅 Atur Jadwal
                            </button>
                        </div>

                    </div>
                @endforeach
            </div>

        </div>

        <!-- Alpine.js Configuration Modal (Backdrop) -->
        <div x-show="isOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            
            <!-- Modal Backdrop Blur -->
            <div class="fixed inset-0 bg-black/60 backdrop-blur-xs"></div>

            <!-- Modal Content Wrapper -->
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div class="relative bg-white dark:bg-zinc-900 rounded-2xl max-w-md w-full border border-gray-200 dark:border-zinc-800 p-6 shadow-xl space-y-4" @click.away="isOpen = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="scale-95 opacity-0" x-transition:enter-end="scale-100 opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="scale-100 opacity-100" x-transition:leave-end="scale-95 opacity-0">
                    
                    <!-- Header -->
                    <div class="flex justify-between items-center border-b dark:border-zinc-800 pb-3">
                        <div>
                            <h3 class="text-base font-extrabold text-gray-900 dark:text-white flex items-center gap-1.5">
                                📅 Atur Jadwal Hari <span x-text="activeDayName" class="text-indigo-650 dark:text-indigo-400"></span>
                            </h3>
                            <p class="text-xs text-gray-550 mt-1">Centang kelas yang akan belajar di hari ini.</p>
                        </div>
                        <button type="button" @click="isOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-zinc-300 font-bold text-lg cursor-pointer">
                            &times;
                        </button>
                    </div>

                    <!-- form -->
                    <form method="POST" action="{{ route('class-schedules.update') }}">
                        @csrf
                        <input type="hidden" name="day" :value="activeDay">

                        <!-- Checkbox List -->
                        <div class="max-h-60 overflow-y-auto space-y-2.5 pr-2">
                            @foreach ($classRooms as $class)
                                <label class="flex items-center justify-between p-3 rounded-xl border border-gray-150 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-900/30 hover:bg-gray-50 dark:hover:bg-zinc-850 cursor-pointer transition select-none">
                                    <div class="flex items-center gap-3">
                                        <input type="checkbox" name="class_room_ids[]" value="{{ $class->id }}" :checked="selectedClasses.includes({{ $class->id }})" @change="if ($el.checked) { if (!selectedClasses.includes({{ $class->id }})) selectedClasses.push({{ $class->id }}) } else { selectedClasses = selectedClasses.filter(id => id !== {{ $class->id }}) }" class="rounded border-gray-300 dark:border-zinc-700 bg-transparent text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-xs font-bold text-gray-800 dark:text-zinc-200">{{ $class->name }}</span>
                                    </div>
                                    <span class="text-[10px] text-gray-500 uppercase font-medium tracking-wide">
                                        {{ $class->program?->name }}
                                    </span>
                                </label>
                            @endforeach
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex justify-end gap-3 border-t dark:border-zinc-800 pt-4 mt-6">
                            <button type="button" @click="isOpen = false" class="px-4 py-2 border border-gray-300 dark:border-zinc-700 hover:bg-gray-100 dark:hover:bg-zinc-800 text-gray-700 dark:text-zinc-300 rounded-xl text-xs font-bold transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow transition cursor-pointer">
                                Simpan Jadwal
                            </button>
                        </div>
                    </form>

                </div>
            </div>

        </div>

    </div>
</x-app-layout>
