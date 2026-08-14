<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hafalan_targets', function (Blueprint $table) {
            $table->string('ummi_jilid')->nullable()->after('teacher_id');
            $table->string('halaman_peraga')->nullable()->after('ummi_jilid');
            $table->string('halaman_buku')->nullable()->after('halaman_peraga');

            $table->unsignedBigInteger('surah_id')->nullable()->change();
            $table->unsignedSmallInteger('ayah_start')->nullable()->change();
            $table->unsignedSmallInteger('ayah_end')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('hafalan_targets', function (Blueprint $table) {
            $table->dropColumn(['ummi_jilid', 'halaman_peraga', 'halaman_buku']);
        });
    }
};
