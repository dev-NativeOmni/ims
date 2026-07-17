<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('audit_logs', 'auditable_label')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->string('auditable_label')
                    ->nullable()
                    ->after('auditable_id')
                    ->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('audit_logs', 'auditable_label')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex(['auditable_label']);
                $table->dropColumn('auditable_label');
            });
        }
    }
};
