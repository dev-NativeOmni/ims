<?php

namespace App\Console\Commands;

use App\Models\ParentProfile;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BulkUpdateParentUsernames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:bulk-update-parent-usernames 
                            {--dry-run : Tampilkan simulasi perubahan tanpa mengubah database}
                            {--force : Lakukan eksekusi perubahan langsung ke database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perbarui username semua akun orang tua secara masal dengan format ortu.<nama_depan_dan_belakang_anak>';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        // Jika tidak ditentukan --force, default ke dry-run mode demi keamanan
        $isSimulation = $dryRun || ! $force;

        if ($isSimulation) {
            $this->info('=====================================================');
            $this->info('  MODE SIMULASI (DRY-RUN) - DATABASE TIDAK DIUBAH   ');
            $this->info('=====================================================');
        } else {
            $this->warn('=====================================================');
            $this->warn('  MODE EKSEKUSI (FORCE) - PERUBAHAN AKAN DISIMPAN    ');
            $this->warn('=====================================================');
        }

        $parents = ParentProfile::query()
            ->with(['user', 'students'])
            ->get();

        if ($parents->isEmpty()) {
            $this->info('Tidak ada data profil orang tua yang ditemukan.');

            return self::SUCCESS;
        }

        $tableRows = [];
        $updatesQueue = [];
        $assignedUsernamesInBatch = [];
        $skippedCount = 0;
        $updatedCount = 0;
        $unchangedCount = 0;

        $index = 1;
        foreach ($parents as $parent) {
            $user = $parent->user;

            if (! $user) {
                continue;
            }

            $students = $parent->students;
            $oldUsername = $user->username;

            if ($students->isEmpty()) {
                $tableRows[] = [
                    'index' => $index++,
                    'parent_name' => $user->name,
                    'old_username' => $oldUsername,
                    'new_username' => '-',
                    'student_name' => '-',
                    'status' => '⚠️ DILEWATI (Belum terhubung ke santri)',
                ];
                $skippedCount++;

                continue;
            }

            // Ambil santri pertama
            $mainStudent = $students->first();
            $cleanStudentName = $this->extractStudentNameForUsername((string) $mainStudent->name);

            if (empty($cleanStudentName)) {
                $cleanStudentName = 'santri';
            }

            $baseCandidate = 'ortu.'.$cleanStudentName;
            $newUsername = $baseCandidate;
            $counter = 2;

            // Pastikan keunikan username di database
            while (
                in_array($newUsername, $assignedUsernamesInBatch, true) ||
                User::withTrashed()
                    ->where('username', $newUsername)
                    ->where('id', '!=', $user->id)
                    ->exists()
            ) {
                $newUsername = $baseCandidate.$counter;
                $counter++;
            }

            $assignedUsernamesInBatch[] = $newUsername;

            if ($oldUsername === $newUsername) {
                $tableRows[] = [
                    'index' => $index++,
                    'parent_name' => $user->name,
                    'old_username' => $oldUsername,
                    'new_username' => $newUsername,
                    'student_name' => $mainStudent->name,
                    'status' => 'ℹ️ SAMA (Tidak perlu diubah)',
                ];
                $unchangedCount++;
            } else {
                $statusText = $isSimulation ? '✏️ AKAN DIUBAH' : '✅ BERHASIL DIUBAH';
                $tableRows[] = [
                    'index' => $index++,
                    'parent_name' => $user->name,
                    'old_username' => $oldUsername,
                    'new_username' => $newUsername,
                    'student_name' => $mainStudent->name,
                    'status' => $statusText,
                ];
                $updatesQueue[] = [
                    'user_id' => $user->id,
                    'old_username' => $oldUsername,
                    'new_username' => $newUsername,
                ];
                $updatedCount++;
            }
        }

        // Tampilkan tabel perbandingan
        $this->table(
            ['No', 'Nama Ortu', 'Username Lama', 'Username Baru', 'Santri Utama', 'Status'],
            $tableRows
        );

        $this->info("Ringkasan: Total {$parents->count()} ortu | {$updatedCount} username perlu diperbarui | {$unchangedCount} sudah sesuai | {$skippedCount} dilewati (tanpa santri).");

        if ($isSimulation) {
            $this->comment("\n💡 Untuk menyimpan perubahan ini ke database, jalankan perintah:");
            $this->comment('   php artisan users:bulk-update-parent-usernames --force');

            return self::SUCCESS;
        }

        if (empty($updatesQueue)) {
            $this->info('Tidak ada username yang perlu diperbarui.');

            return self::SUCCESS;
        }

        // Eksekusi perubahan ke database
        DB::beginTransaction();
        try {
            foreach ($updatesQueue as $item) {
                User::where('id', $item['user_id'])->update([
                    'username' => $item['new_username'],
                ]);
            }
            DB::commit();
            $this->info("\n🎉 Sukses! Berhasil memperbarui {$updatedCount} username orang tua di database.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n❌ Gagal melakukan perubahan: ".$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Ekstraksi nama santri (Depan + Belakang/Kedua) untuk username ortu yang lebih spesifik.
     * Contoh:
     * - "Ahmad Fadhil" -> "ahmadfadhil"
     * - "M. Rizky Ramadhan" -> "rizkyramadhan"
     * - "Budi" -> "budi"
     */
    private function extractStudentNameForUsername(string $fullName): string
    {
        $rawWords = preg_split('/\s+/', trim($fullName));
        $words = [];

        foreach ($rawWords as $w) {
            $clean = strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $w));
            if ($clean !== '') {
                $words[] = $clean;
            }
        }

        if (empty($words)) {
            return '';
        }

        // Jika hanya 1 kata (misal: "Budi")
        if (count($words) === 1) {
            return $words[0];
        }

        // Jika kata pertama berupa inisial/singkatan pendek (misal "m", "a", "md") dan ada kata ke-2 & ke-3
        if (strlen($words[0]) <= 2 && count($words) >= 3) {
            return $words[1].$words[2];
        }

        // Ambil 2 kata pertama (misal: "ahmad" + "fadhil" -> "ahmadfadhil")
        return $words[0].$words[1];
    }
}
