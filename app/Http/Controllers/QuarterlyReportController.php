<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Student;
use Illuminate\Http\Request;

class QuarterlyReportController extends Controller
{
    public function index(Request $request)
    {
        // Load all classrooms with their program
        $classRooms = ClassRoom::query()->with('program')->orderBy('name')->get();
        $selectedClassId = $request->input('class_room_id', $classRooms->first()?->id);
        $selectedClass = $classRooms->firstWhere('id', $selectedClassId);
        
        $academicYear = $request->input('academic_year', '2025/2026');
        $selectedTerm = $request->input('term', '1'); // 1, 2, 3, 4

        // Detect program type
        $programName = strtolower($selectedClass?->program?->name ?? '');
        $isTahfizhProgram = str_contains($programName, 'tahfizh') || str_contains($programName, 'akselerasi');

        // Get actual active students in the selected class
        $students = Student::query()
            ->with(['classRoom', 'teacher.user'])
            ->where('class_room_id', $selectedClassId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // If no students in the class, load some active ones from other classes for demo purposes
        if ($students->isEmpty()) {
            $students = Student::query()
                ->with(['classRoom', 'teacher.user'])
                ->where('status', 'active')
                ->orderBy('name')
                ->take(12)
                ->get();
        }

        // Group students by their Musyrif (teacher)
        $studentsByHalaqah = $students->groupBy(function($student) {
            return $student->teacher?->user?->name ?? 'Ust. Fuad Faris Ghazi';
        });

        $halaqahData = [];
        $surahs = ['Al-Mulk', 'Al-Qalam', 'Al-Haaqqa', 'Al-Ma\'arij', 'Nuh', 'Al-Jinn', 'Al-Muzzammil', 'Al-Muddathir', 'Al-Qiyamah', 'Al-Insan'];

        // Determine months based on term
        $months = [];
        if ($selectedTerm == '1') {
            $months = ['Juli', 'Agustus', 'September'];
        } elseif ($selectedTerm == '2') {
            $months = ['Oktober', 'November', 'Desember'];
        } elseif ($selectedTerm == '3') {
            $months = ['Januari', 'Februari', 'Maret'];
        } else {
            $months = ['April', 'Mei', 'Juni'];
        }

        // Process data for each Musyrif's Halaqah group
        foreach ($studentsByHalaqah as $musyrifName => $groupStudents) {
            $tahfizhRecords = [];
            $regulerRecords = [];

            // 1. Generate Presensi Data
            $presensiData = [];
            if ($isTahfizhProgram) {
                // Tahfizh: 3 months, each with 12 tatap muka columns
                foreach ($groupStudents as $idx => $student) {
                    $studentPresensi = [];
                    foreach ($months as $month) {
                        $daily = [];
                        $sakit = $idx % 7 === 0 ? 1 : 0;
                        $izin = $idx % 9 === 0 ? 1 : 0;
                        $alpa = $idx % 11 === 0 ? 1 : 0;
                        
                        // Generate 12 tatap muka markers
                        for ($i = 1; $i <= 12; $i++) {
                            if ($sakit > 0 && $i == 4) {
                                $daily[$i] = 'S';
                            } elseif ($izin > 0 && $i == 8) {
                                $daily[$i] = 'I';
                            } elseif ($alpa > 0 && $i == 12) {
                                $daily[$i] = 'A';
                            } else {
                                $daily[$i] = 'H';
                            }
                        }
                        $studentPresensi[$month] = [
                            'days' => $daily,
                            'sakit' => $sakit,
                            'izin' => $izin,
                            'alpa' => $alpa
                        ];
                    }
                    $presensiData[$student->id] = $studentPresensi;
                }
            } else {
                // Reguler: Pekan 1 - Pekan 5
                foreach ($groupStudents as $idx => $student) {
                    $pekan = [];
                    $sakit = $idx % 6 === 0 ? 1 : 0;
                    $izin = $idx % 8 === 0 ? 1 : 0;
                    $alpa = $idx % 10 === 0 ? 1 : 0;
                    for ($p = 1; $p <= 5; $p++) {
                        if ($sakit > 0 && $p == 3) {
                            $pekan[$p] = 'Sakit';
                        } elseif ($izin > 0 && $p == 4) {
                            $pekan[$p] = 'Izin';
                        } elseif ($alpa > 0 && $p == 5) {
                            $pekan[$p] = 'Alpa';
                        } else {
                            $pekan[$p] = 'Hadir';
                        }
                    }
                    $presensiData[$student->id] = [
                        'pekan' => $pekan,
                        'hadir' => 5 - ($sakit + $izin + $alpa),
                        'sakit' => $sakit,
                        'izin' => $izin,
                        'alpa' => $alpa
                    ];
                }
            }

            // 2. Generate Jurnal Data
            $jurnalData = [];
            if ($isTahfizhProgram) {
                // Jurnal Harian (12 Tatap Muka)
                for ($tm = 1; $tm <= 12; $tm++) {
                    $dayOffset = ($tm - 1) * 2;
                    $jurnalData[] = [
                        'tanggal' => date('Y-m-d', strtotime("2026-09-01 + {$dayOffset} days")),
                        'materi' => "Ziyadah & Muroja'ah Hafalan Surah " . $surahs[$tm % count($surahs)],
                        'jumlah_murid' => count($groupStudents),
                        'paraf' => '✓'
                    ];
                }
            } else {
                // Jurnal Mingguan (Pekan 1 - Pekan 5)
                for ($p = 1; $p <= 5; $p++) {
                    $jurnalData[] = [
                        'tanggal' => "Pekan $p",
                        'materi' => "Ziyadah & Muroja'ah Hafalan Kelas Reguler",
                        'jumlah_murid' => count($groupStudents),
                        'paraf' => '✓'
                    ];
                }
            }

            // 3. Generate Setoran Data
            if ($isTahfizhProgram) {
                // Format Tahfizh (Pekan 1-5, Senin-Jumat per murid)
                foreach ($groupStudents as $idx => $student) {
                    $pekanRecords = [];
                    $totalCapaianLines = 0;
                    
                    for ($p = 1; $p <= 5; $p++) {
                        $dailyLogs = [];
                        $weekLines = 0;
                        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
                        
                        foreach ($days as $dayIdx => $day) {
                            $isAbsent = ($idx % 7 === 0 && $p === 2 && $day === 'Rabu');
                            if ($isAbsent) {
                                $dailyLogs[$day] = [
                                    'surah' => 'Tidak Masuk',
                                    'ayat_start' => '',
                                    'ayat_end' => '',
                                    'baris' => 0,
                                    'nilai' => 'S'
                                ];
                            } else {
                                $surahName = $surahs[($idx + $p + $dayIdx) % count($surahs)];
                                $baris = $idx % 2 === 0 ? 15 : 10;
                                $dailyLogs[$day] = [
                                    'surah' => $surahName,
                                    'ayat_start' => $dayIdx * 5 + 1,
                                    'ayat_end' => $dayIdx * 5 + 5,
                                    'baris' => $baris,
                                    'nilai' => $idx % 3 === 0 ? 'B+' : 'A'
                                ];
                                $weekLines += $baris;
                            }
                        }
                        
                        $pekanRecords[$p] = [
                            'days' => $dailyLogs,
                            'week_lines' => $weekLines
                        ];
                        $totalCapaianLines += $weekLines;
                    }
                    
                    $targetLines = $idx % 2 === 0 ? 150 : 120;
                    $isTuntas = $totalCapaianLines >= $targetLines;

                    $tahfizhRecords[] = [
                        'student_id' => $student->id,
                        'name' => $student->name,
                        'nis' => $student->student_number ?? '4407-2526' . sprintf('%03d', $idx + 1),
                        'level' => ucfirst($student->tahfizh_level ?? 'reguler'),
                        'pekan' => $pekanRecords,
                        'target_lines' => $targetLines,
                        'total_lines' => $totalCapaianLines,
                        'is_tuntas' => $isTuntas,
                        'pelanggaran' => $idx % 8 === 0 ? 1 : 0
                    ];
                }
            } else {
                // Format Reguler (Pekan 1-5 in a single table)
                foreach ($groupStudents as $idx => $student) {
                    $pekanRecords = [];
                    $totalCapaianLines = 0;
                    
                    for ($p = 1; $p <= 5; $p++) {
                        $isAbsent = ($presensiData[$student->id]['pekan'][$p] !== 'Hadir');
                        if ($isAbsent) {
                            $pekanRecords[$p] = [
                                'surah' => '-',
                                'ayat' => '-',
                                'baris' => 0,
                                'nilai' => '-',
                                'kehadiran' => $presensiData[$student->id]['pekan'][$p]
                            ];
                        } else {
                            $surahName = $surahs[($idx + $p) % count($surahs)];
                            $baris = $idx % 2 === 0 ? 10 : 8;
                            $pekanRecords[$p] = [
                                'surah' => $surahName,
                                'ayat' => (($p - 1) * 10 + 1) . '-' . ($p * 10),
                                'baris' => $baris,
                                'nilai' => 'A',
                                'kehadiran' => 'Hadir'
                            ];
                            $totalCapaianLines += $baris;
                        }
                    }

                    $targetLines = 40; // Default target
                    $isTuntas = $totalCapaianLines >= $targetLines;

                    $regulerRecords[] = [
                        'student_id' => $student->id,
                        'name' => $student->name,
                        'nis' => $student->student_number ?? '4407-2526' . sprintf('%03d', $idx + 1),
                        'level' => ucfirst($student->tahfizh_level ?? 'reguler'),
                        'pekan' => $pekanRecords,
                        'target_lines' => $targetLines,
                        'total_lines' => $totalCapaianLines,
                        'is_tuntas' => $isTuntas,
                        'pelanggaran' => $idx % 7 === 0 ? 1 : 0
                    ];
                }
            }

            // Group everything under this Halaqah Musyrif
            $halaqahData[] = [
                'musyrif' => $musyrifName,
                'students' => $groupStudents,
                'is_tahfizh' => $isTahfizhProgram,
                'presensi' => $presensiData,
                'jurnal' => $jurnalData,
                'tahfizh_records' => $tahfizhRecords,
                'reguler_records' => $regulerRecords,
                'months' => $months,
                'total_students' => count($groupStudents),
                'tuntas_count' => $isTahfizhProgram 
                    ? collect($tahfizhRecords)->where('is_tuntas', true)->count()
                    : collect($regulerRecords)->where('is_tuntas', true)->count()
            ];
        }

        return view('reports.quarterly', [
            'classRooms' => $classRooms,
            'selectedClass' => $selectedClass,
            'isTahfizhProgram' => $isTahfizhProgram,
            'academicYear' => $academicYear,
            'selectedTerm' => $selectedTerm,
            'halaqahData' => $halaqahData,
            'months' => $months
        ]);
    }
}
