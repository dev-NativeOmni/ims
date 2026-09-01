<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 111: Al-Masad -> Al-Lahab
        DB::table('surahs')
            ->where('number', 111)
            ->update([
                'name_latin' => 'Al-Lahab',
                'name_ar' => 'اللهب',
            ]);

        // 102: At-Takathur -> At-Takatsur
        DB::table('surahs')
            ->where('number', 102)
            ->update([
                'name_latin' => 'At-Takatsur',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('surahs')
            ->where('number', 111)
            ->update([
                'name_latin' => 'Al-Masad',
                'name_ar' => 'المسد',
            ]);

        DB::table('surahs')
            ->where('number', 102)
            ->update([
                'name_latin' => 'At-Takathur',
            ]);
    }
};
