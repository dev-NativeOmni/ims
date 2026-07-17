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

        Schema::table('system_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('system_notifications', 'is_read')) {
                $table->boolean('is_read')
                    ->default(false)
                    ->after('action_url');
            }

            if (! Schema::hasColumn('system_notifications', 'published_at')) {
                $table->timestamp('published_at')
                    ->nullable()
                    ->after('read_at');
            }

            if (! Schema::hasColumn('system_notifications', 'expires_at')) {
                $table->timestamp('expires_at')
                    ->nullable()
                    ->after('published_at');
            }

            if (! Schema::hasColumn('system_notifications', 'deleted_at')) {
                $table->softDeletes()
                    ->after('expires_at');
            }
        });

        Schema::table('system_notifications', function (Blueprint $table) {
            if (! Schema::hasColumn('system_notifications', 'target_role')) {
                $table->string('target_role')
                    ->nullable()
                    ->after('type');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('system_notifications')) {
            return;
        }

        Schema::table('system_notifications', function (Blueprint $table) {
            if (Schema::hasColumn('system_notifications', 'is_read')) {
                $table->dropColumn('is_read');
            }

            if (Schema::hasColumn('system_notifications', 'published_at')) {
                $table->dropColumn('published_at');
            }

            if (Schema::hasColumn('system_notifications', 'expires_at')) {
                $table->dropColumn('expires_at');
            }

            if (Schema::hasColumn('system_notifications', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            if (Schema::hasColumn('system_notifications', 'target_role')) {
                $table->dropColumn('target_role');
            }
        });
    }
};
