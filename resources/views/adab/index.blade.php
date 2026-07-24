<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
            {{ __('Penilaian Adab & Akhlak') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ tab: 'list' }">
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
                        Evaluasi kedisiplinan dan pembiasaan adab islami harian santri. Penilaian mencakup 3 modul mandiri santri (adab kepada Allah, adab kepada Rasulullah, adab belajar) dengan bobot 50% dan penilaian pendamping adab dengan bobot 50%.
                    </p>
                </div>
            </div>

            <!-- Tab Controls -->
            <div class="flex border-b border-zinc-200 dark:border-zinc-800 gap-6 no-print">
                <button 
                    @click="tab = 'list'"
                    :class="tab === 'list' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-zinc-400' "
                    class="py-3 px-1 border-b-2 text-sm transition-all focus:outline-none"
                >
                    Daftar Evaluasi Santri
                </button>
                <button 
                    @click="tab = 'dashboard'"
                    :class="tab === 'dashboard' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-zinc-400' "
                    class="py-3 px-1 border-b-2 text-sm transition-all focus:outline-none"
                >
                    Dashboard Kepatuhan Adab
                </button>
            </div>

            <!-- List Tab Content -->
            <div x-show="tab === 'list'" x-transition class="space-y-6">
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
                                            <div class="text-xs text-zinc-400 dark:text-zinc-550 mt-0.5">
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
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400 border border-rose-100 dark:border-rose-900/30">
                                                    Belum Mengisi
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4 text-center">
                                            @if ($student->average_adab_score > 0)
                                                @php
                                                    $score = $student->average_adab_score;
                                                    $grade = $student->adab_grade;
                                                    $badgeColor = match($grade) {
                                                        'A' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30',
                                                        'B' => 'bg-teal-50 text-teal-700 dark:bg-teal-950/20 dark:text-teal-400 border border-teal-100 dark:border-teal-900/30',
                                                        'C' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border border-amber-100 dark:border-amber-900/30',
                                                        'D' => 'bg-orange-50 text-orange-700 dark:bg-orange-950/20 dark:text-orange-400 border border-orange-100 dark:border-orange-900/30',
                                                        default => 'bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-400 border border-rose-100 dark:border-rose-900/30',
                                                    };
                                                @endphp
                                                <div class="flex items-center justify-center gap-2">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-bold {{ $badgeColor }}">
                                                        {{ round($score) }}
                                                    </span>
                                                    <span class="inline-flex items-center justify-center h-7 w-7 rounded-full text-sm font-black {{ $badgeColor }}">
                                                        {{ $grade }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-xs text-zinc-400 dark:text-zinc-650 italic">Belum ada data</span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4 text-right space-x-1">
                                            <a href="{{ route('adab.show', $student) }}" class="inline-flex items-center px-3 py-1.5 border border-zinc-300 dark:border-zinc-700 text-xs font-semibold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 rounded-md hover:bg-zinc-50 dark:hover:bg-zinc-700 transition duration-150">
                                                Riwayat & Rincian
                                            </a>

                                            @if ($isAdmin || $isSupervisor || Auth::user()->hasRole('teacher') || Auth::user()->hasRole('pendamping_adab'))
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

            <!-- Dashboard Visual Tab Content -->
            <div x-show="tab === 'dashboard'" x-transition class="space-y-6">
                <!-- Compliance per Aspect -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm">
                    <h4 class="text-base font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-1.5 border-b pb-2">
                        📊 Persentase Kepatuhan Berdasarkan Kategori Adab
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        @foreach ($categories as $catIdx => $cat)
                            @php
                                $pct   = $catStats[$catIdx] ?? 0;
                                $grade = \App\Models\Setting::getAdabGrade($pct);
                                $colors = [
                                    'A' => ['bar' => 'bg-emerald-500', 'text' => 'text-emerald-600 dark:text-emerald-400'],
                                    'B' => ['bar' => 'bg-teal-500',    'text' => 'text-teal-600 dark:text-teal-400'],
                                    'C' => ['bar' => 'bg-amber-500',   'text' => 'text-amber-600 dark:text-amber-400'],
                                    'D' => ['bar' => 'bg-orange-500',  'text' => 'text-orange-600 dark:text-orange-400'],
                                    'E' => ['bar' => 'bg-rose-500',    'text' => 'text-rose-600 dark:text-rose-400'],
                                ][$grade] ?? ['bar' => 'bg-zinc-500', 'text' => 'text-zinc-600 dark:text-zinc-400'];
                            @endphp
                            <div class="bg-zinc-50 dark:bg-zinc-800/40 rounded-xl p-5 border dark:border-zinc-800 flex flex-col justify-between items-center text-center">
                                <span class="text-2xl mb-2">{{ substr($cat['title'], 0, 2) }}</span>
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-550 dark:text-zinc-400 leading-tight">{{ Str::after($cat['title'], ' ') }}</span>
                                <h3 class="text-3xl font-extrabold {{ $colors['text'] }} mt-2">{{ $grade }}</h3>
                                <p class="text-sm font-semibold text-zinc-500 dark:text-zinc-400">{{ $pct }}%</p>
                                <div class="w-full bg-gray-200 dark:bg-zinc-700 h-2 rounded-full overflow-hidden mt-4">
                                    <div class="{{ $colors['bar'] }} h-full rounded-full" style="width: {{ $pct }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Class Rankings -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm lg:col-span-1">
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 border-b pb-2 flex items-center gap-1.5">
                            <span class="text-amber-500">🏆</span> Kelas dengan Adab Terbaik
                        </h4>
                        @if($classRankings->isEmpty())
                            <p class="text-xs text-gray-500 dark:text-zinc-500 text-center py-6">Belum ada kelas yang terdata.</p>
                        @else
                            <div class="space-y-4">
                                @foreach($classRankings as $rank => $class)
                                    <div class="flex items-center justify-between text-sm">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-50 dark:bg-amber-950/20 text-xs font-bold text-amber-700 dark:text-amber-400">
                                                {{ $rank + 1 }}
                                            </span>
                                            <span class="font-medium text-gray-700 dark:text-zinc-250">{{ is_array($class) ? $class['name'] : $class->name }}</span>
                                        </div>
                                        <span class="font-extrabold text-indigo-600 dark:text-indigo-400">{{ round(is_array($class) ? $class['avg_score'] : $class->avg_score, 1) }} / 100</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Instructions and Advice -->
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-6 shadow-sm lg:col-span-2 flex flex-col justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 border-b pb-2">💡 Tips Pembinaan Karakter Santri</h4>
                            <ul class="space-y-2 text-xs text-gray-600 dark:text-zinc-400 leading-relaxed list-disc list-inside">
                                <li><strong>Target Kepatuhan Tinggi:</strong> Santri dengan kepatuhan adab di atas 85% dikategorikan sebagai <span class="text-green-600 dark:text-emerald-400 font-semibold">Mumtaz</span>. Berikan pujian untuk mempertahankan konsistensi.</li>
                                <li><strong>Intervensi Dini:</strong> Jika adab Al-Qur'an memiliki nilai kepatuhan yang rendah, kaji ulang jadwal murojaah harian bersama asatidzah/guru tahfizh.</li>
                                <li><strong>Kolaborasi dengan Orang Tua:</strong> Manfaatkan menu adab untuk mendiskusikan kepatuhan harian santri saat berada di lingkungan rumah bersama orang tua wali.</li>
                            </ul>
                        </div>
                        <div class="mt-6 p-4 bg-teal-50/50 dark:bg-teal-950/10 rounded-xl border border-teal-100 dark:border-teal-900/30 text-xs text-teal-800 dark:text-teal-400">
                            <strong>Statistik Real-time:</strong> Data di atas diperoleh secara langsung dari rangkuman kuisioner adab harian santri aktif yang telah divalidasi.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
