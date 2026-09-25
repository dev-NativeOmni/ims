<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Services\Backup\BackupManager;
use App\Services\Backup\GoogleDriveBackup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Concerns\SetsUpHafizPlusData;
use Tests\TestCase;
use ZipArchive;

/**
 * Menu Backup & Restore: backup, restore (khusus super admin, dengan konfirmasi & password),
 * dan salinan Google Drive.
 */
class BackupRestoreTest extends TestCase
{
    use RefreshDatabase, SetsUpHafizPlusData;

    private string $backupDir;

    private string $filesDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHafizPlusData();

        $this->backupDir = storage_path('framework/testing/backups');
        $this->filesDir = storage_path('framework/testing/public-files');
        File::deleteDirectory($this->backupDir);
        File::deleteDirectory($this->filesDir);
        config([
            'database_backup.path' => $this->backupDir,
            'database_backup.files_path' => $this->filesDir,
            'services.google_drive.client_id' => 'client-id',
            'services.google_drive.client_secret' => 'client-secret',
        ]);
        $this->superAdmin->update(['password' => Hash::make('rahasia123')]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backupDir);
        File::deleteDirectory($this->filesDir);
        parent::tearDown();
    }

    private function zipBackup(string $name, array $entries): string
    {
        File::ensureDirectoryExists($this->backupDir);
        $zip = new ZipArchive;
        $zip->open($this->backupDir.'/'.$name, ZipArchive::CREATE);
        foreach ($entries as $entry => $content) {
            $zip->addFromString($entry, $content);
        }
        $zip->close();

        return $name;
    }

    #[Test]
    public function super_admin_and_admin_see_the_menu_and_page_but_only_super_admin_can_restore(): void
    {
        $this->zipBackup('2026-09-25_030000_file-unggahan.zip', ['a.txt' => 'x']);

        $page = $this->actingAs($this->superAdmin)->get(route('database-backups.index'));
        $page->assertOk()->assertSee('Backup &amp; Restore', false)->assertSee('2026-09-25_030000_file-unggahan.zip');
        $this->assertTrue($page->viewData('canRestore'));

        $admin = $this->actingAs($this->admin)->get(route('database-backups.index'));
        $admin->assertOk();
        $this->assertFalse($admin->viewData('canRestore'));
        $this->actingAs($this->admin)->post(route('database-backups.restore'), [])->assertForbidden();
        $this->actingAs($this->admin)->get(route('database-backups.drive.connect'))->assertForbidden();
        $this->actingAs($this->admin)->delete(route('database-backups.destroy', '2026-09-25_030000_file-unggahan.zip'))->assertForbidden();

        $this->actingAs($this->teacherUser)->get(route('database-backups.index'))->assertForbidden();
    }

    #[Test]
    public function restore_requires_the_confirmation_word_and_the_correct_password(): void
    {
        $name = $this->zipBackup('files.zip', ['tanda-tangan/kepsek.png' => 'png']);

        $this->actingAs($this->superAdmin)->post(route('database-backups.restore'), [
            'source' => 'local', 'filename' => $name, 'confirmation' => 'pulihkan', 'password' => 'rahasia123',
        ])->assertSessionHasErrors('confirmation');

        $this->actingAs($this->superAdmin)->post(route('database-backups.restore'), [
            'source' => 'local', 'filename' => $name, 'confirmation' => 'PULIHKAN', 'password' => 'salah',
        ])->assertSessionHasErrors('password');

        $this->assertFileDoesNotExist($this->filesDir.'/tanda-tangan/kepsek.png');
    }

    #[Test]
    public function uploaded_files_backup_is_restored_into_the_public_storage(): void
    {
        $name = $this->zipBackup('2026-09-25_file-unggahan.zip', ['tanda-tangan/kepsek.png' => 'png-data']);

        $this->actingAs($this->superAdmin)->post(route('database-backups.restore'), [
            'source' => 'local', 'filename' => $name, 'confirmation' => 'PULIHKAN', 'password' => 'rahasia123',
        ])->assertSessionHas('success');

        $this->assertSame('png-data', File::get($this->filesDir.'/tanda-tangan/kepsek.png'));
    }

    #[Test]
    public function a_zip_that_escapes_the_folder_is_rejected(): void
    {
        $name = $this->zipBackup('jahat.zip', ['../../keluar.php' => '<?php echo 1;']);

        $this->actingAs($this->superAdmin)->post(route('database-backups.restore'), [
            'source' => 'local', 'filename' => $name, 'confirmation' => 'PULIHKAN', 'password' => 'rahasia123',
        ])->assertSessionHas('error');

        $this->assertFileDoesNotExist(storage_path('framework/keluar.php'));
    }

    #[Test]
    public function failed_backup_is_recorded_and_shown(): void
    {
        // Test memakai sqlite: backup mysqldump harus gagal dengan pesan jelas, bukan diam-diam.
        $this->artisan('tad:backup-database')->assertFailed();

        $status = app(BackupManager::class)->lastStatus();
        $this->assertFalse($status['ok']);
        $this->assertStringContainsString('mysql', $status['message']);
        $this->actingAs($this->superAdmin)->get(route('database-backups.index'))->assertSee('Gagal');
    }

    #[Test]
    public function connecting_google_drive_stores_an_encrypted_refresh_token(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'akses', 'refresh_token' => 'segar', 'expires_in' => 3600]),
            'www.googleapis.com/drive/v3/about*' => Http::response(['user' => ['emailAddress' => 'backup@smaia7.sch.id']]),
        ]);

        $redirect = $this->actingAs($this->superAdmin)->get(route('database-backups.drive.connect'));
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $redirect->headers->get('Location'));
        parse_str((string) parse_url($redirect->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('https://www.googleapis.com/auth/drive.file', $query['scope']);

        $this->actingAs($this->superAdmin)
            ->get(route('database-backups.drive.callback', ['code' => 'kode', 'state' => 'salah']))
            ->assertSessionHas('error');

        $this->actingAs($this->superAdmin)->get(route('database-backups.drive.connect'));
        $this->actingAs($this->superAdmin)
            ->get(route('database-backups.drive.callback', ['code' => 'kode', 'state' => session('backup_gdrive_state')]))
            ->assertSessionHas('success');

        $this->assertSame('segar', Crypt::decryptString(Setting::get('backup_gdrive_refresh_token')));
        $this->assertSame('backup@smaia7.sch.id', app(GoogleDriveBackup::class)->accountEmail());
        $this->assertTrue(app(GoogleDriveBackup::class)->isConnected());
    }

    #[Test]
    public function upload_creates_the_folder_and_sends_the_file_in_resumable_chunks(): void
    {
        Setting::set('backup_gdrive_refresh_token', Crypt::encryptString('segar'));
        File::ensureDirectoryExists($this->backupDir);
        File::put($this->backupDir.'/db.sql.gz', str_repeat('a', 1000));

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'akses', 'expires_in' => 3600]),
            'www.googleapis.com/drive/v3/files?fields=id' => Http::response(['id' => 'folder-1']),
            'www.googleapis.com/upload/drive/v3/files*' => Http::response([], 200, ['Location' => 'https://upload.example/sesi-1']),
            'upload.example/*' => Http::response(['id' => 'file-1', 'name' => 'db.sql.gz'], 200),
        ]);

        $uploaded = app(GoogleDriveBackup::class)->upload($this->backupDir.'/db.sql.gz');

        $this->assertSame('file-1', $uploaded['id']);
        $this->assertSame('folder-1', Setting::get('backup_gdrive_folder_id'));
        Http::assertSent(fn (HttpRequest $request) => str_contains($request->url(), 'uploadType=resumable')
            && $request['parents'] === ['folder-1'] && $request['name'] === 'db.sql.gz');
        Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://upload.example/sesi-1'
            && $request->header('Content-Range')[0] === 'bytes 0-999/1000');
    }

    #[Test]
    public function old_drive_backups_are_pruned(): void
    {
        Setting::set('backup_gdrive_refresh_token', Crypt::encryptString('segar'));
        Setting::set('backup_gdrive_folder_id', 'folder-1');

        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'akses', 'expires_in' => 3600]),
            'www.googleapis.com/drive/v3/files/folder-1*' => Http::response(['id' => 'folder-1', 'trashed' => false]),
            'www.googleapis.com/drive/v3/files?*' => Http::response(['files' => [
                ['id' => 'baru', 'name' => 'baru.sql.gz', 'size' => '10', 'createdTime' => now()->subDays(2)->toIso8601String()],
                ['id' => 'lama', 'name' => 'lama.sql.gz', 'size' => '10', 'createdTime' => now()->subDays(90)->toIso8601String()],
            ]]),
            'www.googleapis.com/drive/v3/files/lama' => Http::response(null, 204),
        ]);

        $this->assertSame(1, app(GoogleDriveBackup::class)->prune(60));
        Http::assertSent(fn (HttpRequest $request) => $request->method() === 'DELETE' && str_ends_with($request->url(), '/files/lama'));
        Http::assertNotSent(fn (HttpRequest $request) => $request->method() === 'DELETE' && str_ends_with($request->url(), '/files/baru'));
    }
}
