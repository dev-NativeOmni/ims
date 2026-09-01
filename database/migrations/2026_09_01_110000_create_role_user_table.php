<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->timestamps();

                $table->primary(['user_id', 'role_id']);
            });
        }

        // Populate existing user-role relationships into pivot table
        if (Schema::hasTable('users') && Schema::hasTable('role_user')) {
            $users = DB::table('users')->whereNotNull('role_id')->get(['id', 'role_id']);
            $now = now();
            $inserts = [];

            foreach ($users as $user) {
                $inserts[] = [
                    'user_id' => $user->id,
                    'role_id' => $user->role_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (! empty($inserts)) {
                DB::table('role_user')->insertOrIgnore($inserts);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
