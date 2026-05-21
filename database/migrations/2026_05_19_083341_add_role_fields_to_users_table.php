<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // relasi ke roles
            $table->foreignId('role_id')
                ->nullable()
                ->after('id')
                ->constrained('roles')
                ->nullOnDelete();

            // status user
            $table->enum('status', [
                'active',
                'inactive'
            ])->default('active')->after('password');

            // soft delete
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropForeign(['role_id']);

            $table->dropColumn([
                'role_id',
                'status',
                'deleted_at'
            ]);
        });
    }
};