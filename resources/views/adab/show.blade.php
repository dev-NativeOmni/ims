<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
            {{ __('Laporan Perkembangan Adab') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 rounded-lg text-emerald-800 dark:text-emerald-300 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800 rounded-lg text-rose-800 dark:text-rose-300 text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Header Profil Santri -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <span class="text-xs font-semibold text-indigo-500 dark:text-indigo-400 uppercase tracking-wider block mb-1">Rincian Perkembangan Adab & Karakter</span>
                    <h3 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $student->name }}</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                        Kelas: {{ $student->classRoom?->name ?: '-' }} | NIS: {{ $student->student_number ?: '-' }} | Guru Pembimbing: {{ $student->teacher?->user?->name ?: '-' }}
                    </p>
                </div>
                <div class="flex gap-2">
                    @if (!auth()->user()->hasRole('student'))
                        <a href="{{ route('adab.index') }}" class="inline-flex items-center px-4 py-2 border border-zinc-300 dark:border-zinc-700 text-sm font-semibold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition duration-150">
                            Kembali ke Daftar
                        </a>
                    @endif
                    
                    @php
                        $today = now()->toDateString();
                        $alreadyFilledToday = \App\Models\AdabRecord::where('student_id', $student->id)->where('assessment_date', $today)->exists();
                        $isOwn = auth()->user()->hasRole('student') && $student->user_id === auth()->id();
                        $isManager = auth()->user()->hasAnyRole(['super_admin', 'admin', 'supervisor']);
                    @endphp

                    @if (($isOwn || $isManager) && !$alreadyFilledToday)
                        <a href="{{ route('adab.create', $student) }}" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition duration-150 shadow-sm">
                            Isi Kuisioner Hari Ini
                        </a>
                    @endif
                </div>
            </div>

            @if ($totalAverage > 0)
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Kiri: Card Score Global -->
                    <div class="bg-gradient-to-br from-teal-600 via-indigo-700 to-indigo-900 text-white rounded-xl shadow-lg p-6 flex flex-col justify-between relative overflow-hidden">
                        <div class="absolute -right-6 -bottom-6 opacity-10">
                            <svg class="h-40 w-40" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H7c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.04-.42 1.99-1.07 2.75z"/>
                            </svg>
                        </div>

                        <div class="relative z-10">
                            <h4 class="text-sm font-semibold uppercase text-teal-200 tracking-wider">Rata-rata Nilai Adab Kumulatif</h4>
                            <div class="flex items-baseline gap-2 mt-4">
                                <span class="text-6xl font-black tracking-tight">{{ $totalAverage }}</span>
                                <span class="text-xl text-teal-200">/ 100 Poin</span>
                            </div>
                        </div>

                        @php
                            $category = '-';
                            $desc = '';
                            if ($totalAverage >= 85) {
                                $category = 'Mumtaz (Sangat Baik)';
                                $desc = 'Membiasakan akhlak mulia dan adab islami sehari-hari secara istiqamah.';
                            } elseif ($totalAverage >= 70) {
                                $category = 'Jayyid (Baik)';
                                $desc = 'Menerapkan adab islami dengan baik, pertahankan dan tingkatkan kebiasaan baik Anda.';
                            } elseif ($totalAverage >= 55) {
                                $category = 'Maqbul (Cukup)';
                                $desc = 'Adab cukup baik, disarankan untuk lebih tertib dalam beribadah dan disiplin harian.';
                            } else {
                                $category = 'Dhaif (Kurang)';
                                $desc = 'Memerlukan bimbingan moral intensif dan perhatian khusus dari pembina keagamaan.';
                            }
                        @endphp

                        <div class="mt-6 relative z-10 border-t border-teal-500/30 pt-4">
                            <h5 class="text-lg font-bold text-emerald-300">{{ $category }}</h5>
                            <p class="text-xs text-teal-100 mt-1 leading-relaxed">{{ $desc }}</p>
                        </div>
                    </div>

                    <!-- Kanan: Card Averages per Category -->
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-6 lg:col-span-2">
                        <h4 class="text-sm font-bold text-zinc-800 dark:text-zinc-200 uppercase tracking-wider mb-6">Konsistensi Berdasarkan Kategori</h4>
                        
                        @php
                            $allahAvg = 0; $rasulullahAvg = 0; $belajarAvg = 0; $mentorAvg = 0;
                            
                            if (count($averages) > 0) {
                                $allahAvg = round((($averages['q1'] + $averages['q2'] + $averages['q3'] + $averages['q4'] + $averages['q5']) / 5) * 100);
                                $rasulullahAvg = round((($averages['q6'] + $averages['q7'] + $averages['q8'] + $averages['q9'] + $averages['q10']) / 5) * 100);
                                $belajarAvg = round((($averages['q11'] + $averages['q12'] + $averages['q13'] + $averages['q14'] + $averages['q15']) / 5) * 100);
                                $mentorAvg = round($mentorAverage);
                            }
                            
                            $cats = [
                                ['label' => '🕋 Adab Kepada Allah', 'val' => $allahAvg, 'color' => 'bg-emerald-500', 'desc' => 'Shalat tepat waktu, doa & dzikir.'],
                                ['label' => '💚 Adab Kepada Rasulullah', 'val' => $rasulullahAvg, 'color' => 'bg-teal-500', 'desc' => 'Shalawat & sunnah harian.'],
                                ['label' => '📚 Adab Belajar', 'val' => $belajarAvg, 'color' => 'bg-indigo-500', 'desc' => 'Ketertiban, menyimak khusyuk, & kerapian.'],
                                ['label' => '👥 Penilaian Pendamping', 'val' => $mentorAvg, 'color' => 'bg-purple-500', 'desc' => 'Evaluasi rata-rata dari pendamping adab.'],
                            ];
                        @endphp

                        <div class="space-y-6">
                            @foreach ($cats as $c)
                                <div class="space-y-1">
                                    <div class="flex justify-between items-baseline">
                                        <div>
                                            <span class="text-sm font-bold text-zinc-800 dark:text-zinc-200">{{ $c['label'] }}</span>
                                            <span class="text-[10px] text-zinc-400 dark:text-zinc-550 ml-2">({{ $c['desc'] }})</span>
                                        </div>
                                        <span class="text-sm font-black text-zinc-900 dark:text-white">
                                            {{ $c['val'] }}{{ $c['label'] === '👥 Penilaian Pendamping' ? '' : '%' }}
                                            @if($c['label'] !== '👥 Penilaian Pendamping')
                                                <span class="text-[10px] font-normal text-zinc-400">Konsistensi "Ya"</span>
                                            @else
                                                <span class="text-[10px] font-normal text-zinc-400">Poin Rata-rata</span>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="w-full bg-zinc-100 dark:bg-zinc-800 rounded-full h-3 overflow-hidden">
                                        <div class="{{ $c['color'] }} h-full rounded-full transition-all duration-500" style="width: {{ $c['val'] }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Riwayat Penilaian -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-6">
                    <h4 class="text-sm font-bold text-zinc-800 dark:text-zinc-200 uppercase tracking-wider mb-6">Riwayat Jawaban Kuisioner Harian</h4>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800 text-center">
                            <thead>
                                <tr class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                    <th class="px-4 py-3 text-left">Tanggal Pengisian</th>
                                    <th class="px-4 py-3">Skor Mandiri (50%)</th>
                                    <th class="px-4 py-3">Skor Pendamping (50%)</th>
                                    <th class="px-4 py-3">Total Skor</th>
                                    <th class="px-4 py-3 text-left">Refleksi / Catatan</th>
                                    @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'supervisor']))
                                        <th class="px-4 py-3 text-right">Aksi</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800 text-sm">
                                @foreach ($adabRecords as $record)
                                    @php
                                        $yaCount = 0;
                                        for ($i = 1; $i <= 15; $i++) {
                                            if ($record->{"q{$i}"} == 1) {
                                                $yaCount++;
                                            }
                                        }
                                        $studentScore = round(($yaCount / 15) * 50, 1);
                                        $mentorScore = $record->mentor_score;
                                        $hasMentorScore = !is_null($mentorScore);
                                        $canGrade = auth()->user()->hasAnyRole(['super_admin', 'admin', 'supervisor', 'pendamping_adab']);
                                    @endphp
                                    <tr class="hover:bg-zinc-50/50 dark:hover:bg-white/[0.01] transition duration-150">
                                        <td class="px-4 py-3 font-semibold text-zinc-950 dark:text-white text-left">
                                            {{ $record->assessment_date->format('d M Y') }}
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-zinc-900 dark:text-white">
                                            <span class="text-zinc-900 dark:text-white font-bold">{{ $studentScore }}</span>
                                            <span class="text-xs text-zinc-400">/ 50</span>
                                            <span class="text-xs text-zinc-400 block mt-0.5">({{ $yaCount }} Ya, {{ 15 - $yaCount }} Tidak)</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if (!$hasMentorScore)
                                                @if ($canGrade)
                                                    <form method="POST" action="{{ route('adab.store-mentor-score', [$student, $record]) }}" class="flex items-center gap-1.5 justify-center">
                                                        @csrf
                                                        <input type="number" name="mentor_score" min="0" max="100" placeholder="0-100" class="w-16 px-2 py-1 text-xs border rounded-xl dark:bg-zinc-800 dark:border-zinc-700 dark:text-white text-center focus:ring-indigo-500" required />
                                                        <button type="submit" class="px-2.5 py-1 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-500 shadow-sm transition">Kirim</button>
                                                    </form>
                                                @else
                                                    <span class="text-zinc-400 italic text-xs">Belum dinilai</span>
                                                @endif
                                            @else
                                                @if ($canGrade)
                                                    <div x-data="{ editing: false }" class="flex flex-col items-center">
                                                        <div x-show="!editing" class="flex items-center gap-1.5 justify-center">
                                                            <span class="font-bold text-zinc-900 dark:text-white">{{ $mentorScore }}</span>
                                                            <span class="text-xs text-zinc-400">/ 100</span>
                                                            <span class="text-xs text-zinc-400">(B. {{ $mentorScore * 0.5 }})</span>
                                                            <button @click="editing = true" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-semibold ml-1">Ubah</button>
                                                        </div>
                                                        <form x-show="editing" method="POST" action="{{ route('adab.store-mentor-score', [$student, $record]) }}" class="flex items-center gap-1 justify-center" style="display:none">
                                                            @csrf
                                                            <input type="number" name="mentor_score" value="{{ $mentorScore }}" min="0" max="100" class="w-14 px-1.5 py-0.5 text-xs border rounded-lg dark:bg-zinc-800 dark:border-zinc-700 dark:text-white text-center" required />
                                                            <button type="submit" class="px-1.5 py-0.5 text-[10px] font-bold text-white bg-emerald-600 rounded-md hover:bg-emerald-500">Simpan</button>
                                                            <button type="button" @click="editing = false" class="px-1.5 py-0.5 text-[10px] font-bold text-zinc-600 dark:text-zinc-400 bg-zinc-200 dark:bg-zinc-800 rounded-md hover:bg-zinc-350">Batal</button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <span class="font-bold text-zinc-900 dark:text-white">{{ $mentorScore }}</span>
                                                    <span class="text-xs text-zinc-400">/ 100</span>
                                                    <span class="text-xs text-zinc-400 block mt-0.5">(Bobot: {{ $mentorScore * 0.5 }})</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @php
                                                $badgeClass = '';
                                                $categoryText = '';
                                                if ($record->total_score >= 85) {
                                                    $badgeClass = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/30';
                                                    $categoryText = 'Mumtaz';
                                                } elseif ($record->total_score >= 70) {
                                                    $badgeClass = 'bg-teal-50 text-teal-700 dark:bg-teal-950/20 dark:text-teal-400 border border-teal-100 dark:border-teal-900/30';
                                                    $categoryText = 'Jayyid';
                                                } elseif ($record->total_score >= 55) {
                                                    $badgeClass = 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-400 border border-amber-100 dark:border-amber-900/30';
                                                    $categoryText = 'Maqbul';
                                                } else {
                                                    $badgeClass = 'bg-rose-50 text-rose-700 dark:bg-rose-950/20 dark:text-rose-455 border border-rose-100 dark:border-rose-900/30';
                                                    $categoryText = 'Dhaif';
                                                }
                                            @endphp
                                            <div class="flex flex-col items-center gap-1">
                                                <span class="font-black text-base text-zinc-900 dark:text-white">{{ $record->total_score }}</span>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $badgeClass }}">
                                                    {{ $categoryText }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400 text-left max-w-xs truncate" title="{{ $record->notes }}">
                                            {{ $record->notes ?: '-' }}
                                        </td>
                                        @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'supervisor']))
                                            <td class="px-4 py-3 text-right">
                                                <form method="POST" action="{{ route('adab.destroy', $record) }}" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan adab tanggal {{ $record->assessment_date->format('d/m/Y') }} ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs text-red-600 hover:text-red-800 dark:hover:text-red-400 font-semibold bg-transparent border-none cursor-pointer">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($adabRecords->hasPages())
                        <div class="mt-6">
                            {{ $adabRecords->links() }}
                        </div>
                    @endif
                </div>
            @else
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-12 text-center">
                    <svg class="mx-auto h-16 w-16 text-zinc-400 dark:text-zinc-650" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-bold text-zinc-900 dark:text-white">Belum Ada Catatan Harian</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400 max-w-sm mx-auto">
                        Santri ini belum memiliki riwayat pengisian kuisioner adab. Silakan klik tombol di kanan atas untuk mengisi kuisioner hari ini.
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
