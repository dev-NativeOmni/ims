<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_name',
        'role_name',
        'action',
        'auditable_type',
        'auditable_id',
        'auditable_label',
        'auditable_name',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'method',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Halaman & akun yang membuat data ini (dari log "created"), mis. "spreadsheet-input · Fuad".
     * Dipakai perintah diagnosis setoran (tad:hapus-setoran-ganda, tad:rincian-baris --diinput).
     */
    public static function createdVia(string $auditableType, ?int $auditableId): string
    {
        $log = $auditableId ? static::query()->with('user:id,name')
            ->where('auditable_type', $auditableType)
            ->where('auditable_id', $auditableId)
            ->where('action', 'created')
            ->first() : null;
        if (! $log) {
            return '-';
        }

        $path = trim((string) parse_url((string) $log->url, PHP_URL_PATH), '/') ?: 'konsol';

        return $path.' · '.($log->user?->name ?? $log->user_name ?? '-');
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created' => 'Dibuat',
            'updated' => 'Diubah',
            'deleted' => 'Dihapus',
            'restored' => 'Dipulihkan',
            'force_deleted' => 'Dihapus Permanen',
            default => ucfirst((string) $this->action),
        };
    }

    public function getEventAttribute(): ?string
    {
        return $this->action;
    }

    /**
     * Backward-compatible alias.
     */
    public function getEventLabelAttribute(): string
    {
        return $this->action_label;
    }

    public function getAuditableNameAttribute(): string
    {
        return $this->auditable_label
            ?: class_basename((string) $this->auditable_type).' #'.$this->auditable_id;
    }

    public function getAuditableTypeLabelAttribute(): string
    {
        return match ($this->auditable_type) {
            HafalanRecord::class => 'Setoran Hafalan',
            MurajaahRecord::class => 'Murajaah',
            HafalanTarget::class => 'Target Hafalan',
            Student::class => 'Murid',
            Program::class => 'Program',
            ClassRoom::class => 'Kelas',
            TeacherProfile::class => 'Guru',
            ParentProfile::class => 'Orangtua',
            User::class => 'User',
            CalendarMonthLock::class => 'Kunci Kalender',
            ClassWeekSchedule::class => 'Jadwal Pekanan',
            default => class_basename((string) $this->auditable_type),
        };
    }
}
