<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
                    Riwayat Ujian Tahfizh
                </h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                    Kelola dan pantau riwayat ujian tahfizh seluruh santri.
                </p>
            </div>

            <a
                href="{{ route('tahfizh-exams.create') }}"
                class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest transition-all duration-150 shadow-sm hover:shadow-md"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Mulai Ujian Baru
            </a>
        </div>
    </x-slot>

    <div class="space-y-6">
        @if (session('success'))
            <div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-emerald-800 dark:text-emerald-300 text-sm flex items-center gap-2">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-300 text-sm flex items-center gap-2">
                <svg class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ session('error') }}
            </div>
        @endif

        <!-- Filter Panel -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-5">
            <form method="GET" action="{{ route('tahfizh-exams.index') }}" class="flex flex-wrap items-end gap-3">
                <!-- Kelas -->
                <div class="flex-1 min-w-[140px]">
                    <label class="block text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-1.5">Kelas</label>
                    <select name="class_room_id" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="">Semua Kelas</option>
                        @foreach ($classRooms as $class)
                            <option value="{{ $class->id }}" @selected((string) request('class_room_id') === (string) $class->id)>
                                {{ $class->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Santri -->
                <div class="flex-1 min-w-[140px]">
                    <label class="block text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-1.5">Santri</label>
                    <select name="student_id" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="">Semua Santri</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected((string) request('student_id') === (string) $student->id)>
                                {{ $student->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Juz -->
                <div class="flex-1 min-w-[110px]">
                    <label class="block text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-1.5">Juz</label>
                    <select name="juz" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="">Semua Juz</option>
                        @for ($j = 1; $j <= 30; $j++)
                            <option value="{{ $j }}" @selected((string) request('juz') === (string) $j)>
                                Juz {{ $j }}
                            </option>
                        @endfor
                    </select>
                </div>

                <!-- Surah -->
                <div class="flex-1 min-w-[140px]">
                    <label class="block text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-1.5">Surah</label>
                    <select name="surah_id" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white text-sm focus:ring-indigo-500 focus:border-indigo-500 transition">
                        <option value="">Semua Surah</option>
                        @foreach ($surahs as $surah)
                            <option value="{{ $surah->id }}" @selected((string) request('surah_id') === (string) $surah->id)>
                                {{ $surah->number }}. {{ $surah->name_latin }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2 shrink-0">
                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 rounded-lg text-xs font-semibold text-white uppercase tracking-wider shadow-sm transition-all duration-150">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        Filter
                    </button>
                    <a href="{{ route('tahfizh-exams.index') }}" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg text-xs font-semibold text-zinc-700 dark:text-zinc-300 uppercase tracking-wider border border-zinc-200 dark:border-zinc-700 transition-all duration-150">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H17" />
                        </svg>
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- List Table -->
        <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                        <tr class="text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                            <th class="px-5 py-3.5">Tanggal</th>
                            <th class="px-5 py-3.5">Santri</th>
                            <th class="px-5 py-3.5">Materi Ujian</th>
                            <th class="px-5 py-3.5 text-center">Soal 1-5</th>
                            <th class="px-5 py-3.5 text-center">Nilai Akhir</th>
                            <th class="px-5 py-3.5">Penguji</th>
                            <th class="px-5 py-3.5">Keterangan</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($exams as $exam)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-white/[0.015] transition duration-150">
                                <td class="px-5 py-4 whitespace-nowrap text-zinc-500 dark:text-zinc-400 text-xs">
                                    {{ $exam->exam_date?->format('d/m/Y') }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-zinc-900 dark:text-white text-sm">{{ $exam->student?->name }}</div>
                                    <div class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">
                                        {{ $exam->student?->classRoom?->name ?: '-' }}
                                        @if($exam->juz)
                                            · Juz {{ $exam->juz }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4 font-medium text-zinc-800 dark:text-zinc-200 text-sm">
                                    {{ $exam->exam_range }}
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <div class="flex items-center justify-center gap-1 flex-wrap">
                                        @foreach ([1,2,3,4,5] as $qi)
                                            @php $qval = 'q'.$qi; @endphp
                                            <span class="inline-block bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 rounded-md px-1.5 py-0.5 text-[10px] font-semibold border border-zinc-200 dark:border-zinc-700" title="Pertanyaan {{ $qi }}">
                                                Q{{ $qi }}: {{ $exam->$qval }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-center whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $exam->total_score >= 75 ? 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' : 'bg-red-50 dark:bg-red-950/30 text-red-800 dark:text-red-300 border border-red-200 dark:border-red-800' }}">
                                        {{ round($exam->total_score) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-zinc-600 dark:text-zinc-400 text-sm">
                                    {{ $exam->teacher?->user?->name ?: '-' }}
                                </td>
                                <td class="px-5 py-4 text-zinc-500 dark:text-zinc-400 text-xs max-w-[180px] truncate" title="{{ $exam->notes }}">
                                    {{ $exam->notes ?: '-' }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('tahfizh-exams.edit', $exam->id) }}" class="inline-flex items-center px-2.5 py-1.5 border border-zinc-300 dark:border-zinc-700 text-xs font-semibold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-700 transition duration-150">
                                            Edit
                                        </a>

                                        <form action="{{ route('tahfizh-exams.destroy', $exam->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data ujian ini?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-950/20 rounded-md border border-transparent hover:border-red-100 dark:hover:border-red-900/30 transition duration-150">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-5 py-12 text-center">
                                    <div class="flex flex-col items-center gap-3 text-zinc-400 dark:text-zinc-500">
                                        <svg class="h-10 w-10 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-sm font-medium">Belum ada riwayat ujian tahfizh.</p>
                                        <a href="{{ route('tahfizh-exams.create') }}" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-semibold">Mulai ujian pertama →</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($exams->hasPages())
                <div class="px-5 py-4 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                    {{ $exams->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
