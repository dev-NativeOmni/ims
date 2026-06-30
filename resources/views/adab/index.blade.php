<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
            {{ __('Penilaian Adab & Akhlak') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-emerald-800 dark:text-emerald-300 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Banner / Deskripsi -->
            <div class="bg-gradient-to-r from-teal-500 via-indigo-600 to-indigo-700 text-white rounded-xl shadow-lg p-6 relative overflow-hidden">
                <div class="absolute right-0 bottom-0 opacity-10 transform translate-x-12 translate-y-12">
                    <svg class="h-64 w-64" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H7c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.04-.42 1.99-1.07 2.75z"/>
                    </svg>
                </div>
                <div class="relative z-10 max-w-2xl">
                    <h3 class="text-xl font-bold mb-2">Evaluasi Akhlak & Adab Harian</h3>
                    <p class="text-teal-100 text-sm leading-relaxed">
                        Fitur kuisioner ini diisi mandiri oleh santri setiap hari untuk mengevaluasi adab kepada Allah, Rasulullah, pergaulan, dan Al-Qur'an. Koordinator Keagamaan (Supervisor) memantau progres pengisian di dashboard.
                    </p>
                </div>
            </div>

            <!-- Filter -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm sm:rounded-xl p-6">
                <form method="GET" action="{{ route('adab.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label for="search" class="block text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-2">Cari Santri</label>
                        <input
                            type="text"
                            name="search"
                            id="search"
                            value="{{ request('search') }}"
                            placeholder="Cari nama..."
                            class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600"
                        >
                    </div>

                    <div>
                        <label for="class_room_id" class="block text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-2">Kelas</label>
                        <select name="class_room_id" id="class_room_id" class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white">
                            <option value="" class="dark:bg-zinc-900">Semua Kelas</option>
                            @foreach ($classRooms as $classRoom)
                                <option value="{{ $classRoom->id }}" @selected((string) request('class_room_id') === (string) $classRoom->id) class="dark:bg-zinc-900">
                                    {{ $classRoom->program?->name ? $classRoom->program->name . ' - ' : '' }}{{ $classRoom->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2 flex items-end gap-3">
                        <button type="submit" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold transition duration-150 shadow-sm">
                            Filter Data
                        </button>

                        <a href="{{ route('adab.index') }}" class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-lg text-sm font-semibold transition duration-150">
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Student List Table -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm sm:rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                            <tr class="text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                <th class="px-6 py-4">Nama Santri</th>
                                <th class="px-6 py-4">Kelas</th>
                                <th class="px-6 py-4 text-center">Status Hari Ini ({{ \Carbon\Carbon::parse($today)->format('d M') }})</th>
                                <th class="px-6 py-4 text-center">Rata-rata Nilai</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($students as $student)
                                <tr class="hover:bg-zinc-50/50 dark:hover:bg-white/[0.01] transition duration-150">
                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-zinc-900 dark:text-white">
                                            {{ $student->name }}
                                        </div>
                                        <div class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">
                                            NIS: {{ $student->student_number ?: '-' }} | {{ $student->gender == 'male' ? 'Laki-laki' : 'Perempuan' }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-zinc-100 dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200">
                                            {{ $student->classRoom?->name ?: '-' }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        @if ($student->today_record)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30">
                                                Sudah ({{ $student->today_record->total_score }} Poin)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-455 border border-rose-100 dark:border-rose-900/30">
                                                Belum Mengisi
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        @if ($student->average_adab_score > 0)
                                            @php
                                                $score = $student->average_adab_score;
                                                $badgeColor = 'bg-red-50 text-red-700 dark:bg-red-950/20 dark:text-red-400 border border-red-100 dark:border-red-900/30';
                                                if ($score >= 85) {
                                                    $badgeColor = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30';
                                                } elseif ($score >= 70) {
                                                    $badgeColor = 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border border-amber-100 dark:border-amber-900/30';
                                                }
                                            @endphp
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold {{ $badgeColor }}">
                                                {{ round($score, 1) }}
                                            </span>
                                        @else
                                            <span class="text-xs text-zinc-400 dark:text-zinc-600 italic">Belum ada data</span>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 text-right space-x-1">
                                        <a href="{{ route('adab.show', $student) }}" class="inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-700 text-xs font-semibold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-700 transition duration-150">
                                            Riwayat & Rincian
                                        </a>

                                        @if ($isAdmin || $isSupervisor)
                                            @if (!$student->today_record)
                                                <a href="{{ route('adab.create', $student) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-md transition duration-150">
                                                    Bantu Isi
                                                </a>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-zinc-400 dark:text-zinc-500">
                                        Tidak ada data santri ditemukan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($students->hasPages())
                    <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50">
                        {{ $students->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
