<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TM target murid yang disamakan dengan mayoritas halaqoh (lihat migrasi create_ummi_prosem_overrides_table).
 */
class UmmiProsemOverride extends Model
{
    protected $fillable = ['student_id', 'academic_year', 'semester', 'tm_no', 'created_by'];

    protected function casts(): array
    {
        return ['student_id' => 'integer', 'semester' => 'integer', 'tm_no' => 'integer'];
    }
}
