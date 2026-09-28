<?php

namespace App\Observers;

use App\Models\HafalanRecord;
use App\Models\HafalanRecordSurah;
use App\Models\UmmiRecord;
use App\Models\UmmiRecordSurah;
use App\Services\HafalanTargetAutoCompletionService;
use Illuminate\Database\Eloquent\Model;

/**
 * Begitu setoran (hafalan maupun Ummi) dibuat/diubah/dihapus, status target aktif murid itu langsung dievaluasi
 * (Selesai bila tercapai, Terlewat bila deadline lewat) tanpa menunggu tugas malam.
 * Evaluasi ditunda sampai akhir request dan digabung per murid, supaya simpan massal
 * (spreadsheet) tidak mengevaluasi berulang. Di luar request web (console/seeder) tidak berjalan.
 */
class HafalanTargetStatusObserver
{
    /** @var array<int, true> */
    private static array $pending = [];

    private static ?int $registeredFor = null;

    public function saved(Model $model): void
    {
        $this->mark($model);
    }

    public function deleted(Model $model): void
    {
        $this->mark($model);
    }

    public function restored(Model $model): void
    {
        $this->mark($model);
    }

    private function mark(Model $model): void
    {
        if (! app()->bound('request') || ! request()->route()) {
            return;
        }

        $studentId = match (true) {
            $model instanceof HafalanRecordSurah => (int) HafalanRecord::withTrashed()->whereKey($model->hafalan_record_id)->value('student_id'),
            $model instanceof UmmiRecordSurah => (int) UmmiRecord::query()->whereKey($model->ummi_record_id)->value('student_id'),
            default => (int) $model->student_id,
        };

        if (! $studentId) {
            return;
        }

        // Satu callback per instance aplikasi (instance baru per test/request worker).
        if (self::$registeredFor !== spl_object_id(app())) {
            self::$registeredFor = spl_object_id(app());
            self::$pending = [];
            app()->terminating(fn () => self::flush());
        }

        self::$pending[$studentId] = true;
    }

    public static function flush(): void
    {
        $studentIds = array_keys(self::$pending);
        self::$pending = [];
        self::$registeredFor = null;

        if ($studentIds === []) {
            return;
        }

        try {
            app(HafalanTargetAutoCompletionService::class)->syncStudents($studentIds);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
