<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_room_pendamping_adab', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_room_id')->constrained('class_rooms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['class_room_id', 'user_id'], 'cr_pa_unique');
        });

        // Migrate existing legacy single assignments
        if (Schema::hasTable('class_rooms') && Schema::hasColumn('class_rooms', 'pendamping_adab_id')) {
            $existing = DB::table('class_rooms')
                ->whereNotNull('pendamping_adab_id')
                ->select('id as class_room_id', 'pendamping_adab_id as user_id')
                ->get();

            foreach ($existing as $row) {
                if ($row->user_id) {
                    DB::table('class_room_pendamping_adab')->insertOrIgnore([
                        'class_room_id' => $row->class_room_id,
                        'user_id' => $row->user_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('class_room_pendamping_adab');
    }
};
