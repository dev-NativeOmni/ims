<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create adab_records table
        Schema::create('adab_records', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnDelete();
                
            // The person who filled it (can be the student's user_id or evaluator)
            $table->foreignId('evaluator_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('assessment_date');
            
            // 20 Adab questions (each value: 1 for Yes, 0 for No)
            $table->boolean('q1');
            $table->boolean('q2');
            $table->boolean('q3');
            $table->boolean('q4');
            $table->boolean('q5');
            $table->boolean('q6');
            $table->boolean('q7');
            $table->boolean('q8');
            $table->boolean('q9');
            $table->boolean('q10');
            $table->boolean('q11');
            $table->boolean('q12');
            $table->boolean('q13');
            $table->boolean('q14');
            $table->boolean('q15');
            $table->boolean('q16');
            $table->boolean('q17');
            $table->boolean('q18');
            $table->boolean('q19');
            $table->boolean('q20');
            
            $table->integer('total_score');
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });

        // 2. Create the 'supervisor' role if not exists
        $supervisorRole = Role::updateOrCreate(
            ['name' => 'supervisor'],
            ['display_name' => 'Koordinator Keagamaan']
        );

        // 3. Create the default 'supervisor' user if not exists
        User::updateOrCreate(
            ['username' => 'supervisor'],
            [
                'role_id' => $supervisorRole->id,
                'name' => 'Koordinator Keagamaan HafizPlus',
                'password' => Hash::make('password123'),
                'plain_password' => 'password123',
                'status' => 'active',
            ]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('adab_records');
        
        // Remove supervisor user and role
        $user = User::where('username', 'supervisor')->first();
        if ($user) {
            $user->delete();
        }
        Role::where('name', 'supervisor')->delete();
    }
};
