<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\Student;
use Illuminate\Http\Request;

class QuarterlyReportController extends Controller
{
    public function index(Request $request)
    {
        $classRooms = ClassRoom::query()->orderBy('name')->get();
        $selectedClassId = $request->input('class_room_id', $classRooms->first()?->id);
        $selectedClass = $classRooms->firstWhere('id', $selectedClassId);
        
        $academicYear = $request->input('academic_year', '2025/2026');
        $selectedTerm = $request->input('term', '1'); // 1, 2, 3, 4

        // Get actual active students in the selected class
        $students = Student::query()
            ->with(['classRoom', 'teacher.user'])
            ->where('class_room_id', $selectedClassId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        // If no students in class, grab some random ones for demo
        if ($students->isEmpty()) {
            $students = Student::query()
                ->with(['classRoom', 'teacher.user'])
                ->where('status', 'active')
                ->orderBy('name')
                ->take(8)
                ->get();
        }

        // Mock data mapping for Kelas Tahfizh
        $tahfizhMockData = [];
        $surahs = ['Al-Mulk', 'Al-Qalam', 'Al-Haaqqa', 'Al-Ma\'arij', 'Nuh', 'Al-Jinn', 'Al-Muzzammil', 'Al-Muddathir', 'Al-Qiyamah', 'Al-Insan'];
        
        foreach ($students as $idx => $student) {
            $targetSurah = $surahs[$idx % count($surahs)];
            $capaianSurah = $targetSurah;
            $targetAyat = ($idx % 2 === 0) ? '30' : '40';
            $capaianAyat = ($idx % 3 === 0) ? (int)$targetAyat - 5 : $targetAyat;
            $isTuntas = $capaianAyat == $targetAyat;

            $tahfizhMockData[] = [
                'no' => $idx + 1,
                'name' => $student->name,
                'nis' => $student->student_number ?? '4407-2526' . sprintf('%03d', $idx + 1),
                'halaqah' => ucfirst($student->tahfizh_level ?? 'reguler'),
                'musyrif' => $student->teacher?->user?->name ?? 'Ust. Fuad Faris Ghazi',
                'target_surah' => $targetSurah,
                'target_ayat' => $targetAyat,
                'capaian_surah' => $capaianSurah,
                'capaian_ayat' => $capaianAyat,
                'is_tuntas' => $isTuntas,
                'kehadiran' => [
                    'alpa' => $idx % 4 === 0 ? 1 : 0,
                    'izin' => $idx % 5 === 0 ? 2 : 0,
                    'sakit' => $idx % 6 === 0 ? 1 : 0,
                ],
                'pelanggaran' => $idx % 7 === 0 ? 1 : 0,
            ];
        }

        // Mock data mapping for Kelas Reguler (with Pekan 1 - Pekan 5)
        $regulerMockData = [];
        foreach ($students as $idx => $student) {
            $level = ucfirst($student->tahfizh_level ?? 'reguler');
            $targetLines = $level === 'Akselerasi' ? 14 : ($level === 'Tahsin' ? 6 : 10);
            
            // Generate weekly metrics
            $pekan1 = [
                'surah' => 'Al-Qalam',
                'ayat' => '1-10',
                'baris' => $targetLines,
                'nilai' => 'A',
                'kehadiran' => 'Hadir'
            ];
            $pekan2 = [
                'surah' => 'Al-Qalam',
                'ayat' => '11-20',
                'baris' => $targetLines,
                'nilai' => 'A',
                'kehadiran' => 'Hadir'
            ];
            $pekan3 = [
                'surah' => 'Al-Qalam',
                'ayat' => '21-30',
                'baris' => $targetLines,
                'nilai' => $idx % 3 === 0 ? 'B+' : 'A',
                'kehadiran' => $idx % 5 === 0 ? 'Izin' : 'Hadir'
            ];
            $pekan4 = [
                'surah' => 'Al-Qalam',
                'ayat' => '31-40',
                'baris' => $targetLines,
                'nilai' => 'A',
                'kehadiran' => 'Hadir'
            ];
            $pekan5 = [
                'surah' => 'Al-Qalam',
                'ayat' => '41-52',
                'baris' => $idx % 2 === 0 ? $targetLines + 2 : $targetLines,
                'nilai' => 'A',
                'kehadiran' => $idx % 4 === 0 ? 'Sakit' : 'Hadir'
            ];

            // Sum totals
            $totalLines = $pekan1['baris'] + $pekan2['baris'] + ($pekan3['kehadiran'] == 'Hadir' ? $pekan3['baris'] : 0) + $pekan4['baris'] + ($pekan5['kehadiran'] == 'Hadir' ? $pekan5['baris'] : 0);
            $totalHadir = ($pekan1['kehadiran'] == 'Hadir' ? 1 : 0) + ($pekan2['kehadiran'] == 'Hadir' ? 1 : 0) + ($pekan3['kehadiran'] == 'Hadir' ? 1 : 0) + ($pekan4['kehadiran'] == 'Hadir' ? 1 : 0) + ($pekan5['kehadiran'] == 'Hadir' ? 1 : 0);
            
            $regulerMockData[] = [
                'no' => $idx + 1,
                'name' => $student->name,
                'nis' => $student->student_number ?? '4407-2526' . sprintf('%03d', $idx + 1),
                'halaqah' => $level,
                'musyrif' => $student->teacher?->user?->name ?? 'Ust. Fuad Faris Ghazi',
                'pekan1' => $pekan1,
                'pekan2' => $pekan2,
                'pekan3' => $pekan3,
                'pekan4' => $pekan4,
                'pekan5' => $pekan5,
                'total_baris' => $totalLines,
                'rekap_kehadiran' => [
                    'hadir' => $totalHadir,
                    'izin' => ($pekan3['kehadiran'] == 'Izin' ? 1 : 0),
                    'sakit' => ($pekan5['kehadiran'] == 'Sakit' ? 1 : 0),
                    'alpa' => 0
                ]
            ];
        }

        return view('reports.quarterly', [
            'classRooms' => $classRooms,
            'selectedClass' => $selectedClass,
            'academicYear' => $academicYear,
            'selectedTerm' => $selectedTerm,
            'tahfizhMockData' => $tahfizhMockData,
            'regulerMockData' => $regulerMockData,
            'tuntasCount' => collect($tahfizhMockData)->where('is_tuntas', true)->count(),
            'tidakTuntasCount' => collect($tahfizhMockData)->where('is_tuntas', false)->count(),
        ]);
    }
}
