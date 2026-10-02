<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * Kunci target hafalan per kelas per bulan.
 *
 * - Bulan terkunci: target murid kelas itu di bulan itu tidak bisa dibuat, diubah, atau dihapus
 *   (Target Bulanan, Target Triwulan, Target Ummi, simpan massal). Status tuntas tetap otomatis.
 * - Kelas murid untuk sebuah bulan memakai riwayat kelas (docs/riwayat-kelas.md).
 * - Mengunci: Super Admin, Admin, Koordinator Tahfizh. Membuka kunci: hanya Super Admin.
 */
class TargetLock extends Model
{
    protected $fillable = ['class_room_id', 'month', 'locked_by', 'locked_at'];

    /** @var array<string, bool> cache per request: "kelas|Y-m" => terkunci */
    private static array $cache = [];

    protected function casts(): array
    {
        return ['month' => 'date', 'locked_at' => 'datetime'];
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public static function monthStart(CarbonInterface|string $date): Carbon
    {
        return Carbon::parse($date)->startOfMonth()->startOfDay();
    }

    public static function lockedFor(?int $classRoomId, CarbonInterface|string $date): bool
    {
        if (! $classRoomId) {
            return false;
        }
        $month = self::monthStart($date);
        $key = $classRoomId.'|'.$month->format('Y-m');

        return self::$cache[$key] ??= static::query()->where('class_room_id', $classRoomId)->whereDate('month', $month->toDateString())->exists();
    }

    /**
     * Kunci kelas ini untuk bulan-bulan tertentu, diindeks "Y-m".
     *
     * @param  iterable<string>  $monthKeys  "Y-m"
     * @return Collection<string, TargetLock>
     */
    public static function forMonths(int $classRoomId, iterable $monthKeys): Collection
    {
        $keys = collect($monthKeys)->values();
        if ($keys->isEmpty()) {
            return collect();
        }

        // whereDate (bukan whereIn): kolom bisa tersimpan dengan jam ("Y-m-d 00:00:00").
        return static::query()->with('locker:id,name')
            ->where('class_room_id', $classRoomId)
            ->whereDate('month', '>=', $keys->min().'-01')
            ->whereDate('month', '<=', $keys->max().'-01')
            ->get()
            ->toBase() // only() pada Eloquent Collection memfilter primary key, bukan kunci "Y-m".
            ->keyBy(fn (TargetLock $lock) => $lock->month->format('Y-m'))
            ->only($keys->all());
    }

    /** Kelas murid pada bulan target (riwayat kelas), atau null. */
    public static function classOf(Student $student, CarbonInterface|string $date): ?ClassRoom
    {
        return $student->classRoomOn(StudentClassHistory::referenceDate(Carbon::parse($date)->endOfMonth()));
    }

    public static function blocksStudent(Student $student, CarbonInterface|string $date): bool
    {
        return self::lockedFor(self::classOf($student, $date)?->id, $date);
    }

    public static function message(Student $student, CarbonInterface|string $date): string
    {
        $month = Carbon::parse($date)->locale('id')->translatedFormat('F Y');
        $class = self::classOf($student, $date)?->name ?? '-';

        return "Target {$month} kelas {$class} sudah dikunci ({$student->name}); minta Super Admin membuka kunci untuk mengubahnya.";
    }

    public static function canLock(?User $user): bool
    {
        return (bool) $user?->hasAnyRole(['super_admin', 'admin', 'coordinator_tahfizh']);
    }

    public static function canUnlock(?User $user): bool
    {
        return (bool) $user?->hasRole('super_admin');
    }

    public static function flushCache(): void
    {
        self::$cache = [];
    }
}
