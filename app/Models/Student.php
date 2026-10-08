<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'class_room_id',
        'teacher_id',
        'name',
        'student_number',
        'dapodik_nis',
        'dapodik_nisn',
        'dapodik_rombel',
        'dapodik_synced_at',
        'gender',
        'birth_place',
        'birth_date',
        'status',
        'tahfizh_level',
        'hafalan_direction',
        'juz_orders',
    ];

    protected function casts(): array
    {
        return [
            'juz_orders' => 'array',
            'birth_date' => 'date',
            'dapodik_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Riwayat kelas (docs/riwayat-kelas.md): setiap kelas berubah -- lewat form, impor, atau
        // kode mana pun yang memakai model -- dicatat supaya laporan periode lalu tetap benar.
        static::created(fn (Student $student) => StudentClassHistory::record($student));
        static::updated(function (Student $student) {
            if ($student->wasChanged('class_room_id')) {
                StudentClassHistory::record($student);
            }
        });
    }

    public function classHistories(): HasMany
    {
        return $this->hasMany(StudentClassHistory::class)->orderBy('start_date');
    }

    /**
     * Santri yang berada di kelas ini pada tanggal itu menurut riwayat kelas (bukan kelas saat ini).
     */
    public function scopeInClassOn(Builder $query, int $classRoomId, CarbonInterface|string $date): Builder
    {
        return $query->whereIn($query->qualifyColumn('id'), StudentClassHistory::query()
            ->select('student_id')
            ->where('class_room_id', $classRoomId)
            ->activeOn($date));
    }

    /**
     * Kelas santri pada tanggal itu (riwayat kelas); tanpa riwayat = kelas saat ini.
     */
    public function classRoomOn(CarbonInterface|string $date): ?ClassRoom
    {
        $history = $this->classHistories->first(fn (StudentClassHistory $h) => $h->coversDate($date));

        if (! $history || $history->class_room_id === $this->class_room_id) {
            return $this->classRoom;
        }

        return $history->classRoom()->with('program')->first();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_id');
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(ParentProfile::class, 'parent_student', 'student_id', 'parent_id')
            ->withPivot('relation')
            ->withTimestamps();
    }

    public function hafalanRecords(): HasMany
    {
        return $this->hasMany(HafalanRecord::class);
    }

    public function tahfizhExams(): HasMany
    {
        return $this->hasMany(TahfizhExam::class);
    }

    public function murajaahRecords(): HasMany
    {
        return $this->hasMany(MurajaahRecord::class);
    }

    public function ummiRecords(): HasMany
    {
        return $this->hasMany(UmmiRecord::class);
    }

    public function hafalanTargets(): HasMany
    {
        return $this->hasMany(HafalanTarget::class);
    }

    public function adabRecords(): HasMany
    {
        return $this->hasMany(AdabRecord::class);
    }

    public function points(): HasMany
    {
        return $this->hasMany(StudentPoint::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Murid metode Ummi: level tahfizh "ummi" ATAU berada di kelas 10 (kelas pada periode laporan bila
     * relasi classRoom sudah dipasang ke kelas periode itu). Satu aturan untuk Grafik, Laporan Triwulan,
     * rapor, dan Target -- dulu Laporan Triwulan & rapor hanya melihat level, sehingga murid kelas 10
     * berlevel "reguler" kehilangan data Ummi-nya.
     */
    /**
     * NIS/NISN untuk ditampilkan (web & rapor cetak): dari Dapodik ("NIS / NISN") bila sudah diimpor,
     * selain itu nomor induk aplikasi.
     */
    public function nisNisn(): ?string
    {
        $dapodik = implode(' / ', array_filter([$this->dapodik_nis, $this->dapodik_nisn]));

        return $dapodik !== '' ? $dapodik : $this->student_number;
    }

    /**
     * Kelas di rapor cetak: rombel Dapodik bila sudah diimpor, selain itu kelas pembelajaran (aplikasi).
     */
    public function reportClassName(): ?string
    {
        return $this->dapodik_rombel ?: $this->classRoom?->name;
    }

    /** Hafalan dari sebelum aplikasi dipakai (lihat StudentPriorHafalan). */
    public function priorHafalans(): HasMany
    {
        return $this->hasMany(StudentPriorHafalan::class);
    }

    public function usesUmmi(): bool
    {
        return $this->tahfizh_level === 'ummi' || ($this->classRoom?->isGradeTen() ?? false);
    }

    public function getTahfizhLevelLabelAttribute(): string
    {
        return match ($this->tahfizh_level) {
            'tahsin' => 'Tahsin',
            'reguler' => 'Reguler',
            'akselerasi' => 'Akselerasi',
            'ummi' => 'Metode Ummi',
            default => 'Reguler',
        };
    }
}
