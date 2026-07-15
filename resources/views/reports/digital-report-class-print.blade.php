<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Masal Rapor Digital Kelas - {{ $classRoom->name }}</title>
    <!-- Tailwind CSS v4 via CDN for print layout rendering -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: 215mm 330mm;
            margin: 8mm 12mm;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: white !important;
                color: black !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .page-break {
                page-break-before: always;
            }
            .print-container {
                min-height: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                width: 100% !important;
                max-width: 100% !important;
                zoom: 80% !important;
            }
            .signature-block {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
        body {
            font-family: 'Times New Roman', Times, serif;
        }
        .logo-left {
            width: 90px;
            height: 80px;
            object-fit: cover;
            object-position: left;
        }
        .logo-right {
            width: 92px;
            height: 80px;
            object-fit: cover;
            object-position: right;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-900 p-4 sm:p-8">

    <!-- Floating Action Button for print (hidden on print) -->
    <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center no-print bg-white p-4 rounded-xl border shadow-sm">
        <span class="text-sm text-gray-500 font-semibold">📄 Cetak Masal Rapor Kelas: {{ $classRoom->name }}</span>
        <div class="flex gap-2">
            <button onclick="window.close()" class="px-4 py-2 border rounded-lg text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50">
                Tutup Halaman
            </button>
            <button onclick="window.print()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-bold shadow">
                Cetak Sekarang
            </button>
        </div>
    </div>

    @foreach ($reportsData as $index => $data)
        @php
            $student = $data['student'];
            $academicYear = $data['academicYear'];
            $semester = $data['semester'];
            $progress = $data['progress'];
            $hafalanRecords = $data['hafalanRecords'];
            $murajaahRecords = $data['murajaahRecords'];
            $targetRecords = $data['targetRecords'];
            $tahfizhExams = $data['tahfizhExams'];
            $tahfizhLevelLabel = $data['tahfizhLevelLabel'];
            $termTargetText = $data['termTargetText'];
            $latestCapaianText = $data['latestCapaianText'];
            $latestCapaianNotes = $data['latestCapaianNotes'];
            $avgAllah = $data['avgAllah'];
            $avgTeman = $data['avgTeman'];
            $avgBelajar = $data['avgBelajar'];
            $avgLingkungan = $data['avgLingkungan'];
            $avgQuran = $data['avgQuran'];
            $avgTotal = $data['avgTotal'];
            $violations = $data['violations'];
            $rewards = $data['rewards'];
            $report = $data['report'] ?? null;
        @endphp

        <!-- Official Report Card Layout -->
        <div class="print-container max-w-4xl mx-auto bg-white p-8 sm:p-12 border shadow-sm rounded-none min-h-[330mm] {{ $index > 0 ? 'page-break mt-8 print:mt-0' : '' }}" style="font-family: 'Times New Roman', serif;">
            
            <!-- Kop Surat Terpadu (Sesuai PDF Baru Integrasi) -->
            <div class="flex items-center justify-between border-b border-black pb-4 mb-6">
                <!-- Left Logo: Al Azhar (cropped from image2.png) -->
                <div class="shrink-0">
                    <img src="{{ asset('images/image2.png') }}" class="logo-left" alt="Logo Al Azhar" />
                </div>
                
                <!-- Title & Basmalah -->
                <div class="flex-1 flex flex-col items-center px-4">
                    <img src="{{ asset('images/image1.png') }}" class="h-6 object-contain mb-2" alt="Basmalah" />
                    <h1 class="text-xs sm:text-sm font-black text-black uppercase tracking-wider text-center">LAPORAN PENILAIAN ADAB, TAHFIDZ, DAN TANSE</h1>
                    <h2 class="text-[10px] sm:text-xs font-bold text-black uppercase text-center mt-0.5">SMA ISLAM AL AZHAR 7 SUKOHARJO</h2>
                    
                    <!-- Semester Box -->
                    <div class="border border-black px-4 py-0.5 mt-2 bg-gray-50 text-[9px] font-bold text-black uppercase">
                        SEMESTER : {{ $semester == 1 ? '1 (SATU)' : '2 (DUA)' }}
                    </div>
                    
                    <p class="text-[9px] font-bold text-black mt-1">Tahun Ajaran {{ $academicYear }}</p>
                </div>
                
                <!-- Right Logo: Makarimah (cropped from image2.png) -->
                <div class="shrink-0">
                    <img src="{{ asset('images/image2.png') }}" class="logo-right" alt="Logo Makarimah" />
                </div>
            </div>

            <!-- Identitas Siswa -->
            <table class="text-xs text-black mb-6" style="line-height: 1.6; min-width: 300px;">
                <tr>
                    <td class="w-20 font-bold">Nama</td>
                    <td class="w-4">:</td>
                    <td>{{ $student->name }}</td>
                </tr>
                <tr>
                    <td class="font-bold">NIS/NISN</td>
                    <td>:</td>
                    <td>{{ $student->student_number ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="font-bold">Kelas</td>
                    <td>:</td>
                    <td>{{ $student->classRoom?->name ?: '-' }}</td>
                </tr>
                <tr>
                    <td class="font-bold">Term</td>
                    <td>:</td>
                    <td>{{ $student->classRoom?->program?->name ?: '-' }}</td>
                </tr>
            </table>

            <!-- I. LAPORAN TAHFIDZ -->
            <div class="mb-6 space-y-3">
                <h3 class="text-xs font-black uppercase text-black">I. LAPORAN TAHFIDZ</h3>
                
                <!-- Table 1: Targets Summary -->
                <table class="w-full border border-black text-xs text-left">
                    <thead>
                        <tr class="bg-gray-100 border-b border-black text-center font-bold">
                            <th class="p-1 border-r border-black w-10">No.</th>
                            <th class="p-1 border-r border-black w-48">TARGET</th>
                            <th class="p-1 border-r border-black w-48">CAPAIAN</th>
                            <th class="p-1 border-r border-black w-28">KETERANGAN</th>
                            <th class="p-1">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($targetRecords as $idx => $target)
                            <tr class="border-b border-black">
                                <td class="p-1.5 border-r border-black text-center align-middle">{{ $idx + 1 }}</td>
                                <td class="p-1.5 border-r border-black align-middle">
                                    QS. {{ $target->surah?->name_latin ?? '-' }} (Ayat {{ $target->ayah_start }}-{{ $target->ayah_end }})
                                </td>
                                <td class="p-1.5 border-r border-black align-middle">
                                    @if($target->matching_record)
                                        QS. {{ $target->matching_record->surah?->name_latin ?? '-' }} (Ayat {{ $target->matching_record->ayah_start }}-{{ $target->matching_record->ayah_end }})
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="p-1.5 border-r border-black text-center align-middle font-bold {{ $target->status === 'completed' ? 'text-green-700' : 'text-red-650' }}">
                                    {{ $target->status === 'completed' ? 'Tuntas' : 'Tidak Tuntas' }}
                                </td>
                                <td class="p-1.5 align-middle text-gray-700">
                                    {{ $target->notes ?: ($target->status === 'completed' ? 'Target hafalan term ini telah tercapai.' : 'Belum menyelesaikan target hafalan.') }}
                                </td>
                            </tr>
                        @empty
                            <tr class="border-b border-black">
                                <td colspan="5" class="p-3 text-center text-gray-500 italic">Belum ada data target tahfizh.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Table 2: Nilai Ujian (Recent Exams) -->
                <table class="w-full border border-black text-xs text-left mt-4">
                    <thead>
                        <tr class="bg-gray-100 border-b border-black text-center font-bold">
                            <th class="p-1 border-r border-black w-10">No.</th>
                            <th class="p-1 border-r border-black">NILAI UJIAN</th>
                            <th class="p-1 border-r border-black w-28">KETERANGAN</th>
                            <th class="p-1">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tahfizhExams as $idx => $exam)
                            <tr class="border-b border-black">
                                <td class="p-1.5 border-r border-black text-center">{{ $idx + 1 }}</td>
                                <td class="p-1.5 border-r border-black font-semibold">{{ $exam->exam_range }}</td>
                                <td class="p-1.5 border-r border-black text-center font-bold text-indigo-750">Skor: {{ round($exam->total_score) }}</td>
                                <td class="p-1.5 text-gray-600">{{ $exam->notes ?: 'Lulus ujian tahfizh' }}</td>
                            </tr>
                        @empty
                            <tr class="border-b border-black">
                                <td colspan="4" class="p-3 text-center text-gray-500 italic">Belum ada data nilai ujian tahfizh.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- II. PENILAIAN ADAB -->
            <div class="mb-6 space-y-3">
                <h3 class="text-xs font-black uppercase text-black">II. PENILAIAN ADAB</h3>
                
                <table class="w-full border border-black text-xs text-left">
                    <thead>
                        <tr class="bg-gray-100 border-b border-black text-center font-bold">
                            <th class="p-1 border-r border-black w-10">No.</th>
                            <th class="p-1 border-r border-black w-56">KOMPONEN ADAB</th>
                            <th class="p-1 border-r border-black w-24">Nilai</th>
                            <th class="p-1">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-black">
                            <td class="p-2 border-r border-black text-center align-middle">1</td>
                            <td class="p-2 border-r border-black font-semibold align-middle">ADAB KEPADA ALLAH ({{ round($avgAllah) }}%)</td>
                            <td rowspan="4" class="p-2 border-r border-black text-center align-middle font-bold text-sm text-black">
                                @php
                                    $pred = \App\Models\Setting::getAdabGrade($avgTotal);
                                    $predLabel = \App\Models\Setting::getAdabGradeLabel($pred);
                                    $desc = '';
                                    if ($avgTotal >= 90) {
                                        $desc = 'Sangat baik (Mumtaz), konsisten beribadah kepada Allah, berperilaku sopan terhadap sesama teman, menerapkan adab belajar secara tertib dan disiplin, serta menjaga kebersihan lingkungan dengan sangat baik.';
                                    } elseif ($avgTotal >= 80) {
                                        $desc = 'Baik sekali (Jayyid Jiddan), rutin melaksanakan ibadah harian, bersikap sopan kepada teman, tertib dalam mengikuti pelajaran, dan turut menjaga kebersihan lingkungan dengan baik.';
                                    } elseif ($avgTotal >= 70) {
                                        $desc = 'Baik (Jayyid), menunjukkan kesopanan kepada guru dan teman, mengikuti kegiatan belajar dengan tertib, dan menjaga kebersihan diri serta lingkungan.';
                                    } elseif ($avgTotal >= 60) {
                                        $desc = 'Cukup (Maqbul), sudah berusaha membiasakan adab harian dengan cukup baik, namun masih memerlukan pengawasan dan motivasi berkala agar lebih konsisten.';
                                    } else {
                                        $desc = 'Kurang (Dha\'if), memerlukan pembinaan moral intensif serta bimbingan khusus baik di sekolah maupun asrama untuk meningkatkan kedisiplinan dan adab sehari-hari.';
                                    }
                                @endphp
                                <span class="text-base font-black">{{ $pred }}</span>
                                <span class="text-[9px] font-bold text-gray-700 block mt-1 uppercase">{{ round($avgTotal) }}/100</span>
                            </td>
                            <td rowspan="4" class="p-3 text-gray-700 leading-relaxed align-middle">
                                {{ $desc }}
                            </td>
                        </tr>
                        <tr class="border-b border-black">
                            <td class="p-2 border-r border-black text-center align-middle">2</td>
                            <td class="p-2 border-r border-black font-semibold align-middle">ADAB KEPADA SESAMA TEMAN ({{ round($avgTeman) }}%)</td>
                        </tr>
                        <tr class="border-b border-black">
                            <td class="p-2 border-r border-black text-center align-middle">3</td>
                            <td class="p-2 border-r border-black font-semibold align-middle">ADAB KETIKA BELAJAR ({{ round($avgBelajar) }}%)</td>
                        </tr>
                        <tr class="border-b border-black">
                            <td class="p-2 border-r border-black text-center align-middle">4</td>
                            <td class="p-2 border-r border-black font-semibold align-middle">ADAB TERHADAP LINGKUNGAN ({{ round($avgLingkungan) }}%)</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- III. LAPORAN TANSE -->
            <div class="mb-6 space-y-3">
                <h3 class="text-xs font-black uppercase text-black">III. LAPORAN TANSE</h3>
                
                <table class="w-full border border-black text-xs text-left">
                    <thead>
                        <tr class="bg-gray-100 border-b border-black text-center font-bold">
                            <th class="p-1 border-r border-black w-10">No.</th>
                            <th class="p-1 border-r border-black w-48">JENIS PERILAKU</th>
                            <th class="p-1 border-r border-black w-24">POIN</th>
                            <th class="p-1">Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b border-black">
                            <td class="p-2 border-r border-black text-center">1</td>
                            <td class="p-2 border-r border-black font-bold">Penghargaan</td>
                            <td class="p-2 border-r border-black text-center font-bold text-emerald-700">{{ $rewards->sum('points') }}</td>
                            <td class="p-2 text-gray-700">
                                @if($rewards->isNotEmpty())
                                    <ul class="list-disc list-inside">
                                        @foreach($rewards as $r)
                                            <li>{{ $r->notes }} (+{{ $r->points }} Poin)</li>
                                        @endforeach
                                    </ul>
                                @else
                                    Nihil - Tidak memiliki catatan penghargaan/prestasi.
                                @endif
                            </td>
                        </tr>
                        <tr class="border-b border-black">
                            <td class="p-2 border-r border-black text-center">2</td>
                            <td class="p-2 border-r border-black font-bold">Pelanggaran</td>
                            <td class="p-2 border-r border-black text-center font-bold text-rose-700">{{ $violations->sum('points') }}</td>
                            <td class="p-2 text-gray-700">
                                @if($violations->isNotEmpty())
                                    <ul class="list-disc list-inside text-rose-700">
                                        @foreach($violations as $v)
                                            <li>{{ $v->notes }} (-{{ $v->points }} Poin)</li>
                                        @endforeach
                                    </ul>
                                @else
                                    Nihil - Tidak memiliki catatan pelanggaran perilaku negatif.
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Catatan Wali Kelas -->
            <div class="mb-6 p-3 border border-black rounded-none text-xs">
                <h4 class="font-bold text-black uppercase mb-1">CATATAN & EVALUASI WALI KELAS:</h4>
                <p class="italic text-gray-900 leading-relaxed font-semibold">
                    "{{ $report?->teacher_notes ?: 'Belum ada catatan deskriptif dari wali kelas.' }}"
                </p>
            </div>

            <!-- Signature Area (4 Kolom Sesuai PDF Rapor Baru Integrasi) -->
            <div class="signature-block w-full text-xs text-black mt-8">
                <!-- Row 1 -->
                <div class="grid grid-cols-2 gap-8 text-center">
                    <div>
                        <p class="font-semibold">Koordinator Tahfidz</p>
                        <div class="h-16 print:h-12"></div>
                        <p class="font-bold underline text-black">Zainal Arifin, S.Pd</p>
                        <p class="text-[10px] text-gray-650">NIK. 15.06.0393</p>
                    </div>
                    <div>
                        <p>Sukoharjo, {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}</p>
                        <p class="font-semibold">Koordinator Keagamaan</p>
                        <div class="h-16 print:h-12"></div>
                        <p class="font-bold underline text-black">Rifqi Ihsan, S.Pd</p>
                        <p class="text-[10px] text-gray-650">NIK. 15.06.0393</p>
                    </div>
                </div>
                
                <!-- Row 2 -->
                <div class="grid grid-cols-2 gap-8 text-center mt-6">
                    <div>
                        <p>Mengetahui,</p>
                        <p class="font-semibold">Kepala SMA Islam Al Azhar 7 Sukoharjo</p>
                        <div class="h-16 print:h-12"></div>
                        <p class="font-bold underline text-black">Moh Pandoyo, S.Si., M.Pd., Gr.</p>
                        <p class="text-[10px] text-gray-650">NIK. 08.04.0160</p>
                    </div>
                    <div>
                        <p class="invisible">Spacer</p>
                        <p class="font-semibold">Koordinator Tanse</p>
                        <div class="h-16 print:h-12"></div>
                        <p class="font-bold underline text-black">Yatim Hermawan, S.E., S.Kom</p>
                        <p class="text-[10px] text-gray-650">NIK. 15.06.0393</p>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Auto Print Trigger script -->
    <script>
        window.addEventListener('DOMContentLoaded', (event) => {
            // Auto open print dialog
            setTimeout(() => {
                window.print();
            }, 800);
        });
    </script>
</body>
</html>
