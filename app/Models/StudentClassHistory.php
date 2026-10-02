<?php

namespace App\Models;

use App\Support\AcademicYear;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Riwayat kelas santri: santri di kelas ini dari start_date s.d. end_date (null = sampai sekarang).
 *
 * Aturan (lihat docs/riwayat-kelas.md):
 * - Dicatat otomatis oleh Student (event created/updated) setiap class_room_id berubah: periode lama
 *   ditutup kemarin, periode baru dibuka hari ini. Perubahan di hari yang sama dengan pembukaan
 *   dianggap koreksi input, bukan pindah kelas.
 * - Laporan per kelas untuk suatu periode memakai kelas santri pada "tanggal acuan" periode itu
 *   (akhir periode, atau hari ini bila periode masih berjalan) -- lihat referenceDate().
 */
class StudentClassHistory extends Model
{
    protected $fillable = ['student_id', 'class_room_id', 'start_date', 'end_date'];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    /** Riwayat yang berlaku pada tanggal itu. */
    public function scopeActiveOn(Builder $query, CarbonInterface|string $date): Builder
    {
        $day = Carbon::parse($date)->toDateString();

        return $query->whereDate('start_date', '<=', $day)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $day));
    }

    public function coversDate(CarbonInterface|string $date): bool
    {
        $day = Carbon::parse($date)->startOfDay();

        return $this->start_date->lte($day) && ($this->end_date === null || $this->end_date->gte($day));
    }

    /**
     * Tanggal acuan kelas untuk periode yang berakhir pada $periodEnd: akhir periode, atau hari ini
     * bila periode belum selesai.
     */
    public static function referenceDate(CarbonInterface|string $periodEnd): Carbon
    {
        return Carbon::parse($periodEnd)->startOfDay()->min(today());
    }

    /**
     * Catat kelas santri saat ini ke riwayat (dipanggil dari event Student).
     */
    public static function record(Student $student): void
    {
        $today = today();
        $open = static::query()->where('student_id', $student->id)->whereNull('end_date')->orderByDesc('start_date')->first();

        if ($open && $open->class_room_id === $student->class_room_id) {
            return;
        }

        // Riwayat dibuka hari ini juga: anggap salah pilih kelas lalu dikoreksi, bukan pindah kelas.
        if ($open && $open->created_at?->isSameDay($today)) {
            $student->class_room_id ? $open->update(['class_room_id' => $student->class_room_id]) : $open->delete();

            return;
        }

        $hadHistory = $open !== null || static::query()->where('student_id', $student->id)->exists();
        $open?->update(['end_date' => $today->copy()->subDay()]);

        if ($student->class_room_id) {
            static::create([
                'student_id' => $student->id,
                'class_room_id' => $student->class_room_id,
                // Santri baru: anggap di kelas ini sejak awal tahun ajaran berjalan (setoran yang
                // diinput belakangan untuk bulan-bulan sebelumnya tetap masuk laporan kelasnya).
                'start_date' => $hadHistory ? $today : Carbon::create(AcademicYear::startYear(AcademicYear::forDate($today)), 7, 1),
            ]);
        }
    }
}
