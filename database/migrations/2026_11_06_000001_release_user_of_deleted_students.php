<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Murid yang sudah dihapus melepas akunnya (lihat Student::booted): sebelumnya baris terhapus tetap
 * memegang students.user_id yang unik, sehingga akun itu error 500 saat login atau disimpan di Kelola User.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('students')->whereNotNull('deleted_at')->whereNotNull('user_id')->update(['user_id' => null]);
    }

    public function down(): void
    {
        // Tidak dikembalikan: akun yang dilepas mungkin sudah tersambung ke data murid lain.
    }
};
