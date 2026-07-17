<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Role::updateOrCreate(
            ['name' => 'headmaster'],
            [
                'name' => 'headmaster',
                'display_name' => 'Kepala Sekolah',
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::where('name', 'headmaster')->delete();
    }
};
