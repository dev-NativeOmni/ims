<?php

namespace App\Services\Backup;

use App\Models\Setting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Salinan backup di Google Drive lewat REST API (tanpa paket tambahan).
 *
 * Super admin menghubungkan akun Google sekali (OAuth, scope drive.file: aplikasi hanya bisa
 * melihat file yang dibuatnya sendiri). Refresh token disimpan terenkripsi di settings; file
 * backup diunggah ke satu folder "TAD Backup" di Drive akun tersebut.
 */
class GoogleDriveBackup
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';

    private const API = 'https://www.googleapis.com/drive/v3';

    private const UPLOAD_API = 'https://www.googleapis.com/upload/drive/v3/files';

    private const SCOPE = 'https://www.googleapis.com/auth/drive.file';

    private const CHUNK_SIZE = 8 * 1024 * 1024; // kelipatan 256 KB sesuai syarat resumable upload

    /** Client ID & secret sudah diisi di .env. */
    public function isConfigured(): bool
    {
        return filled(config('services.google_drive.client_id')) && filled(config('services.google_drive.client_secret'));
    }

    /** Akun Google sudah dihubungkan. */
    public function isConnected(): bool
    {
        return $this->isConfigured() && filled(Setting::get('backup_gdrive_refresh_token'));
    }

    public function accountEmail(): ?string
    {
        return Setting::get('backup_gdrive_email');
    }

    public function authorizationUrl(string $redirectUri, string $state): string
    {
        return self::AUTH_URL.'?'.http_build_query([
            'client_id' => config('services.google_drive.client_id'),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);
    }

    /**
     * Tukar kode OAuth dengan refresh token, simpan terenkripsi, dan catat email akun.
     */
    public function connect(string $code, string $redirectUri): void
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => config('services.google_drive.client_id'),
            'client_secret' => config('services.google_drive.client_secret'),
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
        ]);

        $refreshToken = $response->json('refresh_token');
        if ($response->failed() || ! $refreshToken) {
            throw new RuntimeException('Google menolak koneksi: '.($response->json('error_description') ?? $response->json('error') ?? 'refresh token tidak diterima.'));
        }

        Setting::set('backup_gdrive_refresh_token', Crypt::encryptString($refreshToken));
        Setting::set('backup_gdrive_folder_id', null);
        Cache::put($this->tokenCacheKey(), $response->json('access_token'), now()->addSeconds(max(60, (int) $response->json('expires_in', 3600) - 120)));

        $about = $this->api()->get(self::API.'/about', ['fields' => 'user(emailAddress)']);
        Setting::set('backup_gdrive_email', $about->json('user.emailAddress'));
    }

    public function disconnect(): void
    {
        $token = $this->refreshToken();
        if ($token) {
            // Cabut akses di Google; kegagalan (token sudah dicabut) tidak menghalangi pemutusan.
            rescue(fn () => Http::asForm()->post(self::REVOKE_URL, ['token' => $token]), report: false);
        }

        foreach (['backup_gdrive_refresh_token', 'backup_gdrive_email', 'backup_gdrive_folder_id'] as $key) {
            Setting::set($key, null);
        }
        Cache::forget($this->tokenCacheKey());
    }

    /**
     * Unggah satu file ke folder backup (resumable upload per 8 MB).
     *
     * @return array{id: string, name: string}
     */
    public function upload(string $path): array
    {
        $size = filesize($path);
        $session = $this->api()
            ->withHeaders([
                'X-Upload-Content-Type' => 'application/octet-stream',
                'X-Upload-Content-Length' => (string) $size,
            ])
            ->post(self::UPLOAD_API.'?uploadType=resumable&fields=id,name', [
                'name' => basename($path),
                'parents' => [$this->folderId()],
            ]);

        $uploadUrl = $session->header('Location');
        if ($session->failed() || ! $uploadUrl) {
            throw new RuntimeException('Gagal memulai unggahan ke Google Drive: '.$session->body());
        }

        $handle = fopen($path, 'rb');
        try {
            $offset = 0;
            do {
                $chunk = (string) fread($handle, self::CHUNK_SIZE);
                $length = strlen($chunk);
                $range = $size === 0 ? 'bytes */0' : 'bytes '.$offset.'-'.($offset + $length - 1).'/'.$size;

                $response = Http::withToken($this->accessToken())
                    ->timeout(300)
                    ->withHeaders(['Content-Range' => $range])
                    ->withBody($chunk, 'application/octet-stream')
                    ->put($uploadUrl);

                $offset += $length;

                if ($response->status() !== 308 && $response->failed()) {
                    throw new RuntimeException('Unggahan ke Google Drive gagal: '.$response->body());
                }
            } while ($response->status() === 308 && $offset < $size);
        } finally {
            fclose($handle);
        }

        return ['id' => (string) $response->json('id'), 'name' => (string) $response->json('name')];
    }

    /**
     * File backup di folder Drive, terbaru dulu.
     *
     * @return array<int, array{id: string, name: string, size: int, created_at: string}>
     */
    public function files(): array
    {
        $response = $this->api()->get(self::API.'/files', [
            'q' => "'{$this->folderId()}' in parents and trashed = false",
            'fields' => 'files(id,name,size,createdTime)',
            'orderBy' => 'createdTime desc',
            'pageSize' => 200,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Gagal membaca daftar backup di Google Drive: '.$response->body());
        }

        return collect($response->json('files', []))
            ->map(fn ($file) => [
                'id' => $file['id'],
                'name' => $file['name'],
                'size' => (int) ($file['size'] ?? 0),
                'created_at' => $file['createdTime'],
            ])
            ->all();
    }

    public function download(string $fileId, string $destination): void
    {
        $response = $this->api()->timeout(600)->sink($destination)->get(self::API.'/files/'.rawurlencode($fileId), ['alt' => 'media']);

        if ($response->failed()) {
            @unlink($destination);
            throw new RuntimeException('Gagal mengunduh backup dari Google Drive.');
        }
    }

    public function delete(string $fileId): void
    {
        $this->api()->delete(self::API.'/files/'.rawurlencode($fileId));
    }

    /**
     * Hapus backup di Drive yang lebih tua dari $days hari.
     */
    public function prune(int $days): int
    {
        if ($days <= 0) {
            return 0;
        }

        $deleted = 0;
        foreach ($this->files() as $file) {
            if (strtotime($file['created_at']) < now()->subDays($days)->timestamp) {
                $this->delete($file['id']);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Folder backup di Drive (dibuat sekali, dibuat ulang bila dihapus/dibuang ke sampah).
     */
    private function folderId(): string
    {
        $folderId = Setting::get('backup_gdrive_folder_id');
        if ($folderId) {
            $check = $this->api()->get(self::API.'/files/'.rawurlencode($folderId), ['fields' => 'id,trashed']);
            if ($check->successful() && ! $check->json('trashed')) {
                return $folderId;
            }
        }

        $response = $this->api()->post(self::API.'/files?fields=id', [
            'name' => config('services.google_drive.folder_name', 'TAD Backup'),
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);
        if ($response->failed() || ! $response->json('id')) {
            throw new RuntimeException('Gagal membuat folder backup di Google Drive: '.$response->body());
        }

        Setting::set('backup_gdrive_folder_id', $response->json('id'));

        return $response->json('id');
    }

    private function api(): PendingRequest
    {
        return Http::withToken($this->accessToken())->acceptJson()->timeout(60);
    }

    private function accessToken(): string
    {
        return Cache::remember($this->tokenCacheKey(), now()->addMinutes(50), function () {
            $refreshToken = $this->refreshToken();
            if (! $refreshToken) {
                throw new RuntimeException('Google Drive belum dihubungkan.');
            }

            $response = Http::asForm()->post(self::TOKEN_URL, [
                'client_id' => config('services.google_drive.client_id'),
                'client_secret' => config('services.google_drive.client_secret'),
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->failed() || ! $response->json('access_token')) {
                throw new RuntimeException('Akses Google Drive kedaluwarsa atau dicabut. Hubungkan ulang Google Drive di halaman Backup & Restore.');
            }

            return $response->json('access_token');
        });
    }

    private function refreshToken(): ?string
    {
        $encrypted = Setting::get('backup_gdrive_refresh_token');

        return $encrypted ? rescue(fn () => Crypt::decryptString($encrypted), null, false) : null;
    }

    private function tokenCacheKey(): string
    {
        return 'backup_gdrive_access_token';
    }
}
