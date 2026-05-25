@php
    $student = $student ?? null;
    $motivation = $motivation ?? [];
    $progress = $progress ?? [];
    $showStudentName = $showStudentName ?? true;

    $level = data_get($motivation, 'level', []);
    $badges = collect(data_get($motivation, 'badges', []));
    $nextActions = collect(data_get($motivation, 'next_actions', []));

    $earnedBadges = $badges->where('status', 'earned')->count();
    $attentionBadges = $badges->where('status', 'attention')->count();
    $lockedBadges = $badges->where('status', 'locked')->count();

    $tone = data_get($level, 'tone', 'gray');

    $levelClass = match ($tone) {
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        'blue' => 'border-blue-200 bg-blue-50 text-blue-800',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-800',
        'indigo' => 'border-indigo-200 bg-indigo-50 text-indigo-800',
        default => 'border-gray-200 bg-gray-50 text-gray-800',
    };

    $barClass = match ($tone) {
        'emerald' => 'bg-emerald-600',
        'blue' => 'bg-blue-600',
        'amber' => 'bg-amber-500',
        'indigo' => 'bg-indigo-600',
        default => 'bg-gray-500',
    };

    $progressPercent = (float) data_get(
        $progress,
        'progress_percent',
        data_get($progress, 'progress_percentage', 0)
    );

    $progressWidth = min(100, max(0, $progressPercent));
@endphp

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h3 class="text-base font-semibold text-gray-900">
                @if ($showStudentName && $student)
                    {{ $student->name }}
                @else
                    Badge & Motivasi Hafalan
                @endif
            </h3>

            <p class="mt-1 text-sm text-gray-500">
                @if ($showStudentName && $student)
                    {{ $student->classRoom?->name ?? 'Tanpa kelas' }}

                    @if ($student->teacher?->user)
                        · Guru: {{ $student->teacher->user->name }}
                    @endif
                @else
                    Ringkasan motivasi otomatis berdasarkan progress, target, hafalan, dan murajaah.
                @endif
            </p>
        </div>

        <div class="rounded-xl border px-4 py-3 {{ $levelClass }}">
            <div class="text-sm font-bold">
                {{ data_get($level, 'name', 'Level Belum Ada') }}
            </div>

            <div class="mt-1 max-w-md text-xs leading-5">
                {{ data_get($level, 'description', 'Belum ada data motivasi.') }}
            </div>
        </div>
    </div>

    <div class="mt-5 rounded-xl border border-gray-100 bg-gray-50 p-4">
        <p class="text-sm font-semibold text-gray-900">
            Evaluasi Singkat
        </p>

        <p class="mt-2 text-sm leading-6 text-gray-700">
            {{ data_get($motivation, 'message', 'Belum ada pesan evaluasi.') }}
        </p>
    </div>

    <div class="mt-5">
        <div class="flex items-center justify-between text-sm">
            <span class="text-gray-500">
                Progress Hafalan
            </span>

            <span class="font-semibold text-gray-900">
                {{ number_format($progressPercent, 2) }}%
            </span>
        </div>

        <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
            <div class="h-2.5 rounded-full {{ $barClass }}"
                 style="width: {{ $progressWidth }}%">
            </div>
        </div>
    </div>

    <div class="mt-5 grid grid-cols-3 gap-3 text-sm">
        <div class="rounded-lg bg-gray-50 p-3">
            <p class="text-gray-500">
                Tercapai
            </p>

            <p class="mt-1 text-2xl font-bold text-emerald-700">
                {{ $earnedBadges }}
            </p>
        </div>

        <div class="rounded-lg bg-gray-50 p-3">
            <p class="text-gray-500">
                Perhatian
            </p>

            <p class="mt-1 text-2xl font-bold text-red-600">
                {{ $attentionBadges }}
            </p>
        </div>

        <div class="rounded-lg bg-gray-50 p-3">
            <p class="text-gray-500">
                Belum
            </p>

            <p class="mt-1 text-2xl font-bold text-gray-700">
                {{ $lockedBadges }}
            </p>
        </div>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-3 md:grid-cols-2">
        @forelse ($badges->take(6) as $badge)
            @php
                $status = $badge['status'] ?? 'locked';

                $badgeClass = match ($status) {
                    'earned' => 'border-emerald-200 bg-emerald-50',
                    'attention' => 'border-red-200 bg-red-50',
                    default => 'border-gray-200 bg-white',
                };

                $titleClass = match ($status) {
                    'earned' => 'text-emerald-800',
                    'attention' => 'text-red-800',
                    default => 'text-gray-800',
                };

                $label = match ($status) {
                    'earned' => 'Tercapai',
                    'attention' => 'Perhatian',
                    default => 'Belum',
                };

                $labelClass = match ($status) {
                    'earned' => 'bg-emerald-600 text-white',
                    'attention' => 'bg-red-600 text-white',
                    default => 'bg-gray-200 text-gray-700',
                };
            @endphp

            <div class="rounded-xl border p-4 {{ $badgeClass }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold {{ $titleClass }}">
                            {{ $badge['title'] ?? '-' }}
                        </p>

                        <p class="mt-1 text-xs leading-5 text-gray-600">
                            {{ $badge['description'] ?? '-' }}
                        </p>
                    </div>

                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $labelClass }}">
                        {{ $label }}
                    </span>
                </div>

                <p class="mt-3 text-xs font-semibold text-gray-500">
                    {{ $badge['value'] ?? '-' }}
                </p>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-500 md:col-span-2">
                Belum ada badge yang bisa ditampilkan.
            </div>
        @endforelse
    </div>

    <div class="mt-5 border-t border-gray-100 pt-4">
        <h4 class="text-sm font-semibold text-gray-900">
            Prioritas Berikutnya
        </h4>

        <div class="mt-3 space-y-3">
            @forelse ($nextActions as $action)
                @php
                    $priority = $action['priority'] ?? 'low';

                    $priorityClass = match ($priority) {
                        'high' => 'bg-red-100 text-red-700',
                        'medium' => 'bg-amber-100 text-amber-700',
                        default => 'bg-gray-100 text-gray-700',
                    };

                    $priorityLabel = match ($priority) {
                        'high' => 'Tinggi',
                        'medium' => 'Sedang',
                        default => 'Normal',
                    };
                @endphp

                <div class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-sm font-semibold text-gray-900">
                            {{ $action['title'] ?? '-' }}
                        </p>

                        <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $priorityClass }}">
                            {{ $priorityLabel }}
                        </span>
                    </div>

                    <p class="mt-1 text-xs leading-5 text-gray-600">
                        {{ $action['description'] ?? '-' }}
                    </p>
                </div>
            @empty
                <p class="text-sm text-gray-500">
                    Belum ada prioritas.
                </p>
            @endforelse
        </div>
    </div>
</div>