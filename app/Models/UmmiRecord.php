<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmmiRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'teacher_id',
        'tatap_muka',
        'tanggal',
        'hafalan_surah_id',
        'hafalan_ayah',
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
            'hafalan_surah_id' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_id');
    }

    public function surah(): BelongsTo
    {
        return $this->belongsTo(Surah::class, 'hafalan_surah_id');
    }
}
