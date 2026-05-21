<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Detail Murajaah
            </h2>

            <div class="flex items-center gap-2">
                <a href="{{ route('murajaah-records.edit', $murajaahRecord) }}"
                   class="px-4 py-2 bg-yellow-500 text-white rounded-md text-sm font-semibold">
                    Edit
                </a>

                <a href="{{ route('murajaah-records.index') }}"
                   class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md text-sm font-semibold">
                    Kembali
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Informasi Utama
                    </h3>

                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="font-medium text-gray-500">Santri</dt>
                            <dd class="mt-1 text-gray-900">{{ $murajaahRecord->student?->name }}</dd>
                        </div>

                        <div>
                            <dt class="font-medium text-gray-500">Nomor Santri</dt>
                            <dd class="mt-1 text-gray-900">{{ $murajaahRecord->student?->student_number ?? '-' }}</dd>
                        </div>

                        <div>
                            <dt class="font-medium text-gray-500">Kelas</dt>
                            <dd class="mt-1 text-gray-900">{{ $murajaahRecord->student?->classRoom?->name ?? '-' }}</dd>
                        </div>

                        <div>
                            <dt class="font-medium text-gray-500">Program</dt>
                            <dd class="mt-1 text-gray-900">{{ $murajaahRecord->student?->classRoom?->program?->name ?? '-' }}</dd>
                        </div>

                        <div>
                            <dt class="font-medium text-gray-500">Guru</dt>
                            <dd class="mt-1 text-gray-900">{{ $murajaahRecord->teacher?->user?->name ?? '-' }}</dd>
                        </div>

                        <div>
                            <dt class="font-medium text-gray-500">Tanggal Murajaah</dt>
                            <dd class="mt-1 text-gray-900">{{ $murajaahRecord->reviewed_at?->format('d M Y') }}</dd>
                        </div>

                        <div>
                            <dt class="font-medium text-gray-500">Surah</dt>
                            <dd class="mt-1 text-gray-900">
                                {{ $murajaahRecord->surah?->number }}. {{ $murajaahRecord->surah?->name_latin }}
                            </dd>
                        </div>

                        <div>
                            <dt class="font-medium text-gray-500">Rentang Ayat</dt>
                            <dd class="mt-1 text-gray-900">{{ $murajaahRecord->ayah_range }}</dd>
                        </div>

                        <div>
                            <dt class="font-medium text-gray-500">Status</dt>
                            <dd class="mt-1 text-gray-900">{{ $murajaahRecord->status_label }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Penilaian
                    </h3>

                    <dl class="grid grid-cols-1 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <dt class="font-medium text-gray-500">Kelancaran</dt>
                            <dd class="mt-1 text-gray-900">{{ $murajaahRecord->fluency_score ?? '-' }}</dd>
                        </div>

                        <div>
                            <dt class="font-medium text-gray-500">Tajwid</dt>
                            <dd class="mt-1 text-gray-900">{{ $murajaahRecord->tajwid_score ?? '-' }}</dd>
                        </div>

                        <div>
                            <dt class="font-medium text-gray-500">Makhraj</dt>
                            <dd class="mt-1 text-gray-900">{{ $murajaahRecord->makhraj_score ?? '-' }}</dd>
                        </div>

                        <div>
                            <dt class="font-medium text-gray-500">Keseluruhan</dt>
                            <dd class="mt-1 text-gray-900">{{ $murajaahRecord->overall_score ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Catatan
                    </h3>

                    <p class="text-sm text-gray-700 whitespace-pre-line">
                        {{ $murajaahRecord->notes ?: '-' }}
                    </p>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>