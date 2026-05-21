<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard Orangtua
            </h2>
            <p class="text-sm text-gray-500">
                Pantau progres hafalan, murajaah, dan target anak.
            </p>
        </div>
    </x-slot>

    @php
        $childrenProgress = collect(data_get($stats, 'children_progress', []));
        $latestTargets = collect(data_get($stats, 'latest_targets', []));
        $latestHafalanRecords = collect(data_get($stats, 'latest_hafalan_records', []));
        $latestMurajaahRecords = collect(data_get($stats, 'latest_murajaah_records', []));
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (! data_get($stats, 'parent'))
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-5">
                    Akun orangtua ini belum memiliki profil orangtua. Hubungi admin.
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl shadow-sm p-5 border">
                    <p class="text-sm text-gray-500">Jumlah Anak</p>
                    <p class="text-3xl font-bold text-gray-900">{{ data_get($stats, 'total_children', 0) }}</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-5 border">
                    <p class="text-sm text-gray-500">Target Aktif</p>
                    <p class="text-3xl font-bold text-gray-900">{{ data_get($stats, 'active_targets', 0) }}</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-5 border">
                    <p class="text-sm text-gray-500">Target Terlambat</p>
                    <p class="text-3xl font-bold text-red-600">{{ data_get($stats, 'overdue_targets', 0) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-4 border-b">
                    <h3 class="font-semibold text-gray-900">Progress Anak</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-5 py-3 text-left">Santri</th>
                                <th class="px-5 py-3 text-left">Kelas</th>
                                <th class="px-5 py-3 text-left">Guru</th>
                                <th class="px-5 py-3 text-left">Progress</th>
                                <th class="px-5 py-3 text-left">Target Aktif</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @forelse ($childrenProgress as $item)
                                @php
                                    $student = $item['student'];
                                    $percentage = $item['progress_percentage'] ?? 0;
                                @endphp
                                <tr>
                                    <td class="px-5 py-3 font-medium text-gray-900">{{ $student->name }}</td>
                                    <td class="px-5 py-3 text-gray-600">{{ $student->classRoom?->name ?? '-' }}</td>
                                    <td class="px-5 py-3 text-gray-600">{{ $student->teacher?->user?->name ?? '-' }}</td>
                                    <td class="px-5 py-3">
                                        <div class="w-48 bg-gray-100 rounded-full h-2">
                                            <div class="bg-emerald-600 h-2 rounded-full" style="width: {{ min($percentage, 100) }}%"></div>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ $percentage }}%</span>
                                    </td>
                                    <td class="px-5 py-3">{{ $item['active_target_count'] ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-6 text-center text-gray-500">Belum ada data anak.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-4 border-b">
                    <h3 class="font-semibold text-gray-900">Target Hafalan Anak</h3>
                </div>
                <div class="divide-y">
                    @forelse ($latestTargets as $target)
                        <div class="px-5 py-4">
                            <div class="flex justify-between gap-4">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $target->student?->name ?? '-' }}</p>
                                    <p class="text-sm text-gray-600">{{ $target->surah?->name_latin ?? '-' }} ayat {{ $target->ayah_range }}</p>
                                    <p class="text-xs text-gray-400">Guru: {{ $target->teacher?->user?->name ?? '-' }}</p>
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

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="px-5 py-4 border-b">
                        <h3 class="font-semibold text-gray-900">Riwayat Hafalan</h3>
                    </div>
                    <div class="divide-y">
                        @forelse ($latestHafalanRecords as $record)
                            <div class="px-5 py-4">
                                <p class="font-medium text-gray-900">{{ $record->student?->name ?? '-' }}</p>
                                <p class="text-sm text-gray-600">{{ $record->surah?->name_latin ?? '-' }} ayat {{ $record->ayah_range }}</p>
                                <p class="text-xs text-gray-400">{{ $record->submitted_at?->format('d M Y') }} — {{ $record->status_label }}</p>
                            </div>
                        @empty
                            <div class="px-5 py-6 text-center text-gray-500">Belum ada riwayat hafalan.</div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="px-5 py-4 border-b">
                        <h3 class="font-semibold text-gray-900">Riwayat Murajaah</h3>
                    </div>
                    <div class="divide-y">
                        @forelse ($latestMurajaahRecords as $record)
                            <div class="px-5 py-4">
                                <p class="font-medium text-gray-900">{{ $record->student?->name ?? '-' }}</p>
                                <p class="text-sm text-gray-600">{{ $record->surah?->name_latin ?? '-' }} ayat {{ $record->ayah_range }}</p>
                                <p class="text-xs text-gray-400">{{ $record->reviewed_at?->format('d M Y') }} — {{ $record->status_label }}</p>
                            </div>
                        @empty
                            <div class="px-5 py-6 text-center text-gray-500">Belum ada riwayat murajaah.</div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>