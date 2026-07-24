<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $title ?? 'Admin Dashboard' }}
            </h2>
            <p class="text-sm text-gray-500">
                {{ $subtitle ?? 'Monitoring operasional IMS.' }}
            </p>
        </div>
    </x-slot>

    @php
        $studentsProgress = collect(data_get($stats, 'students_progress', []));
        $latestTargets = collect(data_get($stats, 'latest_targets', []));
        $latestHafalanRecords = collect(data_get($stats, 'latest_hafalan_records', []));
        $latestMurajaahRecords = collect(data_get($stats, 'latest_murajaah_records', []));
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-2xl p-5">
                    <p class="text-sm text-gray-500 dark:text-zinc-400">Total Murid</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ data_get($stats, 'total_students', 0) }}</p>
                    <p class="text-xs text-gray-400 dark:text-zinc-500 mt-1">Aktif: {{ data_get($stats, 'active_students', 0) }}</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-2xl p-5">
                    <p class="text-sm text-gray-500 dark:text-zinc-400">Guru</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ data_get($stats, 'total_teachers', 0) }}</p>
                    <p class="text-xs text-gray-400 dark:text-zinc-500 mt-1">Orangtua: {{ data_get($stats, 'total_parents', 0) }}</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-2xl p-5">
                    <p class="text-sm text-gray-500 dark:text-zinc-400">Setoran Hari Ini</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ data_get($stats, 'hafalan_today', 0) }}</p>
                    <p class="text-xs text-gray-400 dark:text-zinc-500 mt-1">Murajaah: {{ data_get($stats, 'murajaah_today', 0) }}</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-2xl p-5">
                    <p class="text-sm text-gray-500 dark:text-zinc-400">Target Aktif</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ data_get($stats, 'active_targets', 0) }}</p>
                    <p class="text-xs text-red-500 mt-1">Terlambat: {{ data_get($stats, 'overdue_targets', 0) }}</p>
                </div>

                <div class="bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-2xl p-5">
                    <p class="text-sm text-gray-500 dark:text-zinc-400">Adab Hari Ini</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ data_get($stats, 'adab_filled_today', 0) }}<span class="text-sm text-gray-500 dark:text-zinc-500">/{{ data_get($stats, 'adab_total_students', 0) }}</span>
                    </p>
                    <p class="text-xs text-gray-400 dark:text-zinc-500 mt-1">Status Pengisian Murid</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                <a href="{{ url('/students') }}" class="bg-white dark:bg-zinc-900 shadow-sm rounded-2xl p-5 hover:shadow-md hover:scale-[1.01] transition-all duration-200 flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex items-center justify-center flex-shrink-0 transition-colors duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.109A2.25 2.25 0 0 1 12.75 21.5h-1.5a2.25 2.25 0 0 1-2.25-2.263V19.13m4.786-3.07a9.348 9.348 0 0 0-2.813-1.077M14.214 16.06c-.822-.656-1.854-1.06-2.964-1.06-1.11 0-2.142.404-2.964 1.06m8.892 0c.501.91.786 1.957.786 3.07v.003m-11.784 0a4.125 4.125 0 0 1-7.533-2.493 9.337 9.337 0 0 1 4.121-.952 9.38 9.38 0 0 1 2.625.372m0 3.07c0-1.113.285-2.16.786-3.07m-5.412 3.07v.109A2.25 2.25 0 0 0 4.5 21.5h1.5a2.25 2.25 0 0 0 2.25-2.263V19.13m4.786-3.07a9.348 9.348 0 0 1 2.813-1.077M8.906 16.06a9.38 9.38 0 0 0-2.813-1.077m0 0a9.338 9.338 0 0 1 5.626 0M8.906 16.06v-.003c0-1.113.285-2.16.786-3.07M12 12a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm6.5 2.5a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm-13 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-zinc-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors duration-150">Kelola Murid</h4>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Data murid, kelas, guru, dan wali murid.</p>
                    </div>
                </a>

                <a href="{{ url('/hafalan-targets') }}" class="bg-white dark:bg-zinc-900 shadow-sm rounded-2xl p-5 hover:shadow-md hover:scale-[1.01] transition-all duration-200 flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 flex items-center justify-center flex-shrink-0 transition-colors duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21a3.745 3.745 0 01-3.068-.593 3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-zinc-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors duration-150">Target Hafalan</h4>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Pantau target aktif, selesai, dan terlambat.</p>
                    </div>
                </a>

                <a href="{{ route('adab.index') }}" class="bg-white dark:bg-zinc-900 shadow-sm rounded-2xl p-5 hover:shadow-md hover:scale-[1.01] transition-all duration-200 flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20 flex items-center justify-center flex-shrink-0 transition-colors duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.03 0 1.9.693 2.166 1.638m-7.377 0A48.536 48.536 0 0 1 12 3c2.208 0 4.3.349 6.277.986M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375 0 1 1-.75 0 .375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375 0 1 1-.75 0 .375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375 0 1 1-.75 0 .375 0 0 1 .75 0Z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-zinc-900 dark:text-white group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors duration-150">Monitoring Adab</h4>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Kuisioner penilaian adab harian santri.</p>
                    </div>
                </a>

                <a href="{{ url('/reports') }}" class="bg-white dark:bg-zinc-900 shadow-sm rounded-2xl p-5 hover:shadow-md hover:scale-[1.01] transition-all duration-200 flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-xl bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20 flex items-center justify-center flex-shrink-0 transition-colors duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9Z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-zinc-900 dark:text-white group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors duration-150">Laporan & Rapor</h4>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Ekspor progres & pencetakan rapor digital.</p>
                    </div>
                </a>
            </div>

            <div class="bg-white dark:bg-zinc-900 shadow-sm rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">Progress Santri Aktif</h3>
                        <p class="text-sm text-gray-500">Diurutkan dari progress tertinggi.</p>
                    </div>
                    <a href="{{ url('/students') }}" class="text-sm text-emerald-700 hover:underline">Lihat semua</a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-5 py-3 text-left">Santri</th>
                                <th class="px-5 py-3 text-left">Kelas</th>
                                <th class="px-5 py-3 text-left">Progress</th>
                                <th class="px-5 py-3 text-left">Target Aktif</th>
                                <th class="px-5 py-3 text-left">Terlambat</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($studentsProgress as $item)
                                @php
                                    $student = $item['student'];
                                    $percentage = $item['progress_percentage'] ?? 0;
                                @endphp
                                <tr>
                                    <td class="px-5 py-3 font-medium text-gray-900">{{ $student->name }}</td>
                                    <td class="px-5 py-3 text-gray-600">
                                        {{ $student->classRoom?->name ?? '-' }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="w-48 bg-gray-100 rounded-full h-2">
                                            <div class="bg-emerald-600 h-2 rounded-full" style="width: {{ min($percentage, 100) }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ $percentage }}%</span>
                                    </td>
                                    <td class="px-5 py-3 text-gray-700">{{ $item['active_target_count'] ?? 0 }}</td>
                                    <td class="px-5 py-3 text-red-600">{{ $item['overdue_target_count'] ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-6 text-center text-gray-500">Belum ada data progress.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-zinc-900 shadow-sm rounded-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-zinc-100 dark:border-zinc-800/80">
                        <h3 class="font-semibold text-gray-900">Target Terdekat</h3>
                    </div>
                    <div class="divide-y">
                        @forelse ($latestTargets as $target)
                            <div class="px-5 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $target->student?->name ?? '-' }}</p>
                                        <p class="text-sm text-gray-600">
                                            {{ $target->surah?->name_latin ?? '-' }} ayat {{ $target->ayah_range }}
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            Guru: {{ $target->teacher?->user?->name ?? '-' }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $target->target_date?->format('d M Y') }}
                                        </p>
                                        <p class="text-xs {{ $target->is_overdue ? 'text-red-600' : 'text-gray-500' }}">
                                            {{ $target->status_label }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="px-5 py-6 text-center text-gray-500">Belum ada target.</div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white dark:bg-zinc-900 shadow-sm rounded-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-zinc-100 dark:border-zinc-800/80">
                        <h3 class="font-semibold text-gray-900">Setoran Hafalan Terbaru</h3>
                    </div>
                    <div class="divide-y">
                        @forelse ($latestHafalanRecords as $record)
                            <div class="px-5 py-4">
                                <p class="font-medium text-gray-900">{{ $record->student?->name ?? '-' }}</p>
                                <p class="text-sm text-gray-600">
                                    {{ $record->surah?->name_latin ?? '-' }} ayat {{ $record->ayah_range }}
                                </p>
                                <p class="text-xs text-gray-400">
                                    {{ $record->submitted_at?->format('d M Y') }} — {{ $record->status_label }}
                                </p>
                            </div>
                        @empty
                            <div class="px-5 py-6 text-center text-gray-500">Belum ada setoran.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-900 shadow-sm rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-zinc-100 dark:border-zinc-800/80">
                    <h3 class="font-semibold text-gray-900">Murajaah Terbaru</h3>
                </div>
                <div class="divide-y">
                    @forelse ($latestMurajaahRecords as $record)
                        <div class="px-5 py-4">
                            <p class="font-medium text-gray-900">{{ $record->student?->name ?? '-' }}</p>
                            <p class="text-sm text-gray-600">
                                {{ $record->surah?->name_latin ?? '-' }} ayat {{ $record->ayah_range }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ $record->reviewed_at?->format('d M Y') }} — {{ $record->status_label }}
                            </p>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-gray-500">Belum ada murajaah.</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>