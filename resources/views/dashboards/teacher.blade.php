<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard Guru
            </h2>
            <p class="text-sm text-gray-500">
                Monitoring murid bimbingan, setoran, murajaah, dan target hafalan.
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

            @if (! data_get($stats, 'teacher'))
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-5">
                    Akun guru ini belum memiliki profil guru. Hubungi admin.
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-zinc-900 shadow-sm hover:shadow-md transition-shadow duration-200 rounded-2xl p-5">
                    <p class="text-sm text-gray-500 dark:text-zinc-400">Murid Bimbingan</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ data_get($stats, 'total_students', 0) }}</p>
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
                    <p class="text-sm text-gray-500 dark:text-zinc-400">Butuh Perhatian</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white">
                        {{ data_get($stats, 'hafalan_need_attention', 0) + data_get($stats, 'murajaah_need_attention', 0) }}
                    </p>
                    <p class="text-xs text-gray-400 dark:text-zinc-500 mt-1">Hafalan + Murajaah</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <a href="{{ url('/hafalan-records/create') }}" class="bg-white dark:bg-zinc-900 shadow-sm rounded-2xl p-5 hover:shadow-md hover:scale-[1.01] transition-all duration-200 flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex items-center justify-center flex-shrink-0 transition-colors duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-zinc-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors duration-150">Input Setoran</h4>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Catat setoran hafalan baru murid.</p>
                    </div>
                </a>

                <a href="{{ url('/murajaah-records/create') }}" class="bg-white dark:bg-zinc-900 shadow-sm rounded-2xl p-5 hover:shadow-md hover:scale-[1.01] transition-all duration-200 flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-xl bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20 flex items-center justify-center flex-shrink-0 transition-colors duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12c0-1.232-.046-2.453-.138-3.662a4.006 4.006 0 0 0-3.7-3.7 48.656 48.656 0 0 0-7.324 0 4.006 4.006 0 0 0-3.7 3.7C4.547 9.547 4.5 10.768 4.5 12s.047 2.453.138 3.662a4.006 4.006 0 0 0 3.7 3.7 48.656 48.656 0 0 0 7.324 0 4.006 4.006 0 0 0 3.7-3.7c.092-1.209.138-2.43.138-3.662Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 10.5h6M9 13.5h6" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-zinc-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors duration-150">Input Murajaah</h4>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Catat evaluasi pengulangan hafalan.</p>
                    </div>
                </a>

                <a href="{{ url('/hafalan-targets/create') }}" class="bg-white dark:bg-zinc-900 shadow-sm rounded-2xl p-5 hover:shadow-md hover:scale-[1.01] transition-all duration-200 flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-xl bg-teal-500/10 text-teal-600 dark:text-teal-400 border border-teal-500/20 flex items-center justify-center flex-shrink-0 transition-colors duration-150">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-zinc-900 dark:text-white group-hover:text-teal-600 dark:group-hover:text-teal-400 transition-colors duration-150">Buat Target</h4>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">Tetapkan sasaran/target hafalan murid.</p>
                    </div>
                </a>
            </div>

            <div class="bg-white dark:bg-zinc-900 shadow-sm rounded-2xl overflow-hidden">
                <div class="px-5 py-4 border-b border-zinc-100 dark:border-zinc-800/80">
                    <h3 class="font-semibold text-gray-900">Progress Murid Bimbingan</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-5 py-3 text-left">Murid</th>
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
                                    <td class="px-5 py-3 text-gray-600">{{ $student->classRoom?->name ?? '-' }}</td>
                                    <td class="px-5 py-3">
                                        <div class="w-48 bg-gray-100 rounded-full h-2">
                                            <div class="bg-emerald-600 h-2 rounded-full" style="width: {{ min($percentage, 100) }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ $percentage }}%</span>
                                    </td>
                                    <td class="px-5 py-3">{{ $item['active_target_count'] ?? 0 }}</td>
                                    <td class="px-5 py-3 text-red-600">{{ $item['overdue_target_count'] ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-6 text-center text-gray-500">Belum ada murid bimbingan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-zinc-900 shadow-sm rounded-2xl overflow-hidden">
                    <div class="px-5 py-4 border-b border-zinc-100 dark:border-zinc-800/80 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-900">Target Terdekat</h3>
                        <a href="{{ url('/hafalan-targets') }}" class="text-sm text-emerald-700 hover:underline">Kelola</a>
                    </div>
                    <div class="divide-y">
                        @forelse ($latestTargets as $target)
                            <div class="px-5 py-4">
                                <div class="flex justify-between gap-4">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $target->student?->name ?? '-' }}</p>
                                        <p class="text-sm text-gray-600">{{ $target->surah?->name_latin ?? '-' }} ayat {{ $target->ayah_range }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium">{{ $target->target_date?->format('d M Y') }}</p>
                                        <p class="text-xs {{ $target->is_overdue ? 'text-red-600' : 'text-gray-500' }}">{{ $target->status_label }}</p>
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
                        <h3 class="font-semibold text-gray-900">Setoran Terbaru</h3>
                    </div>
                    <div class="divide-y">
                        @forelse ($latestHafalanRecords as $record)
                            <div class="px-5 py-4">
                                <p class="font-medium text-gray-900">{{ $record->student?->name ?? '-' }}</p>
                                <p class="text-sm text-gray-600">{{ $record->surah?->name_latin ?? '-' }} ayat {{ $record->ayah_range }}</p>
                                <p class="text-xs text-gray-400">{{ $record->submitted_at?->format('d M Y') }} — {{ $record->status_label }}</p>
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
                            <p class="text-sm text-gray-600">{{ $record->surah?->name_latin ?? '-' }} ayat {{ $record->ayah_range }}</p>
                            <p class="text-xs text-gray-400">{{ $record->reviewed_at?->format('d M Y') }} — {{ $record->status_label }}</p>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-gray-500">Belum ada murajaah.</div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>