<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hafalan_records', function (Blueprint $table) {
            $table->decimal('baris', 5, 2)->nullable()->after('status');
        });

        Schema::table('ummi_records', function (Blueprint $table) {
            $table->decimal('baris', 5, 2)->nullable()->after('nilai');
        });
    }

    public function down(): void
    {
        Schema::table('ummi_records', function (Blueprint $table) {
            $table->dropColumn('baris');
        });

        Schema::table('hafalan_records', function (Blueprint $table) {
            $table->dropColumn('baris');
        });
    }
};
