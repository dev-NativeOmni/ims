<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get($key, $default = null)
    {
        return Cache::rememberForever("setting:{$key}", function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function set($key, $value)
    {
        $setting = self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");

        return $setting;
    }

    /**
     * Returns the 4 adab categories with their questions.
     * Each category has a 'title', 'desc', and 'questions' (array of strings).
     */
    public static function getAdabQuestions(): array
    {
        $default = [
            [
                'title' => '🕋 Adab Kepada Allah',
                'desc' => 'Menjaga hubungan ketakwaan dan ibadah sehari-hari kepada Allah Subhanahu wa Ta\'ala.',
                'questions' => [
                    'Apakah Anda melaksanakan shalat fardhu tepat waktu hari ini?',
                    'Apakah Anda mengawali aktivitas hari ini dengan membaca Basmalah?',
                    'Apakah Anda selalu berdoa setelah selesai shalat fardhu hari ini?',
                    'Apakah Anda bersyukur atas segala nikmat yang Anda rasakan hari ini?',
                    'Apakah Anda menyempatkan diri berdzikir (membaca tasbih/tahmid/takbir) hari ini?',
                ],
            ],
            [
                'title' => '👥 Adab Kepada Sesama Teman',
                'desc' => 'Menjalin hubungan yang baik, saling menghormati, dan berlaku adil terhadap sesama.',
                'questions' => [
                    'Apakah Anda bersikap sopan dan santun kepada teman-teman hari ini?',
                    'Apakah Anda menghindari perkataan kasar, mengejek, atau menyakiti teman?',
                    'Apakah Anda membantu teman yang membutuhkan pertolongan hari ini?',
                    'Apakah Anda menjaga amanah dan kejujuran dalam pergaulan hari ini?',
                    'Apakah Anda ikut menjaga kerukunan dan ketenangan di lingkungan asrama/kelas?',
                ],
            ],
            [
                'title' => '📚 Adab Ketika Belajar',
                'desc' => 'Menjaga ketertiban, kebersihan, kepatuhan, dan doa dalam menuntut ilmu.',
                'questions' => [
                    'Apakah Anda datang/masuk kelas tepat waktu dan menyiapkan peralatan belajar?',
                    'Apakah Anda menyimak penjelasan guru dengan khusyuk dan tidak mengobrol saat pelajaran?',
                    'Apakah Anda mencatat materi pelajaran dengan rapi dan tertib?',
                    'Apakah Anda mengawali dan mengakhiri belajar dengan berdoa?',
                    'Apakah Anda menjaga kebersihan dan kerapian tempat belajar Anda?',
                ],
            ],
            [
                'title' => '🌿 Adab terhadap Lingkungan',
                'desc' => 'Menjaga kebersihan, ketertiban, dan kelestarian lingkungan sebagai bentuk syukur kepada Allah.',
                'questions' => [
                    'Apakah Anda membuang sampah pada tempatnya hari ini?',
                    'Apakah Anda menjaga kebersihan kamar/asrama Anda hari ini?',
                    'Apakah Anda turut merawat fasilitas sekolah/pesantren dengan baik?',
                    'Apakah Anda bersikap hemat dalam menggunakan air, listrik, atau barang fasilitas?',
                    'Apakah Anda tidak merusak atau mencoret-coret benda/properti milik bersama?',
                ],
            ],
        ];

        $json = self::get('adab_questions');
        if ($json) {
            $decoded = json_decode($json, true);
            if (is_array($decoded) && count($decoded) >= 1) {
                return $decoded;
            }
        }

        return $default;
    }

    /**
     * Get list of national holidays (tanggal merah) for Indonesia.
     */
    public static function getNationalHolidays(int $year): array
    {
        $custom = self::get("national_holidays_{$year}");
        if ($custom) {
            $decoded = json_decode($custom, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        // Default Indonesian national holidays (fixed dates + common movable holidays estimate)
        return [
            "{$year}-01-01", // Tahun Baru Masehi
            "{$year}-05-01", // Hari Buruh
            "{$year}-06-01", // Hari Lahir Pancasila
            "{$year}-08-17", // Hari Kemerdekaan RI
            "{$year}-12-25", // Hari Natal
        ];
    }

    /**
     * Calculate count of effective workdays (Senin-Jumat, excluding national holidays) for a month.
     */
    public static function getEffectiveDaysCount(int $year, int $month, ?string $untilDate = null): int
    {
        $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfDay();
        $daysInMonth = $startDate->daysInMonth;

        $now = \Carbon\Carbon::now();
        $isCurrentMonth = ($year === (int) $now->format('Y') && $month === (int) $now->format('n'));

        if ($untilDate) {
            $endDate = \Carbon\Carbon::parse($untilDate)->endOfDay();
        } elseif ($isCurrentMonth) {
            $endDate = $now->copy()->endOfDay();
        } else {
            $endDate = \Carbon\Carbon::createFromDate($year, $month, $daysInMonth)->endOfDay();
        }

        $holidays = self::getNationalHolidays($year);
        $effectiveCount = 0;

        $current = $startDate->copy();
        while ($current->lte($endDate) && $current->month === $month) {
            // Check if weekday (Monday=1 to Friday=5) and not in national holidays
            if ($current->isWeekday() && ! in_array($current->toDateString(), $holidays, true)) {
                $effectiveCount++;
            }
            $current->addDay();
        }

        return max(1, $effectiveCount);
    }

    /**
     * Get student adab questionnaire attendance details for a month.
     */
    public static function getStudentAdabAttendanceDetails(int $studentId, int $year, int $month): array
    {
        $startDate = \Carbon\Carbon::createFromDate($year, $month, 1)->toDateString();
        $endDate = \Carbon\Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        $effectiveDaysTotal = self::getEffectiveDaysCount($year, $month);

        $holidays = self::getNationalHolidays($year);

        // Fetch distinct assessment dates filled by student in effective days
        $filledDates = \App\Models\AdabRecord::where('student_id', $studentId)
            ->whereBetween('assessment_date', [$startDate, $endDate])
            ->pluck('assessment_date')
            ->unique();

        $effectiveDaysFilled = 0;
        foreach ($filledDates as $dateStr) {
            $cDate = \Carbon\Carbon::parse($dateStr);
            if ($cDate->isWeekday() && ! in_array($cDate->toDateString(), $holidays, true)) {
                $effectiveDaysFilled++;
            }
        }

        $attendanceRate = round(($effectiveDaysFilled / $effectiveDaysTotal) * 100, 1);

        return [
            'effective_days_total' => $effectiveDaysTotal,
            'effective_days_filled' => $effectiveDaysFilled,
            'attendance_rate' => min(100.0, $attendanceRate),
        ];
    }

    /**
     * Calculate composite adab score: 40% questionnaire attendance + 60% mentor score.
     */
    public static function calculateAdabScore(int $studentId, int $year, int $month): array
    {
        $attendance = self::getStudentAdabAttendanceDetails($studentId, $year, $month);
        $attendanceRate = $attendance['attendance_rate'];

        $mentorAssessment = \App\Models\AdabMentorAssessment::where('student_id', $studentId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if (! $mentorAssessment) {
            // Fallback: try latest available mentor assessment or use attendance rate
            $mentorAssessment = \App\Models\AdabMentorAssessment::where('student_id', $studentId)
                ->orderByDesc('year')->orderByDesc('month')
                ->first();
        }

        $mentorScore = $mentorAssessment ? (float) $mentorAssessment->mentor_score : null;

        if ($mentorScore !== null) {
            $finalScore = round(($attendanceRate * 0.40) + ($mentorScore * 0.60), 1);
        } else {
            $finalScore = $attendanceRate;
        }

        $grade = self::getAdabGrade($finalScore);
        $gradeLabel = self::getAdabGradeLabel($grade);

        return [
            'attendance_rate' => $attendanceRate,
            'effective_days_filled' => $attendance['effective_days_filled'],
            'effective_days_total' => $attendance['effective_days_total'],
            'mentor_score' => $mentorScore,
            'final_score' => $finalScore,
            'grade' => $grade,
            'grade_label' => $gradeLabel,
        ];
    }

    /**
     * Convert a 0-100 percentage score to a letter grade.
     */
    public static function getAdabGrade(float $score): string
    {
        if ($score >= 90) {
            return 'A';
        }
        if ($score >= 80) {
            return 'B';
        }
        if ($score >= 70) {
            return 'C';
        }
        if ($score >= 60) {
            return 'D';
        }

        return 'E';
    }

    /**
     * Get grade label in Bahasa Indonesia.
     */
    public static function getAdabGradeLabel(string $grade): string
    {
        return match ($grade) {
            'A' => 'Mumtaz (Sangat Baik)',
            'B' => 'Jayyid Jiddan (Baik Sekali)',
            'C' => 'Jayyid (Baik)',
            'D' => 'Maqbul (Cukup)',
            default => 'Dha\'if (Kurang)',
        };
    }
}
