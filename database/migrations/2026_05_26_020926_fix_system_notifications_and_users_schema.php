<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Patch users table
        |--------------------------------------------------------------------------
        | SystemNotificationController mencari users.is_active.
        | Kalau kolom ini tidak ada, pembuatan notifikasi role tertentu akan error.
        */

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_active')
                    ->default(true)
                    ->after('role_id')
                    ->index();
            });

            DB::table('users')->update([
                'is_active' => true,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Patch system_notifications table
        |--------------------------------------------------------------------------
        | SystemNotificationController menyimpan created_by.
        | Kalau kolom ini tidak ada, insert notifikasi baru akan error.
        */

        if (Schema::hasTable('system_notifications') && ! Schema::hasColumn('system_notifications', 'created_by')) {
            Schema::table('system_notifications', function (Blueprint $table) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('system_notifications') && Schema::hasColumn('system_notifications', 'created_by')) {
            Schema::table('system_notifications', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_active')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex(['is_active']);
                $table->dropColumn('is_active');
            });
        }
    }
};
