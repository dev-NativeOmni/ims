<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard Santri
            </h2>
            <p class="text-sm text-gray-500">
                Lihat target, progres hafalan, dan riwayat murajaah.
            </p>
        </div>
    </x-slot>

    @php
        $student = data_get($stats, 'student');
        $summary = data_get($stats, 'summary');
        $activeTargets = collect(data_get($stats, 'active_targets', []));
        $overdueTargets = collect(data_get($stats, 'overdue_targets', []));
        $latestHafalanRecords = collect(data_get($stats, 'latest_hafalan_records', []));
        $latestMurajaahRecords = collect(data_get($stats, 'latest_murajaah_records', []));
        $progressPercentage = data_get($summary, 'progress_percentage', 0);
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (! $student)
                <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl p-5">
                    Akun santri ini belum terhubung ke data santri. Hubungi admin.
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border p-6">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900">{{ $student->name }}</h3>
                            <p class="text-sm text-gray-500">
                                {{ $student->classRoom?->name ?? '-' }}
                                @if ($student->classRoom?->program)
                                    · {{ $student->classRoom->program->name }}
                                @endif
                            </p>
                            <p class="text-sm text-gray-500">
                                Guru: {{ $student->teacher?->user?->name ?? '-' }}
                            </p>
                        </div>

                        <div class="w-full lg:w-96">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-600">Progress Hafalan</span>
                                <span class="font-semibold text-gray-900">{{ $progressPercentage }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-3">
                                <div class="bg-emerald-600 h-3 rounded-full" style="width: {{ min($progressPercentage, 100) }}%"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                {{ data_get($summary, 'memorized_ayah_count', 0) }}
                                dari
                                {{ data_get($summary, 'total_ayah_count', 6236) }}
                                ayat tercatat lulus.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-sm p-5 border">
                    <p class="text-sm text-gray-500">Total Setoran</p>
                    <p class="text-3xl font-bold text-gray-900">{{ data_get($summary, 'total_hafalan_records', 0) }}</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-5 border">
                    <p class="text-sm text-gray-500">Total Murajaah</p>
                    <p class="text-3xl font-bold text-gray-900">{{ data_get($summary, 'total_murajaah_records', 0) }}</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-5 border">
                    <p class="text-sm text-gray-500">Target Aktif</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $activeTargets->count() }}</p>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-5 border">
                    <p class="text-sm text-gray-500">Target Terlambat</p>
                    <p class="text-3xl font-bold text-red-600">{{ $overdueTargets->count() }}</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <div class="px-5 py-4 border-b">
                    <h3 class="font-semibold text-gray-900">Target Hafalan Aktif</h3>
                </div>

                <div class="divide-y">
                    @forelse ($activeTargets as $target)
                        <div class="px-5 py-4">
                            <div class="flex justify-between gap-4">
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ $target->surah?->name_latin ?? '-' }} ayat {{ $target->ayah_range }}
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        Guru: {{ $target->teacher?->user?->name ?? '-' }}
                                    </p>
                                    @if ($target->notes)
                                        <p class="text-sm text-gray-500 mt-1">{{ $target->notes }}</p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium">{{ $target->target_date?->format('d M Y') }}</p>
                                    <p class="text-xs {{ $target->is_overdue ? 'text-red-600' : 'text-gray-500' }}">
                                        {{ $target->is_overdue ? 'Terlambat' : $target->status_label }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-center text-gray-500">
                            Belum ada target aktif.
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="px-5 py-4 border-b">
                        <h3 class="font-semibold text-gray-900">Riwayat Hafalan Terbaru</h3>
                    </div>

                    <div class="divide-y">
                        @forelse ($latestHafalanRecords as $record)
                            <div class="px-5 py-4">
                                <p class="font-medium text-gray-900">
                                    {{ $record->surah?->name_latin ?? '-' }} ayat {{ $record->ayah_range }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    {{ $record->submitted_at?->format('d M Y') }} — {{ $record->status_label }}
                                </p>
                                @if ($record->notes)
                                    <p class="text-sm text-gray-500 mt-1">{{ $record->notes }}</p>
                                @endif
                            </div>
                        @empty
                            <div class="px-5 py-6 text-center text-gray-500">Belum ada hafalan.</div>
                        @endforelse
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                    <div class="px-5 py-4 border-b">
                        <h3 class="font-semibold text-gray-900">Riwayat Murajaah Terbaru</h3>
                    </div>

                    <div class="divide-y">
                        @forelse ($latestMurajaahRecords as $record)
                            <div class="px-5 py-4">
                                <p class="font-medium text-gray-900">
                                    {{ $record->surah?->name_latin ?? '-' }} ayat {{ $record->ayah_range }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    {{ $record->reviewed_at?->format('d M Y') }} — {{ $record->status_label }}
                                </p>
                                @if ($record->notes)
                                    <p class="text-sm text-gray-500 mt-1">{{ $record->notes }}</p>
                                @endif
                            </div>
                        @empty
                            <div class="px-5 py-6 text-center text-gray-500">Belum ada murajaah.</div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>