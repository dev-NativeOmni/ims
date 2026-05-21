<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $title ?? 'Admin Dashboard' }}
            </h2>
            <p class="text-sm text-gray-500">
                {{ $subtitle ?? 'Monitoring operasional HafizPlus.' }}
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

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-sm p-5 border">
                    <p class="text-sm text-gray-500">Total Santri</p>
                    <p class="text-3xl font-bold text-gray-900">{{ data_get($stats, 'total_students', 0) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Aktif: {{ data_get($stats, 'active_students', 0) }}</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-5 border">
                    <p class="text-sm text-gray-500">Guru</p>
                    <p class="text-3xl font-bold text-gray-900">{{ data_get($stats, 'total_teachers', 0) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Orangtua: {{ data_get($stats, 'total_parents', 0) }}</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-5 border">
                    <p class="text-sm text-gray-500">Setoran Hari Ini</p>
                    <p class="text-3xl font-bold text-gray-900">{{ data_get($stats, 'hafalan_today', 0) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Murajaah: {{ data_get($stats, 'murajaah_today', 0) }}</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-5 border">
                    <p class="text-sm text-gray-500">Target Aktif</p>
                    <p class="text-3xl font-bold text-gray-900">{{ data_get($stats, 'active_targets', 0) }}</p>
                    <p class="text-xs text-red-500 mt-1">Terlambat: {{ data_get($stats, 'overdue_targets', 0) }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <a href="{{ url('/students') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl px-5 py-4 shadow-sm">
                    <p class="font-semibold">Kelola Santri</p>
                    <p class="text-sm text-emerald-100">Data santri, kelas, guru, dan orangtua.</p>
                </a>

                <a href="{{ url('/hafalan-targets') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-5 py-4 shadow-sm">
                    <p class="font-semibold">Target Hafalan</p>
                    <p class="text-sm text-indigo-100">Pantau target aktif, selesai, dan terlambat.</p>
                </a>

                <a href="{{ url('/reports') }}" class="bg-gray-900 hover:bg-black text-white rounded-xl px-5 py-4 shadow-sm">
                    <p class="font-semibold">Laporan</p>
                    <p class="text-sm text-gray-300">Filter dan export progres HafizPlus.</p>
                </a>
            </div>

            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-4 border-b flex items-center justify-between">
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
                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="px-5 py-4 border-b">
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

                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="px-5 py-4 border-b">
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

            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-4 border-b">
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