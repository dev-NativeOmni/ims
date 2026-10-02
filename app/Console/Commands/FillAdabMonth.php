<?php

namespace App\Console\Commands;

use App\Models\AdabMentorAssessment;
use App\Models\AdabRecord;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Lengkapi Adab satu bulan untuk semua murid aktif (mis. bulan awal sebelum aplikasi rutin dipakai):
 * - kuisioner harian di setiap hari efektif Adab yang masih kosong diisi semua "Ya" (nilai 100);
 *   kuisioner yang sudah diisi murid tidak disentuh;
 * - nilai pendamping bulan itu diisi --nilai-pendamping (bawaan 100), nilai lama dicatat di keterangan.
 * Semua isian ditandai MARKER sehingga --batalkan bisa menghapus/mengembalikannya.
 */
class FillAdabMonth extends Command
{
    public const MARKER = '[isi-otomatis]';

    protected $signature = 'tad:isi-adab-bulan
        {bulan : Bulan yang dilengkapi, format YYYY-MM (mis. 2026-07)}
        {--nilai-pendamping=100 : Nilai pendamping bulan itu untuk semua murid}
        {--oleh= : Username pencatat (bawaan: Super Admin pertama)}
        {--batalkan : Hapus kuisioner otomatis & kembalikan nilai pendamping bulan itu}
        {--dry-run : Tampilkan yang akan diubah tanpa menyimpan}
        {--force : Jalankan tanpa pertanyaan konfirmasi}';

    protected $description = 'Lengkapi kuisioner Adab harian & nilai pendamping satu bulan untuk semua murid aktif.';

    public function handle(): int
    {
        if (! preg_match('/^(\d{4})-(\d{2})$/', (string) $this->argument('bulan'), $match)) {
            $this->error('Format bulan harus YYYY-MM, mis. 2026-07.');

            return self::FAILURE;
        }
        [$year, $month] = [(int) $match[1], (int) $match[2]];
        $monthStart = Carbon::create($year, $month, 1);
        $label = $monthStart->locale('id')->translatedFormat('F Y');

        return $this->option('batalkan') ? $this->undo($year, $month, $label) : $this->fill($year, $month, $label);
    }

    private function fill(int $year, int $month, string $label): int
    {
        $score = (int) $this->option('nilai-pendamping');
        if ($score < 0 || $score > 100) {
            $this->error('Nilai pendamping harus 0-100.');

            return self::FAILURE;
        }

        $recorder = $this->option('oleh')
            ? User::where('username', $this->option('oleh'))->first()
            : User::whereHas('role', fn ($q) => $q->where('name', 'super_admin'))->orderBy('id')->first();
        if (! $recorder) {
            $this->error('Pencatat tidak ditemukan. Isi --oleh=<username>.');

            return self::FAILURE;
        }

        $today = today()->toDateString();
        $dates = collect(array_keys(Setting::getEffectiveDatesSet($year, $month)))->filter(fn ($d) => $d <= $today)->sort()->values();
        $students = Student::query()->where('status', 'active')->whereNotNull('class_room_id')->orderBy('name')->get(['id', 'name']);
        $studentIds = $students->pluck('id');

        $existing = AdabRecord::query()->whereIn('student_id', $studentIds)
            // whereDate: assessment_date bisa tersimpan dengan jam ("Y-m-d 00:00:00").
            ->whereDate('assessment_date', '>=', Carbon::create($year, $month, 1)->toDateString())
            ->whereDate('assessment_date', '<=', Carbon::create($year, $month, 1)->endOfMonth()->toDateString())
            ->get(['student_id', 'assessment_date'])
            ->map(fn ($r) => $r->student_id.'|'.substr((string) $r->assessment_date, 0, 10))
            ->flip();
        $missing = $students->flatMap(fn ($s) => $dates->reject(fn ($d) => $existing->has($s->id.'|'.$d))->map(fn ($d) => [$s->id, $d]));

        $assessments = AdabMentorAssessment::query()->whereIn('student_id', $studentIds)->where('year', $year)->where('month', $month)->get()->keyBy('student_id');
        $changedScores = $assessments->filter(fn ($a) => (int) $a->mentor_score !== $score)->count();

        $this->table(['Data', $label], [
            ['Murid aktif', $students->count()],
            ['Hari efektif Adab (s.d. hari ini)', $dates->count()],
            ['Kuisioner sudah ada (dibiarkan)', $existing->count()],
            ['Kuisioner akan diisi (semua "Ya", nilai 100)', $missing->count()],
            ["Nilai pendamping baru ({$score})", $students->count() - $assessments->count()],
            ["Nilai pendamping diubah ke {$score}", $changedScores],
            ['Dicatat atas nama', $recorder->name],
        ]);

        if ($this->option('dry-run')) {
            $this->warn('Mode uji coba: tidak ada yang disimpan.');

            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm("Lengkapi Adab {$label} seperti di atas?")) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        // Semua butir kuisioner (kategori dari Pengaturan Adab) dijawab "Ya".
        $answers = collect(Setting::getAdabQuestions())
            ->mapWithKeys(fn ($cat, $idx) => ["cat_{$idx}" => array_fill(0, count($cat['questions'] ?? []), true)])
            ->all();

        DB::transaction(function () use ($missing, $answers, $recorder, $students, $assessments, $year, $month, $label, $score) {
            foreach ($missing as [$studentId, $date]) {
                AdabRecord::create([
                    'student_id' => $studentId,
                    'evaluator_id' => $recorder->id,
                    'assessment_date' => $date,
                    'answers' => $answers,
                    'student_score' => 100,
                    'total_score' => 100,
                    'notes' => self::MARKER.' Dilengkapi otomatis (tad:isi-adab-bulan).',
                ]);
            }

            foreach ($students as $student) {
                $current = $assessments->get($student->id);
                if ($current && str_starts_with((string) $current->notes, self::MARKER)) {
                    // Sudah pernah diisi otomatis: cukup samakan nilainya, catatan nilai asli dipertahankan.
                    $current->update(['mentor_score' => $score]);

                    continue;
                }
                $previous = $current ? self::MARKER." sebelumnya={$current->mentor_score} ".(string) $current->notes : self::MARKER;
                AdabMentorAssessment::updateOrCreate(
                    ['student_id' => $student->id, 'year' => $year, 'month' => $month],
                    ['mentor_id' => $recorder->id, 'mentor_score' => $score, 'period_label' => $label, 'notes' => trim($previous)]
                );
            }
        });

        $this->info("Adab {$label} dilengkapi: {$missing->count()} kuisioner diisi, nilai pendamping {$score} untuk {$students->count()} murid.");

        return self::SUCCESS;
    }

    private function undo(int $year, int $month, string $label): int
    {
        $records = AdabRecord::query()
            ->whereDate('assessment_date', '>=', Carbon::create($year, $month, 1)->toDateString())
            ->whereDate('assessment_date', '<=', Carbon::create($year, $month, 1)->endOfMonth()->toDateString())
            ->where('notes', 'like', self::MARKER.'%');
        $assessments = AdabMentorAssessment::query()->where('year', $year)->where('month', $month)->where('notes', 'like', self::MARKER.'%')->get();
        $restorable = $assessments->filter(fn ($a) => preg_match('/sebelumnya=(\d+)/', (string) $a->notes));

        $this->table(['Data', $label], [
            ['Kuisioner otomatis dihapus', (clone $records)->count()],
            ['Nilai pendamping dikembalikan ke nilai lama', $restorable->count()],
            ['Nilai pendamping otomatis dihapus (sebelumnya kosong)', $assessments->count() - $restorable->count()],
        ]);

        if ($this->option('dry-run')) {
            $this->warn('Mode uji coba: tidak ada yang diubah.');

            return self::SUCCESS;
        }
        if (! $this->option('force') && ! $this->confirm("Batalkan isian otomatis Adab {$label}?")) {
            $this->line('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($records, $assessments) {
            $records->delete();
            foreach ($assessments as $assessment) {
                if (preg_match('/^'.preg_quote(self::MARKER, '/').' sebelumnya=(\d+) ?(.*)$/s', (string) $assessment->notes, $old)) {
                    $assessment->update(['mentor_score' => (int) $old[1], 'notes' => $old[2] !== '' ? $old[2] : null]);
                } else {
                    $assessment->delete();
                }
            }
        });

        $this->info("Isian otomatis Adab {$label} dibatalkan.");

        return self::SUCCESS;
    }
}
