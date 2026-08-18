<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('hafalan_records')) {
            Schema::table('hafalan_records', function (Blueprint $table) {
                $table->index(['student_id', 'submitted_at'], 'idx_hafalan_std_subdate');
            });
        }

        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->index(['student_id', 'tanggal'], 'idx_att_std_tanggal');
                $table->index(['class_room_id', 'tanggal'], 'idx_att_cls_tanggal');
            });
        }

        if (Schema::hasTable('ummi_records')) {
            Schema::table('ummi_records', function (Blueprint $table) {
                $table->index(['student_id', 'tanggal'], 'idx_ummi_std_tanggal');
            });
        }

        if (Schema::hasTable('murajaah_records')) {
            Schema::table('murajaah_records', function (Blueprint $table) {
                $table->index(['student_id', 'submitted_at'], 'idx_murajaah_std_subdate');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('hafalan_records')) {
            Schema::table('hafalan_records', function (Blueprint $table) {
                $table->dropIndex('idx_hafalan_std_subdate');
            });
        }

        if (Schema::hasTable('attendances')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropIndex('idx_att_std_tanggal');
                $table->dropIndex('idx_att_cls_tanggal');
            });
        }

        if (Schema::hasTable('ummi_records')) {
            Schema::table('ummi_records', function (Blueprint $table) {
                $table->dropIndex('idx_ummi_std_tanggal');
            });
        }

        if (Schema::hasTable('murajaah_records')) {
            Schema::table('murajaah_records', function (Blueprint $table) {
                $table->dropIndex('idx_murajaah_std_subdate');
            });
        }
    }
};
