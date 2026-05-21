<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'severity',
        'title',
        'message',
        'source_type',
        'source_id',
        'action_url',
        'unique_hash',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'source_id' => 'integer',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    public function getSeverityLabelAttribute(): string
    {
        return match ($this->severity) {
            'success' => 'Sukses',
            'warning' => 'Peringatan',
            'danger' => 'Penting',
            'info' => 'Info',
            default => 'Info',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'target_overdue' => 'Target Terlambat',
            'hafalan_attention' => 'Hafalan Perlu Perhatian',
            'murajaah_attention' => 'Murajaah Perlu Perhatian',
            default => 'Notifikasi',
        };
    }

    public function markAsRead(): void
    {
        if ($this->read_at) {
            return;
        }

        $this->update([
            'read_at' => now(),
        ]);
    }
}