<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->foreignId('pendamping_adab_id')
                ->nullable()
                ->after('program_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->dropForeign(['pendamping_adab_id']);
            $table->dropColumn('pendamping_adab_id');
        });
    }
};
