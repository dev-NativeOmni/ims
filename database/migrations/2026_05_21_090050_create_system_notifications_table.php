<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('type', 80);
            $table->string('severity', 30)->default('info');

            $table->string('title');
            $table->text('message');

            $table->string('source_type', 120)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->string('action_url')->nullable();

            $table->string('unique_hash', 64)->unique();

            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'type']);
            $table->index(['type', 'severity']);
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_notifications');
    }
};