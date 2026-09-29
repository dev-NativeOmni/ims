<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Buku Ummi Dewasa: Jilid 1-3, Gharib, Tajwid. Pilihan lama "Ghoroib" diseragamkan jadi "Gharib"
 * supaya cocok dengan dropdown baru (App\Support\UmmiBook::BOOKS).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('ummi_records')->where('ummi_jilid', 'Ghoroib')->update(['ummi_jilid' => 'Gharib']);
    }

    public function down(): void
    {
        DB::table('ummi_records')->where('ummi_jilid', 'Gharib')->update(['ummi_jilid' => 'Ghoroib']);
    }
};
