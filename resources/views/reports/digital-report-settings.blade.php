<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="font-bold text-lg sm:text-xl text-zinc-900 dark:text-zinc-100 leading-tight">
                    Pengaturan Rapor Digital & Template Cetak
                </h2>
                <p class="text-xs sm:text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                    Konfigurasi periode, judul kop surat, data pejabat penandatangan, serta cetak massal rapor per kelas.
                </p>
            </div>
            <a href="{{ route('digital-reports.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-100 dark:bg-zinc-800 hover:bg-gray-200 text-gray-700 dark:text-zinc-300 rounded-xl text-xs font-bold transition self-start sm:self-auto">
                <x-heroicon-m-arrow-left class="w-4 h-4 shrink-0" />
                <span>Kembali ke Daftar Rapor</span>
            </a>
        </div>
    </x-slot>

    <div class="py-6 sm:py-8" x-data="{
        academicYear: '{{ $academicYear }}',
        // Periode & titimangsa pratinjau: aturan sama dengan StudentReportController::activePeriod()
        // dan reportDate(), dihitung dari isian tanggal BLP di form (ikut berubah sebelum disimpan).
        blpDates: @js($blpDates),
        periodBlp: @js(\App\Http\Controllers\StudentReportController::PERIOD_BLP),
        periodLabels: @js(\App\Http\Controllers\StudentReportController::REPORT_PERIODS),
        periodDeadline(term) {
            const y = parseInt(this.academicYear, 10);
            const triwulanEnd = { 1: `${y}-09-30`, 2: `${y}-12-31`, 3: `${y + 1}-03-31`, 4: `${y + 1}-06-30` };
            return this.blpDates[this.periodBlp[term]] || triwulanEnd[term];
        },
        get reportPeriod() {
            const d = new Date();
            const today = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
            return [1, 2, 3].find((term) => today <= this.periodDeadline(term)) ?? 4;
        },
        formatDate(value) {
            const [y, m, d] = value.split('-').map(Number);
            return new Date(y, m - 1, d).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        },
        get reportDate() {
            const value = this.blpDates[this.periodBlp[this.reportPeriod]];
            return value ? this.formatDate(value) : '........................';
        },
        showTahfizh: {{ $showTahfizh ? 'true' : 'false' }},
        showAdab: {{ $showAdab ? 'true' : 'false' }},
        showTanse: {{ $showTanse ? 'true' : 'false' }},
        reportMainTitle: '{{ addslashes($reportMainTitle) }}',
        reportSchoolName: '{{ addslashes($reportSchoolName) }}',
        reportCity: '{{ addslashes($reportCity) }}',
        coordTahfizhName: '{{ addslashes($coordTahfizhName) }}',
        coordTahfizhNik: '{{ addslashes($coordTahfizhNik) }}',
        coordKeagamaanName: '{{ addslashes($coordKeagamaanName) }}',
        coordKeagamaanNik: '{{ addslashes($coordKeagamaanNik) }}',
        headmasterTitle: '{{ addslashes($headmasterTitle) }}',
        headmasterName: '{{ addslashes($headmasterName) }}',
        headmasterNik: '{{ addslashes($headmasterNik) }}',
        coordTanseName: '{{ addslashes($coordTanseName) }}',
        coordTanseNik: '{{ addslashes($coordTanseNik) }}',
        // Tanda tangan pejabat: sig = yang tampil di preview, sigSaved = yang tersimpan.
        sig: @js($officialSignatures),
        sigSaved: @js($officialSignatures),
        pickSignature(key, event) {
            const file = event.target.files[0];
            if (! file) { this.sig[key] = this.sigSaved[key]; return; }
            const reader = new FileReader();
            reader.onload = (e) => { this.sig[key] = e.target.result; };
            reader.readAsDataURL(file);
        },
        resetSignature(key, checked) {
            this.sig[key] = checked ? null : this.sigSaved[key];
        }
    }">
        <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-6">

            @if (session('success'))
                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/50 rounded-2xl text-emerald-700 dark:text-emerald-300 text-sm font-semibold flex items-center gap-2 shadow-sm">
                    <x-heroicon-o-check-circle class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 rounded-2xl text-rose-700 dark:text-rose-300 text-sm font-semibold flex items-center gap-2 shadow-sm">
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 shrink-0" />
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            {{-- Main 2-Column Grid: Form on Left, Live Sheet Preview on Right --}}
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                {{-- LEFT COLUMN: Settings Form (7 cols) --}}
                <div class="lg:col-span-7 space-y-6">
                    <form method="POST" action="{{ route('digital-reports.settings.update') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        {{-- 1. Periode & Modul Komponen --}}
                        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 sm:p-6 shadow-sm">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white border-b pb-3 mb-5 dark:border-zinc-800 flex items-center gap-2">
                                <x-heroicon-o-cog-6-tooth class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                                <span>Konfigurasi Periode & Modul Rapor</span>
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="academic_year" class="block text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Tahun Ajaran Aktif</label>
                                    <input type="text" name="academic_year" id="academic_year" x-model="academicYear" required class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-sm text-gray-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500" placeholder="Contoh: 2026/2027">
                                </div>

                                {{-- Otomatis dari tanggal BLP (StudentReportController::activePeriod()), tidak diatur manual. --}}
                                <div>
                                    <span class="block text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Periode Rapor Aktif</span>
                                    <div class="w-full rounded-xl border border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800/50 px-3 py-2">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white" x-text="periodLabels[reportPeriod]">{{ \App\Http\Controllers\StudentReportController::REPORT_PERIODS[$reportPeriod] }}</p>
                                        <p class="text-[11px] text-gray-500 dark:text-zinc-400">
                                            Otomatis dari tanggal BLP &middot; s.d. <span x-text="formatDate(periodDeadline(reportPeriod))">{{ $reportPeriodUntil->locale('id')->translatedFormat('d F Y') }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t pt-4 mt-4 dark:border-zinc-800 space-y-2.5">
                                <label class="block text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider">Modul Rapor yang Ditampilkan</label>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                                    <label class="flex items-center gap-2.5 p-2.5 bg-gray-50 dark:bg-zinc-800/50 rounded-xl border border-gray-200 dark:border-zinc-800 cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-800 transition">
                                        <input type="checkbox" name="report_show_tahfizh" value="1" x-model="showTahfizh" class="rounded text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-xs font-bold text-gray-800 dark:text-zinc-200 inline-flex items-center gap-1"><x-heroicon-o-book-open class="w-3.5 h-3.5 text-teal-600" /> Tahfizh</span>
                                    </label>

                                    <label class="flex items-center gap-2.5 p-2.5 bg-gray-50 dark:bg-zinc-800/50 rounded-xl border border-gray-200 dark:border-zinc-800 cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-800 transition">
                                        <input type="checkbox" name="report_show_adab" value="1" x-model="showAdab" class="rounded text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-xs font-bold text-gray-800 dark:text-zinc-200 inline-flex items-center gap-1"><x-heroicon-o-sparkles class="w-3.5 h-3.5 text-amber-500" /> Adab</span>
                                    </label>

                                    <label class="flex items-center gap-2.5 p-2.5 bg-gray-50 dark:bg-zinc-800/50 rounded-xl border border-gray-200 dark:border-zinc-800 cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-800 transition">
                                        <input type="checkbox" name="report_show_tanse" value="1" x-model="showTanse" class="rounded text-indigo-600 focus:ring-indigo-500">
                                        <span class="text-xs font-bold text-gray-800 dark:text-zinc-200 inline-flex items-center gap-1"><x-heroicon-o-shield-check class="w-3.5 h-3.5 text-rose-500" /> Tanse</span>
                                    </label>
                                </div>
                            </div>

                            {{-- Tanggal BLP: titimangsa rapor, dipilih otomatis menurut triwulan rapor. --}}
                            <div class="border-t pt-4 mt-4 dark:border-zinc-800 space-y-2.5">
                                <label class="block text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider">
                                    Tanggal BLP (Titimangsa Rapor) &middot; Tahun Ajaran <span x-text="academicYear"></span>
                                </label>
                                <p class="text-[11px] text-gray-500 dark:text-zinc-400">
                                    Tanggal BLP menjadi tanggal rapor sekaligus batas periode: periode aktif berpindah ke periode berikutnya setelah tanggal BLP-nya lewat. Bila kosong, tanggal di rapor berupa titik-titik, batas periode memakai akhir triwulan, dan kelas belum bisa dikunci.
                                </p>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @foreach (\App\Http\Controllers\StudentReportController::BLP_EXAMS as $blpSemester => $exams)
                                        <div class="p-3 bg-gray-50 dark:bg-zinc-800/50 rounded-xl border border-gray-200 dark:border-zinc-800 space-y-2">
                                            <p class="text-xs font-bold text-gray-800 dark:text-zinc-200">Semester {{ $blpSemester }}</p>
                                            @foreach ($exams as $key => $examLabel)
                                                <div class="flex items-center gap-2">
                                                    <label for="blp_{{ $key }}" class="w-12 shrink-0 text-xs font-semibold text-gray-600 dark:text-zinc-400" title="Rapor {{ \App\Http\Controllers\StudentReportController::REPORT_PERIODS[array_search($key, \App\Http\Controllers\StudentReportController::PERIOD_BLP)] }}">{{ $examLabel }}</label>
                                                    <input type="date" name="blp_dates[{{ $key }}]" id="blp_{{ $key }}" value="{{ $blpDates[$key] }}" x-model="blpDates['{{ $key }}']"
                                                           class="w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-sm text-gray-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Predikat & Deskripsi Tanse --}}
                        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white border-b pb-3 dark:border-zinc-800 flex items-center gap-2">
                                <x-heroicon-o-shield-check class="w-5 h-5 text-rose-500" />
                                <span>Predikat &amp; Deskripsi Tanse</span>
                            </h3>
                            <p class="text-[11px] text-gray-500 dark:text-zinc-400">Skor Tanse = 100 − poin pelanggaran triwulan. Deskripsi tercetak di kolom Deskripsi bagian Tanse rapor.</p>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label for="tanse_a_min" class="block text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Predikat A jika skor ≥</label>
                                    <input type="number" min="1" max="100" name="tanse_a_min" id="tanse_a_min" value="{{ old('tanse_a_min', $tanseRules['a_min']) }}" class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-sm text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label for="tanse_b_min" class="block text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Predikat B jika skor ≥</label>
                                    <input type="number" min="0" max="100" name="tanse_b_min" id="tanse_b_min" value="{{ old('tanse_b_min', $tanseRules['b_min']) }}" class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-sm text-gray-900 dark:text-white">
                                </div>
                            </div>
                            @error('tanse_b_min') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            @foreach (['A', 'B', 'C'] as $grade)
                                <div>
                                    <label for="tanse_note_{{ $grade }}" class="block text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-2">Deskripsi Predikat {{ $grade }}</label>
                                    <textarea name="tanse_notes[{{ $grade }}]" id="tanse_note_{{ $grade }}" rows="3" class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-sm text-gray-900 dark:text-white">{{ old('tanse_notes.'.$grade, $tanseRules['notes'][$grade]) }}</textarea>
                                </div>
                            @endforeach
                        </div>

                        {{-- 2. Header & Kop Surat Rapor --}}
                        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 sm:p-6 shadow-sm">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white border-b pb-3 mb-5 dark:border-zinc-800 flex items-center gap-2">
                                <x-heroicon-o-document-text class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                                <span>Header & Kop Surat Dokumen Rapor</span>
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="sm:col-span-2">
                                    <label for="report_main_title" class="block text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">Judul Utama Dokumen</label>
                                    <input type="text" name="report_main_title" id="report_main_title" x-model="reportMainTitle" required class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-sm text-gray-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                </div>

                                <div>
                                    <label for="report_city" class="block text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">Kota Titimangsa</label>
                                    <input type="text" name="report_city" id="report_city" x-model="reportCity" required class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-sm text-gray-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="report_school_name" class="block text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-1.5">Nama Sekolah / Subjudul Kop</label>
                                    <input type="text" name="report_school_name" id="report_school_name" x-model="reportSchoolName" required class="w-full rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-sm text-gray-900 dark:text-white focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>
                        </div>

                        {{-- 3. Pejabat Penandatangan Rapor --}}
                        <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 sm:p-6 shadow-sm">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white border-b pb-3 mb-5 dark:border-zinc-800 flex items-center gap-2">
                                <x-heroicon-o-pencil-square class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                                <span>Pejabat & Tanda Tangan Dokumen</span>
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                                {{-- Baris Atas: Kiri (Koordinator Tahfizh) --}}
                                <div class="p-3.5 bg-gray-50/70 dark:bg-zinc-800/40 rounded-xl border border-gray-200 dark:border-zinc-800 space-y-2.5">
                                    <h4 class="text-xs font-extrabold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">1. Koordinator Tahfizh (Kiri Atas)</h4>
                                    <div>
                                        <label for="report_coord_tahfizh_name" class="block text-[11px] font-bold text-gray-600 dark:text-zinc-400 mb-1">Nama Lengkap & Gelar</label>
                                        <input type="text" name="report_coord_tahfizh_name" id="report_coord_tahfizh_name" x-model="coordTahfizhName" required class="w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs text-gray-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label for="report_coord_tahfizh_nik" class="block text-[11px] font-bold text-gray-600 dark:text-zinc-400 mb-1">NIK</label>
                                        <input type="text" name="report_coord_tahfizh_nik" id="report_coord_tahfizh_nik" x-model="coordTahfizhNik" required class="w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs text-gray-900 dark:text-white">
                                    </div>
                                    @include('reports.partials.signature-upload', ['key' => 'coord_tahfizh', 'canEdit' => $canEditSignatures])
                                </div>

                                {{-- Baris Atas: Kanan (Koordinator Keagamaan) --}}
                                <div class="p-3.5 bg-gray-50/70 dark:bg-zinc-800/40 rounded-xl border border-gray-200 dark:border-zinc-800 space-y-2.5">
                                    <h4 class="text-xs font-extrabold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">2. Koordinator Keagamaan (Kanan Atas)</h4>
                                    <div>
                                        <label for="report_coord_keagamaan_name" class="block text-[11px] font-bold text-gray-600 dark:text-zinc-400 mb-1">Nama Lengkap & Gelar</label>
                                        <input type="text" name="report_coord_keagamaan_name" id="report_coord_keagamaan_name" x-model="coordKeagamaanName" required class="w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs text-gray-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label for="report_coord_keagamaan_nik" class="block text-[11px] font-bold text-gray-600 dark:text-zinc-400 mb-1">NIK</label>
                                        <input type="text" name="report_coord_keagamaan_nik" id="report_coord_keagamaan_nik" x-model="coordKeagamaanNik" required class="w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs text-gray-900 dark:text-white">
                                    </div>
                                    @include('reports.partials.signature-upload', ['key' => 'coord_keagamaan', 'canEdit' => $canEditSignatures])
                                </div>

                                {{-- Baris Bawah: Kiri (Kepala Sekolah) --}}
                                <div class="p-3.5 bg-gray-50/70 dark:bg-zinc-800/40 rounded-xl border border-gray-200 dark:border-zinc-800 space-y-2.5">
                                    <h4 class="text-xs font-extrabold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">3. Kepala Sekolah (Kiri Bawah)</h4>
                                    <div>
                                        <label for="report_headmaster_title" class="block text-[11px] font-bold text-gray-600 dark:text-zinc-400 mb-1">Jabatan</label>
                                        <input type="text" name="report_headmaster_title" id="report_headmaster_title" x-model="headmasterTitle" required class="w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs text-gray-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label for="report_headmaster_name" class="block text-[11px] font-bold text-gray-600 dark:text-zinc-400 mb-1">Nama Lengkap & Gelar</label>
                                        <input type="text" name="report_headmaster_name" id="report_headmaster_name" x-model="headmasterName" required class="w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs text-gray-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label for="report_headmaster_nik" class="block text-[11px] font-bold text-gray-600 dark:text-zinc-400 mb-1">NIK</label>
                                        <input type="text" name="report_headmaster_nik" id="report_headmaster_nik" x-model="headmasterNik" required class="w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs text-gray-900 dark:text-white">
                                    </div>
                                    @include('reports.partials.signature-upload', ['key' => 'headmaster', 'canEdit' => $canEditSignatures])
                                </div>

                                {{-- Baris Bawah: Kanan (Koordinator Tanse) --}}
                                <div class="p-3.5 bg-gray-50/70 dark:bg-zinc-800/40 rounded-xl border border-gray-200 dark:border-zinc-800 space-y-2.5">
                                    <h4 class="text-xs font-extrabold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">4. Koordinator Tanse (Kanan Bawah)</h4>
                                    <div>
                                        <label for="report_coord_tanse_name" class="block text-[11px] font-bold text-gray-600 dark:text-zinc-400 mb-1">Nama Lengkap & Gelar</label>
                                        <input type="text" name="report_coord_tanse_name" id="report_coord_tanse_name" x-model="coordTanseName" required class="w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs text-gray-900 dark:text-white">
                                    </div>
                                    <div>
                                        <label for="report_coord_tanse_nik" class="block text-[11px] font-bold text-gray-600 dark:text-zinc-400 mb-1">NIK</label>
                                        <input type="text" name="report_coord_tanse_nik" id="report_coord_tanse_nik" x-model="coordTanseNik" required class="w-full rounded-lg border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-xs text-gray-900 dark:text-white">
                                    </div>
                                    @include('reports.partials.signature-upload', ['key' => 'coord_tanse', 'canEdit' => $canEditSignatures])
                                </div>

                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-md hover:shadow-lg hover:scale-[1.01] active:scale-98 transition cursor-pointer">
                                <x-heroicon-o-check class="w-4 h-4" />
                                <span>Simpan Seluruh Pengaturan</span>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- RIGHT COLUMN: Live Interactive Document Preview (5 cols) --}}
                <div class="lg:col-span-5 lg:sticky lg:top-6 space-y-3">
                    
                    {{-- Preview Card Header --}}
                    <div class="flex items-center justify-between px-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider flex items-center gap-1.5">
                                <x-heroicon-o-document-magnifying-glass class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                                <span>Live Document Preview</span>
                            </span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 animate-pulse">
                                ● Real-time
                            </span>
                        </div>
                        <span class="text-[10px] text-gray-400">Miniatur Lembar Cetak</span>
                    </div>

                    {{-- Paper Mockup Sheet --}}
                    <div class="bg-white text-gray-900 rounded-xl p-4 sm:p-5 border border-gray-300 shadow-xl overflow-hidden select-none" style="font-family: 'Times New Roman', serif;">
                        
                        <!-- Mini Kop Surat -->
                        <div class="grid grid-cols-[38px_1fr_38px] items-center border-b border-black pb-2.5 mb-3">
                            <div class="shrink-0 flex justify-start">
                                <img src="{{ asset('images/logo_alazhar7.png') }}" class="h-9 w-auto object-contain" alt="Logo" />
                            </div>
                            
                            <div class="flex-1 flex flex-col items-center px-1 text-center">
                                <img src="{{ asset('images/image1.png') }}" class="h-3.5 object-contain mb-1" alt="Basmalah" />
                                <h4 class="text-[9px] font-black uppercase text-black leading-tight tracking-tight" x-text="reportMainTitle"></h4>
                                <p class="text-[8px] font-bold uppercase text-black mt-0.5 leading-none" x-text="reportSchoolName"></p>
                                
                                <div class="border border-black px-2 py-0.5 mt-1 bg-gray-50 text-[7px] font-bold text-black uppercase leading-none">
                                    <span x-text="periodLabels[reportPeriod]"></span>
                                </div>
                                <p class="text-[7px] font-bold text-black mt-0.5">Tahun Ajaran <span x-text="academicYear"></span></p>
                            </div>
                            
                            <div class="shrink-0 w-[38px]"></div>
                        </div>

                        <!-- Mini Identitas Siswa -->
                        <div class="text-[8px] text-black mb-3 space-y-0.5 border-b border-gray-100 pb-2">
                            <div class="grid grid-cols-[50px_1fr]">
                                <span class="font-bold">Nama</span>
                                <span>: Abbas Surya Permana (Contoh)</span>
                            </div>
                            <div class="grid grid-cols-[50px_1fr]">
                                <span class="font-bold">Kelas / Term</span>
                                <span>: X E2 / <span x-text="{{ \Illuminate\Support\Js::from(\App\Http\Controllers\StudentReportController::TERM_ROMAN) }}[reportPeriod]"></span></span>
                            </div>
                        </div>

                        <!-- Mini Section I: Tahfizh (Dynamic Toggle) -->
                        <div class="mb-2.5 space-y-1" x-show="showTahfizh" x-transition>
                            <h5 class="text-[8px] font-black uppercase text-black">I. LAPORAN TAHFIZH</h5>
                            {{-- Contoh Kelas 10/Ummi; Kelas 11/12 memakai Surah|Ayat + Baris, tanpa nilai. --}}
                            <table class="w-full border border-black text-[6px] text-center">
                                <thead class="bg-gray-100 border-b border-black font-bold">
                                    <tr>
                                        <th rowspan="2" class="p-0.5 border-r border-black w-4">No.</th>
                                        <th colspan="4" class="p-0.5 border-r border-b border-black">Target Triwulan</th>
                                        <th colspan="4" class="p-0.5 border-r border-b border-black">Capaian Akhir</th>
                                        <th rowspan="2" class="p-0.5 border-r border-black">Status</th>
                                        <th rowspan="2" class="p-0.5">Nilai &amp; Deskripsi</th>
                                    </tr>
                                    <tr class="border-b border-black font-semibold">
                                        @foreach ([1, 2] as $unused)
                                            <th class="p-0.5 border-r border-black">Jilid</th>
                                            <th class="p-0.5 border-r border-black">Hal.</th>
                                            <th class="p-0.5 border-r border-black">Surah</th>
                                            <th class="p-0.5 border-r border-black">Ayat</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-black">
                                        <td class="p-0.5 border-r border-black">1</td>
                                        <td class="p-0.5 border-r border-black">Jilid 1</td>
                                        <td class="p-0.5 border-r border-black">27</td>
                                        <td class="p-0.5 border-r border-black">Al-Bayyinah</td>
                                        <td class="p-0.5 border-r border-black">8</td>
                                        <td class="p-0.5 border-r border-black">Jilid 1</td>
                                        <td class="p-0.5 border-r border-black">27</td>
                                        <td class="p-0.5 border-r border-black">Al-Bayyinah</td>
                                        <td class="p-0.5 border-r border-black">8</td>
                                        <td class="p-0.5 border-r border-black font-bold text-emerald-700">Tuntas</td>
                                        <td class="p-0.5 text-left">
                                            <p class="text-center font-black">87 / 100</p>
                                            <p class="text-center font-bold">Jayyid Jiddan (Baik Sekali)</p>
                                            <p class="border-t border-gray-300 mt-0.5 pt-0.5 text-gray-600">Tercapai.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mini Section II: Adab (Dynamic Toggle) -->
                        <div class="mb-2.5 space-y-1" x-show="showAdab" x-transition>
                            <h5 class="text-[8px] font-black uppercase text-black">II. PENILAIAN ADAB</h5>
                            <table class="w-full border border-black text-[7px] text-left">
                                <thead class="bg-gray-100 border-b border-black font-bold text-center">
                                    <tr>
                                        <th class="p-0.5 border-r border-black w-5">No.</th>
                                        <th class="p-0.5 border-r border-black">Komponen</th>
                                        <th class="p-0.5 border-r border-black w-10">Nilai</th>
                                        <th class="p-0.5">Deskripsi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-black">
                                        <td class="p-0.5 border-r border-black text-center">1</td>
                                        <td class="p-0.5 border-r border-black">Adab Kepada Allah</td>
                                        <td class="p-0.5 border-r border-black text-center font-bold">A (100)</td>
                                        <td class="p-0.5 italic text-gray-600">Mumtaz (Sangat Baik)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mini Section III: Tanse (Dynamic Toggle) -->
                        <div class="mb-3 space-y-1" x-show="showTanse" x-transition>
                            <h5 class="text-[8px] font-black uppercase text-black">III. LAPORAN TANSE</h5>
                            <table class="w-full border border-black text-[7px] text-left">
                                <thead class="bg-gray-100 border-b border-black font-bold text-center">
                                    <tr>
                                        <th class="p-0.5 border-r border-black w-5">No.</th>
                                        <th class="p-0.5 border-r border-black">Jenis</th>
                                        <th class="p-0.5 border-r border-black w-8">Poin</th>
                                        <th class="p-0.5">Deskripsi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-black">
                                        <td class="p-0.5 border-r border-black text-center">1</td>
                                        <td class="p-0.5 border-r border-black">Penghargaan</td>
                                        <td class="p-0.5 border-r border-black text-center text-emerald-700 font-bold">0</td>
                                        <td rowspan="2" class="p-0.5 text-gray-700 align-top"><span class="font-bold">Predikat A</span> &mdash; Alhamdulillah ananda sudah Sangat Baik&hellip;</td>
                                    </tr>
                                    <tr class="border-b border-black">
                                        <td class="p-0.5 border-r border-black text-center">2</td>
                                        <td class="p-0.5 border-r border-black">Pelanggaran</td>
                                        <td class="p-0.5 border-r border-black text-center text-rose-700 font-bold">0</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Mini Live Signatures (Pixel-Perfect 4 Block Grid) -->
                        <div class="border-t border-gray-200 pt-2 text-[7px] text-black">
                            
                            <!-- Row 1 -->
                            <div class="grid grid-cols-2 gap-3 text-center">
                                <div>
                                    <p class="invisible leading-tight" x-text="reportCity + ', ' + reportDate"></p>
                                    <p class="font-bold leading-tight">Koordinator Tahfizh</p>
                                    <div class="h-6 flex items-center justify-center">
                                        <template x-if="sig['coord_tahfizh']"><img :src="sig['coord_tahfizh']" alt="" class="max-h-6 max-w-[70px] object-contain"></template>
                                    </div>
                                    <p class="font-black underline leading-tight" x-text="coordTahfizhName"></p>
                                    <p class="text-[6px] text-gray-600 leading-none">NIK. <span x-text="coordTahfizhNik"></span></p>
                                </div>
                                <div>
                                    <p class="leading-tight" x-text="reportCity + ', ' + reportDate"></p>
                                    <p class="font-bold leading-tight">Koordinator Keagamaan</p>
                                    <div class="h-6 flex items-center justify-center">
                                        <template x-if="sig['coord_keagamaan']"><img :src="sig['coord_keagamaan']" alt="" class="max-h-6 max-w-[70px] object-contain"></template>
                                    </div>
                                    <p class="font-black underline leading-tight" x-text="coordKeagamaanName"></p>
                                    <p class="text-[6px] text-gray-600 leading-none">NIK. <span x-text="coordKeagamaanNik"></span></p>
                                </div>
                            </div>

                            <!-- Row 2 -->
                            <div class="grid grid-cols-2 gap-3 text-center mt-2.5">
                                <div>
                                    <p class="leading-tight">Mengetahui,</p>
                                    <p class="font-bold leading-tight truncate" x-text="headmasterTitle"></p>
                                    <div class="h-6 flex items-center justify-center">
                                        <template x-if="sig['headmaster']"><img :src="sig['headmaster']" alt="" class="max-h-6 max-w-[70px] object-contain"></template>
                                    </div>
                                    <p class="font-black underline leading-tight truncate" x-text="headmasterName"></p>
                                    <p class="text-[6px] text-gray-600 leading-none">NIK. <span x-text="headmasterNik"></span></p>
                                </div>
                                <div>
                                    <p class="invisible leading-tight">Mengetahui,</p>
                                    <p class="font-bold leading-tight">Koordinator Tanse</p>
                                    <div class="h-6 flex items-center justify-center">
                                        <template x-if="sig['coord_tanse']"><img :src="sig['coord_tanse']" alt="" class="max-h-6 max-w-[70px] object-contain"></template>
                                    </div>
                                    <p class="font-black underline leading-tight truncate" x-text="coordTanseName"></p>
                                    <p class="text-[6px] text-gray-600 leading-none">NIK. <span x-text="coordTanseNik"></span></p>
                                </div>
                            </div>

                        </div>

                    </div>

                    <p class="text-[11px] text-gray-500 dark:text-zinc-400 text-center italic flex items-center justify-center gap-1">
                        <x-heroicon-o-light-bulb class="w-3.5 h-3.5 text-amber-500 shrink-0" />
                        <span>Tampilan di atas adalah miniatur format cetak sebenarnya.</span>
                    </p>
                </div>

            </div>

            {{-- Opsi Print Rapor Per Kelas --}}
            <div class="bg-white dark:bg-zinc-900 border border-gray-200 dark:border-zinc-800 rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">
                <div class="border-b pb-3 dark:border-zinc-800">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-printer class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                        <span>Opsi Cetak Rapor Per Kelas (Batch Print Rapor)</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">Cetak seluruh rapor murid dalam 1 kelas secara lengkap sekaligus dalam satu dokumen siap cetak/PDF.</p>
                    <p class="text-xs text-gray-500 mt-1">
                        <strong>Kunci</strong> membekukan isi rapor seluruh santri kelas itu untuk periode terpilih, sehingga rapor tetap utuh walau data berikutnya berubah.
                        Membuka kunci hanya bisa oleh Super Admin.
                    </p>
                </div>

                {{-- Periode yang dicetak/dikunci: bisa periode lama, terpisah dari periode aktif di atas. --}}
                <form method="GET" action="{{ route('digital-reports.settings') }}" class="flex flex-wrap items-end gap-3">
                    <div>
                        <label for="print_year" class="block text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-1">Tahun Ajaran</label>
                        <select name="print_year" id="print_year" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-sm text-gray-900 dark:text-white">
                            @foreach ($academicYearOptions as $yearOption)
                                <option value="{{ $yearOption }}" @selected($printYear === $yearOption)>{{ $yearOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="print_term" class="block text-xs font-bold text-gray-700 dark:text-zinc-300 uppercase tracking-wider mb-1">Periode</label>
                        <select name="print_term" id="print_term" onchange="this.form.submit()" class="rounded-xl border-gray-300 dark:border-zinc-700 dark:bg-zinc-800 text-sm text-gray-900 dark:text-white">
                            @foreach (\App\Http\Controllers\StudentReportController::REPORT_PERIODS as $periodValue => $periodLabel)
                                <option value="{{ $periodValue }}" @selected($printTerm === $periodValue)>{{ $periodLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse ($classRooms as $cRoom)
                        <div class="p-4 bg-gray-50 dark:bg-zinc-800/50 rounded-2xl border border-gray-200 dark:border-zinc-800 flex flex-col justify-between space-y-3">
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white text-base">{{ $cRoom->name }}</h4>
                                <p class="text-xs text-gray-500 font-medium mt-0.5">Program: {{ $cRoom->program?->name ?: '-' }}</p>
                            </div>
                            @php $lock = $classLocks->get($cRoom->id); @endphp
                            @if ($lock)
                                <p class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-600 dark:text-rose-400">
                                    <x-heroicon-o-lock-closed class="w-3.5 h-3.5" />
                                    Terkunci {{ \Illuminate\Support\Carbon::parse($lock->locked_at)->locale('id')->translatedFormat('d M Y H:i') }} &middot; {{ $lock->total }} santri
                                </p>
                            @else
                                <p class="text-[11px] text-gray-500 dark:text-zinc-400">Belum dikunci &middot; rapor dihitung dari data terkini</p>
                            @endif
                            <a href="{{ route('digital-reports.class-print', ['classRoom' => $cRoom->id, 'academic_year' => $printYear, 'term' => $printTerm]) }}" target="_blank" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition w-full">
                                <x-heroicon-o-printer class="w-4 h-4" />
                                <span>Cetak Rapor Seluruh Kelas</span>
                            </a>
                            @if (! $lock)
                                <form method="POST" action="{{ route('digital-reports.class-lock', $cRoom) }}"
                                      onsubmit="return confirm({{ \Illuminate\Support\Js::from("Kunci rapor {$printPeriodLabel} {$printYear} kelas {$cRoom->name}? Isi rapor seluruh santri kelas ini akan dibekukan.") }})">
                                    @csrf
                                    <input type="hidden" name="academic_year" value="{{ $printYear }}">
                                    <input type="hidden" name="term" value="{{ $printTerm }}">
                                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 border border-rose-300 dark:border-rose-800 text-rose-700 dark:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950/30 font-bold text-xs rounded-xl transition w-full">
                                        <x-heroicon-o-lock-closed class="w-4 h-4" />
                                        <span>Kunci Rapor {{ $printPeriodLabel }}</span>
                                    </button>
                                </form>
                            @elseif ($canUnlock)
                                <form method="POST" action="{{ route('digital-reports.class-unlock', $cRoom) }}"
                                      onsubmit="return confirm({{ \Illuminate\Support\Js::from("Buka kunci rapor {$printPeriodLabel} {$printYear} kelas {$cRoom->name}? Data yang dibekukan akan dibuang dan rapor kembali dihitung dari data terkini.") }})">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="academic_year" value="{{ $printYear }}">
                                    <input type="hidden" name="term" value="{{ $printTerm }}">
                                    <button type="submit" class="inline-flex items-center justify-center gap-1.5 px-4 py-2 border border-gray-300 dark:border-zinc-700 text-gray-700 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-800 font-bold text-xs rounded-xl transition w-full">
                                        <x-heroicon-o-lock-open class="w-4 h-4" />
                                        <span>Buka Kunci</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 py-4 col-span-full">Belum ada kelas terdaftar.</p>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
