<?php

namespace App\Models;

use App\Support\AyahLabel;
use App\Support\UmmiAyah;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UmmiRecord extends Model
{
    use HasFactory;

    /** Nilai sesi Ummi bila guru tidak mengisi nilai. */
    public const DEFAULT_NILAI = 'B';

    protected static function booted(): void
    {
        static::saving(function (self $ummi) {
            if (blank($ummi->nilai)) {
                $ummi->nilai = self::DEFAULT_NILAI;
            }
        });
    }

    protected $fillable = [
        'student_id',
        'teacher_id',
        'tatap_muka',
        'tanggal',
        'ummi_jilid',
        'ummi_halaman',
        'materi',
        'nilai',
        'disimak_guru',
        'disimak_ortu',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'teacher_id' => 'integer',
            'tatap_muka' => 'integer',
            'tanggal' => 'date',
        ];
    }

    public function getLinesCountAttribute(): float
    {
        return (float) $this->surahs->sum(fn (UmmiRecordSurah $surah) => $surah->lines_count);
    }

    public function getSurahsLabelAttribute(): string
    {
        return $this->surahs
            ->map(function (UmmiRecordSurah $surah) {
                $label = $surah->surah?->name_latin ?? '-';

                return $surah->hafalan_ayah ? "{$label} ({$surah->hafalan_ayah})" : $label;
            })
            ->implode(', ');
    }

    /** Label capaian untuk laporan: cukup ayat akhir tiap surah, mis. "An-Naba (38)". */
    public function getSurahsEndLabelAttribute(): string
    {
        return $this->surahs
            ->map(function (UmmiRecordSurah $surah) {
                $label = $surah->surah?->name_latin ?? '-';

                // Ayat melebihi panjang surah (salah input) = akhir surah (App\Support\UmmiAyah).
                $ayah = $surah->surah ? UmmiAyah::clamp((string) $surah->hafalan_ayah, (int) $surah->surah->total_ayah) : $surah->hafalan_ayah;

                return $surah->hafalan_ayah ? $label.' ('.AyahLabel::end($ayah).')' : $label;
            })
            ->implode(', ');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_id');
    }

    public function surahs(): HasMany
    {
        return $this->hasMany(UmmiRecordSurah::class)->orderBy('sort_order')->orderBy('id');
    }
}
