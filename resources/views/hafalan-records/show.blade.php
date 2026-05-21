<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Setoran Hafalan
            </h2>

            <a
                href="{{ route('hafalan-records.edit', $hafalanRecord) }}"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700"
            >
                Edit Setoran
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-900 mb-4">
                    Informasi Setoran
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Tanggal</p>
                        <p class="font-semibold text-gray-900">
                            {{ $hafalanRecord->submitted_at?->format('d M Y') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Santri</p>
                        <p class="font-semibold text-gray-900">
                            {{ $hafalanRecord->student?->name }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Kelas</p>
                        <p class="font-semibold text-gray-900">
                            {{ $hafalanRecord->student?->classRoom?->name ?: '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Guru Pembimbing</p>
                        <p class="font-semibold text-gray-900">
                            {{ $hafalanRecord->teacher?->user?->name ?: '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Surah</p>
                        <p class="font-semibold text-gray-900">
                            {{ $hafalanRecord->surah?->number }}. {{ $hafalanRecord->surah?->name_latin }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Rentang Ayat</p>
                        <p class="font-semibold text-gray-900">
                            {{ $hafalanRecord->ayah_start }} - {{ $hafalanRecord->ayah_end }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Jenis Setoran</p>
                        <p class="font-semibold text-gray-900">
                            {{ $hafalanRecord->submission_type_label }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Nilai</p>
                        <p class="font-semibold text-gray-900">
                            {{ $hafalanRecord->score !== null ? number_format((float) $hafalanRecord->score, 2) : '-' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        <p class="font-semibold text-gray-900">
                            {{ $hafalanRecord->status_label }}
                        </p>
                    </div>
                </div>

                <div class="mt-5">
                    <p class="text-sm text-gray-500">Catatan Guru</p>
                    <p class="text-gray-800 whitespace-pre-line">
                        {{ $hafalanRecord->notes ?: '-' }}
                    </p>
                </div>
            </div>

            <div class="flex justify-end">
                <a href="{{ route('hafalan-records.index') }}" class="text-sm text-gray-600 hover:underline">
                    Kembali ke daftar setoran
                </a>
            </div>
        </div>
    </div>
</x-app-layout>