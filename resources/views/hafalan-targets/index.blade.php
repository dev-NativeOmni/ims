<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Target Hafalan
                </h2>
                <p class="text-sm text-gray-500">
                    Kelola target hafalan santri berdasarkan surah, rentang ayat, dan tanggal target.
                </p>
            </div>

            <a href="{{ route('hafalan-targets.create') }}"
               class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                Tambah Target
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Total Target</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ $summary['total'] }}</p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Aktif</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ $summary['active'] }}</p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Selesai</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ $summary['completed'] }}</p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Terlewat</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">{{ $summary['missed'] }}</p>
                </div>

                <div class="rounded-xl bg-white p-5 shadow-sm border border-gray-100">
                    <p class="text-sm text-gray-500">Lewat Deadline</p>
                    <p class="mt-2 text-2xl font-bold text-red-600">{{ $summary['overdue'] }}</p>
                </div>
            </div>

            <div class="rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                <form method="GET" action="{{ route('hafalan-targets.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4 lg:grid-cols-7">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kelas</label>
                        <select name="class_room_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Semua kelas</option>
                            @foreach ($classRooms as $class)
                                <option value="{{ $class->id }}" @selected((string) request('class_room_id') === (string) $class->id)>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Santri</label>
                        <select name="student_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Semua santri</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" @selected((string) request('student_id') === (string) $student->id)>
                                    {{ $student->name }} — {{ $student->classRoom?->name ?? '-' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Surah</label>
                        <select name="surah_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Semua surah</option>
                            @foreach ($surahs as $surah)
                                <option value="{{ $surah->id }}" @selected((string) request('surah_id') === (string) $surah->id)>
                                    {{ $surah->number }}. {{ $surah->name_latin }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Semua status</option>
                            <option value="active" @selected(request('status') === 'active')>Aktif</option>
                            <option value="completed" @selected(request('status') === 'completed')>Selesai</option>
                            <option value="missed" @selected(request('status') === 'missed')>Terlewat</option>
                            <option value="cancelled" @selected(request('status') === 'cancelled')>Dibatalkan</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Dari Tanggal</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sampai Tanggal</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                               class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit"
                                class="w-full rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                            Filter
                        </button>

                        <a href="{{ route('hafalan-targets.index') }}"
                           class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Santri</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Guru</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Surah</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Ayat</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Target</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600">Status</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-600">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($targets as $target)
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

                                <tr>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-gray-900">{{ $target->student?->name ?? '-' }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ $target->student?->student_number ?? '-' }}
                                            · {{ $target->student?->classRoom?->name ?? '-' }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 text-gray-700">
                                        {{ $target->teacher?->user?->name ?? '-' }}
                                    </td>

                                    <td class="px-4 py-4 text-gray-700">
                                        {{ $target->surah?->number }}. {{ $target->surah?->name_latin }}
                                    </td>

                                    <td class="px-4 py-4 text-gray-700">
                                        {{ $target->ayah_range }}
                                    </td>

                                    <td class="px-4 py-4">
                                        <div class="text-gray-900">{{ $target->target_date?->format('d M Y') }}</div>
                                        @if ($target->is_overdue)
                                            <div class="text-xs font-semibold text-red-600">Lewat deadline</div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4">
                                        <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ $statusClass }}">
                                            {{ $target->status_label }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-4">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('hafalan-targets.show', $target) }}"
                                               class="btn-action-detail">
                                                Detail
                                            </a>

                                            <a href="{{ route('hafalan-targets.edit', $target) }}"
                                               class="btn-action-edit">
                                                Edit
                                            </a>

                                            @if ($target->status !== 'completed')
                                                <form method="POST" action="{{ route('hafalan-targets.complete', $target) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                            class="btn-action-complete">
                                                        Selesai
                                                    </button>
                                                </form>
                                            @endif

                                            <form method="POST" action="{{ route('hafalan-targets.destroy', $target) }}"
                                                  onsubmit="return confirm('Hapus target hafalan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="btn-action-delete">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                        Belum ada target hafalan sesuai filter.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-6 py-4">
                    {{ $targets->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>