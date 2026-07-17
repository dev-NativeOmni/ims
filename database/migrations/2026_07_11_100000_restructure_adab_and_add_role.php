<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adab_records', function (Blueprint $table) {
            $table->boolean('q16')->nullable()->change();
            $table->boolean('q17')->nullable()->change();
            $table->boolean('q18')->nullable()->change();
            $table->boolean('q19')->nullable()->change();
            $table->boolean('q20')->nullable()->change();

            $table->integer('mentor_score')->nullable()->after('total_score');

            $table->foreignId('mentor_id')
                ->nullable()
                ->after('evaluator_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        $role = Role::updateOrCreate(
            ['name' => 'pendamping_adab'],
            ['display_name' => 'Pendamping Adab']
        );

        User::updateOrCreate(
            ['username' => 'pendamping_adab'],
            [
                'role_id' => $role->id,
                'name' => 'Pendamping Adab IMS',
                'password' => Hash::make('password123'),
                'status' => 'active',
            ]
        );
    }

    public function down(): void
    {
        $user = User::where('username', 'pendamping_adab')->first();
        if ($user) {
            $user->delete();
        }
        Role::where('name', 'pendamping_adab')->delete();

        Schema::table('adab_records', function (Blueprint $table) {
            $table->dropForeign(['mentor_id']);
            $table->dropColumn(['mentor_id', 'mentor_score']);

            $table->boolean('q16')->nullable(false)->change();
            $table->boolean('q17')->nullable(false)->change();
            $table->boolean('q18')->nullable(false)->change();
            $table->boolean('q19')->nullable(false)->change();
            $table->boolean('q20')->nullable(false)->change();
        });
    }
};
