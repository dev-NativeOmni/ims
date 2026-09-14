<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Collapses ayah_start/ayah_end into a single `ayah` column.
     *
     * New semantics: a target represents "memorized from ayah 1 through
     * this ayah" (a cumulative milestone), so existing ranges are
     * backfilled using their ayah_end value.
     */
    public function up(): void
    {
        Schema::table('hafalan_targets', function (Blueprint $table) {
            $table->unsignedSmallInteger('ayah')->nullable()->after('surah_id');
        });

        DB::table('hafalan_targets')->whereNotNull('ayah_end')->update([
            'ayah' => DB::raw('ayah_end'),
        ]);

        Schema::table('hafalan_targets', function (Blueprint $table) {
            $table->dropIndex(['surah_id', 'ayah_start', 'ayah_end']);
            $table->dropColumn(['ayah_start', 'ayah_end']);
            $table->index(['surah_id', 'ayah']);
        });
    }

    /**
     * Rollback is lossy for ayah_start: the original start of the range
     * cannot be reconstructed from a single `ayah` value, so it is
     * restored as 1 for every row (documented data loss).
     */
    public function down(): void
    {
        Schema::table('hafalan_targets', function (Blueprint $table) {
            $table->dropIndex(['surah_id', 'ayah']);
            $table->unsignedSmallInteger('ayah_start')->nullable()->after('surah_id');
            $table->unsignedSmallInteger('ayah_end')->nullable()->after('ayah_start');
        });

        DB::table('hafalan_targets')->whereNotNull('ayah')->update([
            'ayah_start' => 1,
            'ayah_end' => DB::raw('ayah'),
        ]);

        Schema::table('hafalan_targets', function (Blueprint $table) {
            $table->dropColumn('ayah');
            $table->index(['surah_id', 'ayah_start', 'ayah_end']);
        });
    }
};
