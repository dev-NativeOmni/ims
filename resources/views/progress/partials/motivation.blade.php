@php
    $level = $motivation['level'] ?? [
        'name' => 'Level Belum Ada',
        'tone' => 'gray',
        'description' => 'Belum ada data motivasi.',
    ];

    $levelClass = match ($level['tone'] ?? 'gray') {
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'blue' => 'border-blue-200 bg-blue-50 text-blue-800',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-800',
        'indigo' => 'border-indigo-200 bg-indigo-50 text-indigo-800',
        default => 'border-gray-200 bg-gray-50 text-gray-800',
    };
@endphp

<div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
    <div class="border-b border-gray-200 px-5 py-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-900">
                    Badge & Motivasi Murid
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Badge ini dihitung otomatis dari progress, setoran, murajaah, dan target. Tidak memakai tabel baru.
                </p>
            </div>

            <div class="rounded-xl border px-4 py-3 {{ $levelClass }}">
                <div class="text-sm font-bold">
                    {{ $level['name'] }}
                </div>
                <div class="mt-1 max-w-md text-xs">
                    {{ $level['description'] }}
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 p-5 xl:grid-cols-3">
        <div class="xl:col-span-2">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                <p class="text-sm font-semibold text-gray-900">
                    Pesan Evaluasi
                </p>
                <p class="mt-2 text-sm leading-6 text-gray-700">
                    {{ $motivation['message'] ?? 'Belum ada pesan evaluasi.' }}
                </p>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach (($motivation['badges'] ?? []) as $badge)
                    @php
                        $badgeClass = match ($badge['status']) {
                            'earned' => 'border-emerald-200 bg-emerald-50',
                            'attention' => 'border-red-200 bg-red-50',
                            default => 'border-gray-200 bg-white',
                        };

                        $titleClass = match ($badge['status']) {
                            'earned' => 'text-emerald-800',
                            'attention' => 'text-red-800',
                            default => 'text-gray-700',
                        };

                        $statusLabel = match ($badge['status']) {
                            'earned' => 'Tercapai',
                            'attention' => 'Perhatian',
                            default => 'Belum',
                        };

                        $statusClass = match ($badge['status']) {
                            'earned' => 'bg-emerald-600 text-white',
                            'attention' => 'bg-red-600 text-white',
                            default => 'bg-gray-200 text-gray-700',
                        };
                    @endphp

                    <div class="rounded-xl border p-4 {{ $badgeClass }}">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-bold {{ $titleClass }}">
                                    {{ $badge['title'] }}
                                </p>
                                <p class="mt-1 text-xs leading-5 text-gray-600">
                                    {{ $badge['description'] }}
                                </p>
                            </div>

                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-bold {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </div>

                        <p class="mt-3 text-xs font-semibold text-gray-500">
                            Progress: {{ $badge['value'] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <div class="rounded-xl border border-gray-200 bg-white p-4">
                <h4 class="text-sm font-semibold text-gray-900">
                    Prioritas Berikutnya
                </h4>

                <div class="mt-4 space-y-3">
                    @foreach (($motivation['next_actions'] ?? []) as $action)
                        @php
                            $priorityClass = match ($action['priority']) {
                                'high' => 'bg-red-100 text-red-700',
                                'medium' => 'bg-amber-100 text-amber-700',
                                default => 'bg-gray-100 text-gray-700',
                            };

                            $priorityLabel = match ($action['priority']) {
                                'high' => 'Tinggi',
                                'medium' => 'Sedang',
                                default => 'Normal',
                            };
                        @endphp

                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ $action['title'] }}
                                </p>

                                <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $priorityClass }}">
                                    {{ $priorityLabel }}
                                </span>
                            </div>

                            <p class="mt-1 text-xs leading-5 text-gray-600">
                                {{ $action['description'] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-sm font-semibold text-amber-900">
                    Catatan
                </p>
                <p class="mt-1 text-xs leading-5 text-amber-800">
                    Ini bukan ranking kompetitif antar murid. Badge hanya alat bantu membaca pola progres, bukan alat untuk mempermalukan murid yang progresnya lambat.
                </p>
            </div>
        </div>
    </div>
</div>