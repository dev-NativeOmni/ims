<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 dark:text-zinc-200 leading-tight">
            {{ __('Kuisioner Harian Adab & Akhlak') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Profil Singkat Santri -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <span class="text-xs font-semibold text-indigo-500 dark:text-indigo-400 uppercase tracking-wider block mb-1">Pengisian Mandiri Santri</span>
                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $student->name }}</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                        Kelas: {{ $student->classRoom?->name ?: '-' }} | NIS: {{ $student->student_number ?: '-' }}
                    </p>
                </div>
                <a href="{{ route('adab.show', $student) }}" class="inline-flex items-center px-4 py-2 border border-zinc-300 dark:border-zinc-700 text-sm font-semibold text-zinc-750 dark:text-zinc-300 bg-white dark:bg-zinc-800 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition duration-150">
                    Kembali ke Riwayat
                </a>
            </div>

            <!-- Form Penilaian -->
            <form method="POST" action="{{ route('adab.store', $student) }}" class="space-y-6" id="adabForm">
                @csrf

                <!-- Tanggal & Real-Time Tracker -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-6 md:col-span-1 flex flex-col justify-between">
                        <div>
                            <span class="block text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-2">Tanggal Pengisian</span>
                            <div class="text-base font-bold text-zinc-800 dark:text-zinc-200 bg-zinc-55 dark:bg-zinc-800/60 p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700">
                                {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}
                            </div>
                        </div>
                        <div class="mt-6 p-4 bg-zinc-50 dark:bg-zinc-800/40 rounded-lg text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed border border-zinc-150 dark:border-zinc-800">
                            <strong>Petunjuk:</strong> Jawablah seluruh pertanyaan kejujuran ini dengan <strong>Ya</strong> atau <strong>Tidak</strong>. Setiap jawaban <strong>Ya</strong> bernilai <strong>5 poin</strong>. Dapatkan nilai sempurna <strong>100 poin</strong> dengan membiasakan seluruh adab mulia ini setiap harinya.
                        </div>
                    </div>

                    <!-- Visual Score Widget -->
                    <div class="bg-gradient-to-br from-indigo-900 to-purple-900 text-white rounded-xl shadow-lg p-6 md:col-span-2 flex flex-col justify-between relative overflow-hidden">
                        <div class="absolute -right-6 -bottom-6 opacity-10">
                            <svg class="h-40 w-40" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H7c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.04-.42 1.99-1.07 2.75z"/>
                            </svg>
                        </div>
                        <div class="relative z-10">
                            <h4 class="text-sm font-semibold uppercase text-indigo-200 tracking-wider">Perkiraan Skor Hari Ini</h4>
                            <div class="flex items-baseline gap-2 mt-4">
                                <span class="text-6xl font-black tracking-tight" id="liveScore">0</span>
                                <span class="text-xl text-indigo-300">/ 100 Poin</span>
                            </div>
                        </div>
                        <div class="mt-6 relative z-10">
                            <!-- Progress Bar -->
                            <div class="w-full bg-indigo-950/50 rounded-full h-3 border border-indigo-800">
                                <div class="bg-gradient-to-r from-emerald-400 to-teal-400 h-full rounded-full transition-all duration-300" id="progressBar" style="width: 0%"></div>
                            </div>
                            <div class="flex justify-between items-center text-xs text-indigo-200 mt-2">
                                <span id="scoreCategory">Kategori: -</span>
                                <span id="filledCount">0 dari 20 pertanyaan terjawab</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Soal Kuisioner per Kategori -->
                @php
                    $categories = [
                        [
                            'title' => '🕋 Adab Kepada Allah',
                            'desc' => 'Menjaga hubungan ketakwaan dan ibadah sehari-hari kepada Allah Subhanahu wa Ta\'ala.',
                            'questions' => [
                                'q1' => 'Apakah Anda melaksanakan shalat fardhu tepat waktu hari ini?',
                                'q2' => 'Apakah Anda mengawali aktivitas hari ini dengan membaca Basmalah?',
                                'q3' => 'Apakah Anda selalu berdoa setelah selesai shalat fardhu hari ini?',
                                'q4' => 'Apakah Anda bersyukur atas segala nikmat yang Anda rasakan hari ini?',
                                'q5' => 'Apakah Anda menyempatkan diri berdzikir (membaca tasbih/tahmid/takbir) hari ini?'
                            ]
                        ],
                        [
                            'title' => '💚 Adab Kepada Rasulullah',
                            'desc' => 'Menghidupkan kecintaan dan amalan sunnah sesuai ajaran Nabi Muhammad Shallallahu \'Alaihi wa Sallam.',
                            'questions' => [
                                'q6' => 'Apakah Anda membaca shalawat kepada Nabi Muhammad hari ini?',
                                'q7' => 'Apakah Anda berusaha menjalankan sunnah Nabi (seperti makan/minum dengan duduk dan tangan kanan) hari ini?',
                                'q8' => 'Apakah Anda menyempatkan diri membaca doa/dzikir pagi atau petang hari ini?',
                                'q9' => 'Apakah Anda membaca doa harian (sebelum/sesudah tidur, makan, atau masuk kamar mandi) hari ini?',
                                'q10' => 'Apakah Anda mendengarkan, membaca, atau merenungkan hadits Rasulullah hari ini?'
                            ]
                        ],
                        [
                            'title' => '🤝 Adab Pergaulan (Akhlak Sosial)',
                            'desc' => 'Menjaga tutur kata dan sikap santun dalam bersosialisasi dengan teman, guru, dan lingkungan.',
                            'questions' => [
                                'q11' => 'Apakah Anda berbicara dan bertutur kata sopan kepada sesama hari ini?',
                                'q12' => 'Apakah Anda menjauhi perkataan kasar, mencela, merundung (bully), atau berbohong hari ini?',
                                'q13' => 'Apakah Anda menghormati orang yang lebih tua (guru/orang tua) dan menyayangi yang lebih muda hari ini?',
                                'q14' => 'Apakah Anda bersedia membantu teman yang membutuhkan bantuan hari ini?',
                                'q15' => 'Apakah Anda mengucapkan salam ketika bertemu atau berpapasan dengan teman/guru hari ini?'
                            ]
                        ],
                        [
                            'title' => '📖 Adab Kepada Al-Qur\'an',
                            'desc' => 'Menghormati kesucian Al-Qur\'an dan istiqamah dalam berinteraksi dengan Kalamullah.',
                            'questions' => [
                                'q16' => 'Apakah Anda berwudhu terlebih dahulu sebelum menyentuh mushaf Al-Qur\'an hari ini?',
                                'q17' => 'Apakah Anda membaca Al-Qur\'an dengan tartil, tenang, dan tidak terburu-buru hari ini?',
                                'q18' => 'Apakah Anda meletakkan mushaf Al-Qur\'an di tempat yang tinggi, aman, dan bersih hari ini?',
                                'q19' => 'Apakah Anda mendengarkan lantunan ayat Al-Qur\'an dengan khusyuk hari ini?',
                                'q20' => 'Apakah Anda disiplin melaksanakan murojaah hafalan Al-Qur\'an hari ini?'
                            ]
                        ],
                    ];
                @endphp

                @foreach ($categories as $catIdx => $category)
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-6 space-y-6">
                        <div>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white">{{ $category['title'] }}</h3>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">{{ $category['desc'] }}</p>
                        </div>

                        <div class="space-y-4">
                            @foreach ($category['questions'] as $key => $questionText)
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between py-3 border-b border-zinc-100 dark:border-zinc-800 last:border-none gap-3">
                                    <div class="flex items-start gap-3 flex-1">
                                        <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-indigo-50 dark:bg-indigo-950/40 text-[10px] font-bold text-indigo-600 dark:text-indigo-400 mt-0.5">
                                            {{ substr($key, 1) }}
                                        </span>
                                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 leading-relaxed">{{ $questionText }}</p>
                                    </div>

                                    <!-- Opsi Ya / Tidak (Radio buttons) -->
                                    <div class="flex items-center gap-2 self-end sm:self-center shrink-0">
                                        <!-- Opsi Ya -->
                                        <label class="flex items-center justify-center px-4 py-1.5 border rounded-lg cursor-pointer text-xs font-bold transition select-none border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:border-emerald-300 dark:hover:border-emerald-900/40" data-label-type="ya">
                                            <input 
                                                type="radio" 
                                                name="{{ $key }}" 
                                                value="1" 
                                                class="sr-only adab-radio"
                                                data-question="{{ $key }}"
                                                required
                                            >
                                            Ya
                                        </label>

                                        <!-- Opsi Tidak -->
                                        <label class="flex items-center justify-center px-4 py-1.5 border rounded-lg cursor-pointer text-xs font-bold transition select-none border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:border-rose-300 dark:hover:border-rose-900/40" data-label-type="tidak">
                                            <input 
                                                type="radio" 
                                                name="{{ $key }}" 
                                                value="0" 
                                                class="sr-only adab-radio"
                                                data-question="{{ $key }}"
                                                required
                                            >
                                            Tidak
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <!-- Catatan Evaluator / Tambahan (Opsional) -->
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm rounded-xl p-6">
                    <label for="notes" class="block text-xs font-semibold uppercase text-zinc-400 dark:text-zinc-500 mb-2">Catatan Harian / Refleksi Diri (Opsional)</label>
                    <textarea 
                        name="notes" 
                        id="notes" 
                        rows="3" 
                        placeholder="Tuliskan refleksi singkat Anda hari ini..."
                        class="w-full rounded-lg border-zinc-300 dark:border-zinc-700 bg-transparent text-sm focus:ring-indigo-500 focus:border-indigo-500 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-600"
                    ></textarea>
                </div>

                <!-- Submit Area -->
                <div class="flex justify-end gap-3">
                    <a href="{{ route('adab.show', $student) }}" class="inline-flex items-center px-5 py-3 border border-zinc-300 dark:border-zinc-700 text-sm font-semibold text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 rounded-xl hover:bg-zinc-50 dark:hover:bg-zinc-700 transition duration-150">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm font-bold shadow-md hover:shadow-lg transition duration-150">
                        Kirim Jawaban Kuisioner
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Script Kalkulasi Real-time -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('adabForm');
            const radios = document.querySelectorAll('.adab-radio');
            const liveScoreEl = document.getElementById('liveScore');
            const progressBar = document.getElementById('progressBar');
            const scoreCategoryEl = document.getElementById('scoreCategory');
            const filledCountEl = document.getElementById('filledCount');

            function updateCalculations() {
                let filledQuestions = new Set();
                let totalScore = 0;

                radios.forEach(radio => {
                    const label = radio.closest('label');
                    const val = parseInt(radio.value);
                    const q = radio.getAttribute('data-question');
                    const labelType = label.getAttribute('data-label-type');

                    if (radio.checked) {
                        filledQuestions.add(q);
                        if (val === 1) {
                            totalScore += 5; // 20 * 5 = 100 max
                        }

                        // Apply checked styling based on answer
                        if (labelType === 'ya') {
                            label.className = 'flex items-center justify-center px-4 py-1.5 border rounded-lg cursor-pointer text-xs font-bold transition select-none bg-emerald-500 border-emerald-500 text-white ring-4 ring-emerald-500/10 dark:ring-emerald-500/5';
                        } else {
                            label.className = 'flex items-center justify-center px-4 py-1.5 border rounded-lg cursor-pointer text-xs font-bold transition select-none bg-zinc-500 border-zinc-500 text-white ring-4 ring-zinc-500/10 dark:ring-zinc-500/5';
                        }
                    } else {
                        // Reset label style back to normal
                        label.className = 'flex items-center justify-center px-4 py-1.5 border rounded-lg cursor-pointer text-xs font-bold transition select-none border-zinc-200 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:border-zinc-350 dark:hover:border-zinc-700';
                    }
                });

                // Update UI elements
                liveScoreEl.textContent = totalScore;
                progressBar.style.width = totalScore + '%';
                filledCountEl.textContent = `${filledQuestions.size} dari 20 pertanyaan terjawab`;

                // Update Category string
                let category = '-';
                if (filledQuestions.size === 20) {
                    if (totalScore >= 85) {
                        category = 'Mumtaz (Sangat Baik)';
                    } else if (totalScore >= 70) {
                        category = 'Jayyid (Baik)';
                    } else if (totalScore >= 55) {
                        category = 'Maqbul (Cukup)';
                    } else {
                        category = 'Dhaif (Kurang)';
                    }
                }
                scoreCategoryEl.textContent = `Kategori: ${category}`;
            }

            radios.forEach(radio => {
                radio.addEventListener('change', updateCalculations);
            });

            // Run initial update
            updateCalculations();
        });
    </script>
</x-app-layout>
