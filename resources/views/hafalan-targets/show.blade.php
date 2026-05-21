<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Detail Target Hafalan
                </h2>
                <p class="text-sm text-gray-500">
                    Detail target, santri, guru pembimbing, dan status penyelesaian.
                </p>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('hafalan-targets.edit', $target) }}"
                   class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                    Edit
                </a>

                <a href="{{ route('hafalan-targets.index') }}"
                   class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @php
                $statusClass = match ($target->status) {
                    'active' => $target->is_overdue
                        ? 'bg-red-50 text-red-700 border-red-200'
                        : 'bg-blue-50 text-blue-700 border-blue-200',
                    'completed' => 'bg-green-50 text-green-700 border-green-200',
                    'missed' => 'bg-orange-50 text-orange-700 border-orange-200',
                    'cancelled' => 'bg-gray-50 text-gray-700 border-gray-200',
                    default => 'bg-gray-50 text-gray-700 border-gray-200',
                };
            @endphp

            <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="text-sm text-gray-500">Santri</div>
                        <div class="mt-1 text-2xl font-bold text-gray-900">
                            {{ $target->student?->name ?? '-' }}
                        </div>
                        <div class="mt-1 text-sm text-gray-500">
                            {{ $target->student?->student_number ?? '-' }}
                            · {{ $target->student?->classRoom?->program?->name ?? '-' }}
                            · {{ $target->student?->classRoom?->name ?? '-' }}
                        </div>
                    </div>

                    <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                        {{ $target->status_label }}
                    </span>
                </div>

                <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="rounded-lg border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Guru Pembimbing</div>
                        <div class="mt-1 font-semibold text-gray-900">
                            {{ $target->teacher?->user?->name ?? '-' }}
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Surah</div>
                        <div class="mt-1 font-semibold text-gray-900">
                            {{ $target->surah?->number }}. {{ $target->surah?->name_latin }}
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Rentang Ayat</div>
                        <div class="mt-1 font-semibold text-gray-900">
                            {{ $target->ayah_range }}
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Tanggal Target</div>
                        <div class="mt-1 font-semibold text-gray-900">
                            {{ $target->target_date?->format('d M Y') }}
                        </div>

                        @if ($target->is_overdue)
                            <div class="mt-1 text-xs font-semibold text-red-600">
                                Target sudah melewati deadline.
                            </div>
                        @endif
                    </div>

                    <div class="rounded-lg border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Selesai Pada</div>
                        <div class="mt-1 font-semibold text-gray-900">
                            {{ $target->completed_at?->format('d M Y H:i') ?? '-' }}
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-100 p-4">
                        <div class="text-sm text-gray-500">Dibuat Pada</div>
                        <div class="mt-1 font-semibold text-gray-900">
                            {{ $target->created_at?->format('d M Y H:i') }}
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-lg border border-gray-100 p-4">
                    <div class="text-sm text-gray-500">Catatan</div>
                    <div class="mt-2 whitespace-pre-line text-sm text-gray-800">
                        {{ $target->notes ?: '-' }}
                    </div>
                </div>

                @if ($target->status !== 'completed')
                    <div class="mt-6 flex justify-end">
                        <form method="POST" action="{{ route('hafalan-targets.complete', $target) }}">
                            @csrf
                            @method('PATCH')

                            <button type="submit"
                                    class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-800">
                                Tandai Selesai
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>