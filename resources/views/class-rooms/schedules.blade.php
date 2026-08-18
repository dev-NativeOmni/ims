<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex flex-col gap-1">
                <h2 class="font-semibold text-xl text-gray-900 dark:text-zinc-150 leading-tight">
                    Jadwal Pelajaran Tahfizh Kelas
                </h2>
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    Tentukan hari aktif pelajaran tahfizh untuk masing-masing kelas dengan mengubah pilihan Aktif/Non Aktif pada kolom hari di bawah.
                </p>
            </div>
            <div>
                <button type="submit" form="schedules-form" class="inline-flex items-center gap-2 rounded-xl bg-teal-600 hover:bg-teal-700 px-5 py-3 text-sm font-semibold text-white shadow shadow-teal-550/20 transition cursor-pointer">
                    <x-heroicon-o-arrow-down-on-square class="w-4 h-4" /> Simpan Perubahan Jadwal
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ activeDay: '{{ array_key_first($daysOfWeek) }}' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Success Alert Notification -->
            @if (session('success'))
                <div class="bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-900/30 rounded-xl p-4 flex items-center gap-3">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                    <span class="text-sm text-emerald-800 dark:text-emerald-300 font-semibold">{{ session('success') }}</span>
                </div>
            @endif

            <!-- Day Selector Tabs (Toggles) -->
            <div class="flex flex-wrap gap-2 p-1.5 bg-gray-100 dark:bg-zinc-800 rounded-2xl border border-gray-255 dark:border-zinc-700 max-w-4xl shadow-xs">
                @foreach ($daysOfWeek as $dayNum => $dayName)
                    <button type="button" 
                            @click="activeDay = '{{ $dayNum }}'" 
                            :class="activeDay === '{{ $dayNum }}' ? 'bg-teal-600 text-white shadow-xs' : 'text-gray-600 dark:text-zinc-400 hover:text-gray-900 hover:bg-gray-200/50 dark:hover:bg-zinc-700/50'"
                            class="flex-1 py-2 px-4 rounded-xl text-xs font-extrabold uppercase tracking-wider transition duration-150 cursor-pointer text-center select-none">
                        {{ $dayName }}
                    </button>
                @endforeach
            </div>

            <!-- Main Schedules Form -->
            <form id="schedules-form" method="POST" action="{{ route('class-schedules.update') }}">
                @csrf

                <!-- Day Cards Stack -->
                <div>
                    @foreach ($daysOfWeek as $dayNum => $dayName)
                        @php
                            $dayData = $scheduleBoard[$dayNum];
                            $activeClassesCount = count($dayData['classRooms']);
                            
                            $kelas10 = $classRooms->filter(fn($c) => preg_match('/^X\b/i', $c->name));
                            $kelas11 = $classRooms->filter(fn($c) => preg_match('/^XI\b/i', $c->name));
                            $kelas12 = $classRooms->filter(fn($c) => preg_match('/^XII\b/i', $c->name));
                        @endphp
                        
                        <div x-show="activeDay === '{{ $dayNum }}'" 
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 transform translate-y-1"
                             x-transition:enter-end="opacity-100 transform translate-y-0"
                             class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-3xl p-6 shadow-xs space-y-5">
                            
                            <!-- Day Header -->
                            <div class="flex justify-between items-center border-b dark:border-zinc-800 pb-3">
                                <h3 class="font-extrabold text-base text-gray-800 dark:text-zinc-200 uppercase tracking-wider flex items-center gap-2">
                                    <x-heroicon-o-calendar class="w-5 h-5 text-indigo-600 dark:text-indigo-400" /> {{ $dayName }}
                                </h3>
                                <span class="bg-indigo-100 dark:bg-indigo-950/50 text-indigo-750 dark:text-indigo-405 text-xs px-3 py-1 rounded-full font-extrabold">
                                    {{ $activeClassesCount }} Kelas Aktif
                                </span>
                            </div>

                            <!-- Group Rows by Grade -->
                            <div class="space-y-5 divide-y divide-gray-100 dark:divide-zinc-800/60">
                                
                                <!-- Kelas 10 Row -->
                                @if ($kelas10->isNotEmpty())
                                    <div class="flex flex-col md:flex-row md:items-center gap-4 pt-4 first:pt-0">
                                        <div class="md:w-28 shrink-0">
                                            <span class="bg-blue-50 dark:bg-blue-950/20 text-blue-700 dark:text-blue-400 text-xs px-3 py-1.5 rounded-xl font-bold uppercase tracking-wider block text-center md:text-left">
                                                Kelas 10
                                            </span>
                                        </div>
                                        <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-zinc-800 w-full">
                                            @foreach ($kelas10 as $class)
                                                @php
                                                    $isActive = in_array($dayNum, $class->tahfizh_days, true);
                                                @endphp
                                                @include('class-rooms.partials.schedule-card', ['class' => $class, 'dayNum' => $dayNum, 'isActive' => $isActive])
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Kelas 11 Row -->
                                @if ($kelas11->isNotEmpty())
                                    <div class="flex flex-col md:flex-row md:items-center gap-4 pt-4">
                                        <div class="md:w-28 shrink-0">
                                            <span class="bg-amber-50 dark:bg-amber-950/20 text-amber-700 dark:text-amber-400 text-xs px-3 py-1.5 rounded-xl font-bold uppercase tracking-wider block text-center md:text-left">
                                                Kelas 11
                                            </span>
                                        </div>
                                        <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-zinc-800 w-full">
                                            @foreach ($kelas11 as $class)
                                                @php
                                                    $isActive = in_array($dayNum, $class->tahfizh_days, true);
                                                @endphp
                                                @include('class-rooms.partials.schedule-card', ['class' => $class, 'dayNum' => $dayNum, 'isActive' => $isActive])
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <!-- Kelas 12 Row -->
                                @if ($kelas12->isNotEmpty())
                                    <div class="flex flex-col md:flex-row md:items-center gap-4 pt-4">
                                        <div class="md:w-28 shrink-0">
                                            <span class="bg-purple-50 dark:bg-purple-950/20 text-purple-700 dark:text-purple-400 text-xs px-3 py-1.5 rounded-xl font-bold uppercase tracking-wider block text-center md:text-left">
                                                Kelas 12
                                            </span>
                                        </div>
                                        <div class="flex gap-3 overflow-x-auto pb-2 scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-zinc-800 w-full">
                                            @foreach ($kelas12 as $class)
                                                @php
                                                    $isActive = in_array($dayNum, $class->tahfizh_days, true);
                                                @endphp
                                                @include('class-rooms.partials.schedule-card', ['class' => $class, 'dayNum' => $dayNum, 'isActive' => $isActive])
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                            </div>
                        </div>
                    @endforeach
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
