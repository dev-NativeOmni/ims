<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                    Jadwal Pelajaran Tahfizh Kelas
                </h2>
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    Tentukan hari aktif pelajaran tahfizh untuk masing-masing kelas dengan mengubah pilihan Aktif/Off pada kolom hari di bawah.
                </p>
            </div>
            <div>
                <button type="submit" form="schedules-form" class="inline-flex items-center gap-2 rounded-xl bg-teal-600 hover:bg-teal-700 px-5 py-3 text-sm font-semibold text-white shadow shadow-teal-550/20 transition cursor-pointer">
                    💾 Simpan Perubahan Jadwal
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Success Alert Notification -->
            @if (session('success'))
                <div class="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/30 rounded-xl p-4 flex items-center gap-3">
                    <span class="text-emerald-600 dark:text-emerald-400 text-lg">✅</span>
                    <span class="text-sm text-emerald-800 dark:text-emerald-300 font-semibold">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Main Schedules Form -->
            <form id="schedules-form" method="POST" action="{{ route('class-schedules.update') }}">
                @csrf

                <!-- Kanban Grid -->
                <div class="grid grid-cols-1 md:grid-cols-7 gap-4 overflow-x-auto pb-4">
                    @foreach ($daysOfWeek as $dayNum => $dayName)
                        @php
                            $dayData = $scheduleBoard[$dayNum];
                            $activeClassesCount = count($dayData['classRooms']);
                        @endphp
                        <div class="bg-gray-50 dark:bg-zinc-900/60 border border-gray-200 dark:border-zinc-800 rounded-2xl p-4 flex flex-col min-w-[280px] md:min-w-[170px] shadow-sm">
                            
                            <!-- Column Header -->
                            <div class="flex justify-between items-center border-b dark:border-zinc-800 pb-2 mb-3">
                                <h3 class="font-extrabold text-sm text-gray-800 dark:text-zinc-200 uppercase tracking-wider">
                                    {{ $dayName }}
                                </h3>
                                <span class="bg-indigo-100 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-400 text-xs px-2 py-0.5 rounded-full font-bold">
                                    {{ $activeClassesCount }} Aktif
                                </span>
                            </div>

                            <!-- Cards Container (All Classes List) -->
                            <div class="space-y-3">
                                @foreach ($classRooms as $class)
                                    @php
                                        $isActive = in_array($dayNum, $class->tahfizh_days, true);
                                    @endphp
                                    <div x-data="{ status: '{{ $isActive ? '1' : '0' }}' }" class="bg-white dark:bg-zinc-850 border border-gray-150 dark:border-zinc-800 rounded-xl p-3 shadow-xs transition duration-150 hover:shadow-sm">
                                        <div class="flex flex-col gap-2">
                                            <div>
                                                <div class="font-bold text-xs text-gray-900 dark:text-white leading-tight">
                                                    {{ $class->name }}
                                                </div>
                                                <div class="text-[9px] text-gray-450 dark:text-zinc-500 uppercase tracking-wide font-medium mt-0.5">
                                                    {{ $class->program?->name }}
                                                </div>
                                            </div>
                                            
                                            <!-- Choice: Aktif vs Off (Radio Button Group) -->
                                            <div class="flex items-center bg-gray-100 dark:bg-zinc-800/80 rounded-lg p-0.5 border border-gray-200/50 dark:border-zinc-700 w-full justify-between">
                                                <!-- Aktif Option -->
                                                <label class="flex-1 cursor-pointer select-none">
                                                    <input type="radio" name="schedules[{{ $dayNum }}][{{ $class->id }}]" value="1" x-model="status" class="sr-only">
                                                    <span :class="status === '1' ? 'bg-emerald-600 text-white shadow-xs' : 'text-gray-500 dark:text-zinc-400 hover:text-gray-700'" class="text-center py-1 rounded-md text-[9px] font-extrabold block transition duration-150">
                                                        Aktif
                                                    </span>
                                                </label>
                                                <!-- Off Option -->
                                                <label class="flex-1 cursor-pointer select-none">
                                                    <input type="radio" name="schedules[{{ $dayNum }}][{{ $class->id }}]" value="0" x-model="status" class="sr-only">
                                                    <span :class="status === '0' ? 'bg-rose-600 text-white shadow-xs' : 'text-gray-450 dark:text-zinc-500 hover:text-gray-700'" class="text-center py-1 rounded-md text-[9px] font-extrabold block transition duration-150">
                                                        Off
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                        </div>
                    @endforeach
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
