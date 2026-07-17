<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_notifications')) {
            return;
        }

        if (! Schema::hasColumn('system_notifications', 'created_by')) {
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
        if (! Schema::hasTable('system_notifications')) {
            return;
        }

        if (Schema::hasColumn('system_notifications', 'created_by')) {
            Schema::table('system_notifications', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            });
        }
    }
};
