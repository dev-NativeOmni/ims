<?php

namespace App\Models;

use App\Services\SchoolCalendar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected static array $studentAdabScoreCache = [];

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
        self::$studentAdabScoreCache = [];

        return $setting;
    }

    /**
     * Dipanggil SchoolCalendar saat kalender berubah: skor adab bergantung pada hari efektif.
     */
    public static function flushCalendarCaches(): void
    {
        self::$studentAdabScoreCache = [];
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
     * Tanggal Libur Total (Tahfizh & Adab) setahun. Lihat App\Services\SchoolCalendar.
     */
    public static function getNationalHolidays(int $year): array
    {
        return app(SchoolCalendar::class)->totalHolidays($year);
    }

    /**
     * Libur Tahfizh khusus kelas ('Y-m-d' => [class_room_id, ...]). Lihat App\Services\SchoolCalendar.
     */
    public static function getClassHolidays(int $year): array
    {
        return app(SchoolCalendar::class)->classDays($year);
    }

    /**
     * Hari efektif kuisioner Adab: hari pengisian Adab (Kalender), bukan libur Adab (lihat SchoolCalendar).
     */
    public static function isEffectiveAdabDay(Carbon $date): bool
    {
        return app(SchoolCalendar::class)->isAdabEffectiveDay($date);
    }

    /**
     * Get associative set of effective dates for a given month ['YYYY-MM-DD' => true] for O(1) lookup.
     */
    public static function getEffectiveDatesSet(int $year, int $month, ?string $untilDate = null): array
    {
        return app(SchoolCalendar::class)->adabEffectiveDates($year, $month, $untilDate);
    }

    /**
     * Jumlah hari efektif kuisioner Adab dalam sebulan (lihat SchoolCalendar::isAdabEffectiveDay).
     */
    public static function getEffectiveDaysCount(int $year, int $month, ?string $untilDate = null): int
    {
        return max(1, count(self::getEffectiveDatesSet($year, $month, $untilDate)));
    }

    /**
     * Get student adab questionnaire attendance details for a month.
     */
    public static function getStudentAdabAttendanceDetails(int $studentId, int $year, int $month): array
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->toDateString();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

        $effectiveDaysTotal = self::getEffectiveDaysCount($year, $month);
        $effectiveDatesSet = self::getEffectiveDatesSet($year, $month);

        // Fetch distinct assessment dates filled by student in effective days
        $filledDates = AdabRecord::where('student_id', $studentId)
            ->whereBetween('assessment_date', [$startDate, $endDate])
            ->pluck('assessment_date')
            ->unique();

        $effectiveDaysFilled = 0;
        foreach ($filledDates as $dateStr) {
            $d = is_string($dateStr) ? substr($dateStr, 0, 10) : (is_object($dateStr) ? $dateStr->format('Y-m-d') : '');
            if (isset($effectiveDatesSet[$d])) {
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
     * Aturan penilaian Adab bawaan; bisa diubah di Pengaturan Adab (adabScoring()).
     * - attendance_weight: bobot (%) kerajinan pengisian kuisioner; sisanya (100 - bobot) nilai pendamping.
     * - thresholds: nilai minimal tiap predikat (A..D); di bawah D = E. Juga dipakai predikat nilai Tahfizh rapor.
     * - descriptions: deskripsi Adab di rapor per predikat.
     */
    public const ADAB_SCORING_DEFAULTS = [
        'attendance_weight' => 40,
        'thresholds' => ['A' => 90, 'B' => 80, 'C' => 70, 'D' => 60],
        'descriptions' => [
            'A' => 'Sangat baik (Mumtaz), konsisten beribadah kepada Allah, berperilaku sopan terhadap sesama teman, menerapkan adab belajar secara tertib dan disiplin, serta menjaga kebersihan lingkungan dengan sangat baik.',
            'B' => 'Baik sekali (Jayyid Jiddan), rutin melaksanakan ibadah harian, bersikap sopan kepada teman, tertib dalam mengikuti pelajaran, dan turut menjaga kebersihan lingkungan dengan baik.',
            'C' => 'Baik (Jayyid), menunjukkan kesopanan kepada guru dan teman, mengikuti kegiatan belajar dengan tertib, dan menjaga kebersihan diri serta lingkungan.',
            'D' => 'Cukup (Maqbul), sudah berusaha membiasakan adab harian dengan cukup baik, namun masih memerlukan pengawasan dan motivasi berkala agar lebih konsisten.',
            'E' => "Kurang (Dha'if), memerlukan pembinaan moral intensif serta bimbingan khusus baik di sekolah maupun asrama untuk meningkatkan kedisiplinan dan adab sehari-hari.",
        ],
    ];

    /**
     * Aturan penilaian Adab tersimpan (Pengaturan Adab), dilengkapi nilai bawaan.
     *
     * @return array{attendance_weight: int, thresholds: array<string, int>, descriptions: array<string, string>}
     */
    public static function adabScoring(): array
    {
        $saved = json_decode((string) self::get('adab_scoring'), true) ?: [];
        $defaults = self::ADAB_SCORING_DEFAULTS;

        return [
            'attendance_weight' => (int) ($saved['attendance_weight'] ?? $defaults['attendance_weight']),
            'thresholds' => collect($defaults['thresholds'])->map(fn ($default, $grade) => (int) ($saved['thresholds'][$grade] ?? $default))->all(),
            'descriptions' => collect($defaults['descriptions'])->map(fn ($default, $grade) => trim((string) ($saved['descriptions'][$grade] ?? '')) ?: $default)->all(),
        ];
    }

    /**
     * Nilai akhir Adab = bobot kerajinan x kehadiran kuisioner + sisa bobot x nilai pendamping.
     * Belum ada nilai pendamping = nilai kehadiran saja. Satu-satunya tempat rumus ini.
     */
    public static function adabCompositeScore(float $attendanceRate, ?float $mentorScore): float
    {
        if ($mentorScore === null) {
            return $attendanceRate;
        }

        $weight = self::adabScoring()['attendance_weight'] / 100;

        return round(($attendanceRate * $weight) + ($mentorScore * (1 - $weight)), 1);
    }

    public static function adabDescription(string $grade): string
    {
        return self::adabScoring()['descriptions'][$grade] ?? self::ADAB_SCORING_DEFAULTS['descriptions']['E'];
    }

    /**
     * Calculate composite adab score (bobot dari Pengaturan Adab, lihat adabCompositeScore()).
     */
    public static function calculateAdabScore(int $studentId, int $year, int $month): array
    {
        $cacheKey = "{$studentId}_{$year}_{$month}";
        if (isset(self::$studentAdabScoreCache[$cacheKey])) {
            return self::$studentAdabScoreCache[$cacheKey];
        }

        $attendance = self::getStudentAdabAttendanceDetails($studentId, $year, $month);
        $attendanceRate = $attendance['attendance_rate'];

        $mentorAssessment = AdabMentorAssessment::where('student_id', $studentId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if (! $mentorAssessment) {
            // Fallback: try latest available mentor assessment or use attendance rate
            $mentorAssessment = AdabMentorAssessment::where('student_id', $studentId)
                ->orderByDesc('year')->orderByDesc('month')
                ->first();
        }

        $mentorScore = $mentorAssessment ? (float) $mentorAssessment->mentor_score : null;

        $finalScore = self::adabCompositeScore($attendanceRate, $mentorScore);

        $grade = self::getAdabGrade($finalScore);
        $gradeLabel = self::getAdabGradeLabel($grade);

        $result = [
            'attendance_rate' => $attendanceRate,
            'effective_days_filled' => $attendance['effective_days_filled'],
            'effective_days_total' => $attendance['effective_days_total'],
            'mentor_score' => $mentorScore,
            'final_score' => $finalScore,
            'grade' => $grade,
            'grade_label' => $gradeLabel,
        ];

        return self::$studentAdabScoreCache[$cacheKey] = $result;
    }

    /**
     * Convert a 0-100 percentage score to a letter grade.
     */
    public static function getAdabGrade(float $score): string
    {
        foreach (self::adabScoring()['thresholds'] as $grade => $minimum) {
            if ($score >= $minimum) {
                return $grade;
            }
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

    /**
     * Get hafalan targets configuration per grade level and program.
     */
    public static function getHafalanTargetsConfig(): array
    {
        $default = [
            'grade_10' => [
                'tahfizh' => [
                    'target_juz_count' => 4,
                    'mode' => 'specific',
                    'specific_juz' => [30, 29, 28, 1],
                ],
                'reguler' => [
                    'target_juz_count' => 2,
                    'mode' => 'specific',
                    'specific_juz' => [30, 29],
                ],
            ],
            'grade_11' => [
                'tahfizh' => [
                    'target_juz_count' => 4,
                    'mode' => 'any',
                    'specific_juz' => [],
                ],
                'reguler' => [
                    'target_juz_count' => 2,
                    'mode' => 'any',
                    'specific_juz' => [],
                ],
            ],
            'grade_12' => [
                'tahfizh' => [
                    'target_juz_count' => 4,
                    'mode' => 'any',
                    'specific_juz' => [],
                ],
                'reguler' => [
                    'target_juz_count' => 2,
                    'mode' => 'any',
                    'specific_juz' => [],
                ],
            ],
        ];

        $val = self::get('hafalan_targets_config');
        if (! $val) {
            return $default;
        }

        $decoded = is_string($val) ? json_decode($val, true) : $val;

        return is_array($decoded) ? array_replace_recursive($default, $decoded) : $default;
    }

    /**
     * Get tahfizh final-grade scoring configuration: how much of the
     * combined score (max 100) comes from target completion vs the exam,
     * and how many points an incomplete target still earns.
     */
    public static function getTahfizhScoringConfig(): array
    {
        $default = [
            'target_weight' => 50,
            'exam_weight' => 50,
            'target_incomplete_score' => 40,
        ];

        $val = self::get('tahfizh_scoring_config');
        if (! $val) {
            return $default;
        }

        $decoded = is_string($val) ? json_decode($val, true) : $val;

        return is_array($decoded) ? array_replace_recursive($default, $decoded) : $default;
    }

    /**
     * Combine a student's latest target-completion status with their
     * latest exam score into the final tahfizh grade (max 100) shown on
     * the report card.
     */
    public static function calculateTahfizhScore(Student $student): array
    {
        $config = self::getTahfizhScoringConfig();

        $latestTarget = HafalanTarget::query()
            ->where('student_id', $student->id)
            ->whereNotNull('surah_id')
            ->orderByDesc('target_date')
            ->orderByDesc('id')
            ->first();

        $targetScore = null;
        if ($latestTarget) {
            $targetScore = $latestTarget->status === 'completed'
                ? $config['target_weight']
                : $config['target_incomplete_score'];
            $targetScore = min($targetScore, $config['target_weight']);
        }

        $latestExam = TahfizhExam::query()
            ->where('student_id', $student->id)
            ->orderByDesc('exam_date')
            ->orderByDesc('id')
            ->first();

        $examScore = null;
        if ($latestExam) {
            // Defensive cap: exams recorded before the scoring simplification
            // may still hold a legacy 0-100 average-of-5 value.
            $examScore = min((float) $latestExam->total_score, $config['exam_weight']);
        }

        return [
            'target_score' => $targetScore,
            'target_weight' => $config['target_weight'],
            'target_status' => $latestTarget?->status,
            'target_label' => $latestTarget ? ($latestTarget->status === 'completed' ? 'Tuntas' : 'Belum Tuntas') : null,
            'exam_score' => $examScore,
            'exam_weight' => $config['exam_weight'],
            'exam_date' => $latestExam?->exam_date,
            'has_target' => (bool) $latestTarget,
            'has_exam' => (bool) $latestExam,
            'final_score' => round(($targetScore ?? 0) + ($examScore ?? 0), 1),
        ];
    }
}
