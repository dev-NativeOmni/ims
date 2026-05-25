<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('type', 80);
            $table->string('title', 180);
            $table->text('message');

            $table->string('action_url')->nullable();

            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->enum('priority', [
                'low',
                'normal',
                'high',
            ])->default('normal');

            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['type']);
            $table->index(['priority']);
            $table->index(['source_type', 'source_id']);

            $table->unique(
                ['user_id', 'type', 'source_type', 'source_id'],
                'internal_notifications_unique_source'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internal_notifications');
    }
};