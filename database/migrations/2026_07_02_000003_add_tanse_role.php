<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Role::updateOrCreate(
            ['name' => 'tanse'],
            [
                'name' => 'tanse',
                'display_name' => 'Ketahanan Sekolah',
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::where('name', 'tanse')->delete();
    }
};
