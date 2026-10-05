<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Akun "lihat saja" untuk pengunjung dari luar (role `trial`).
 *
 * Role trial melihat aplikasi persis seperti role VIEWS_AS (lihat User::currentRole()), tapi
 * hanya boleh membuka halaman: semua permintaan selain GET/HEAD ditolak, begitu juga halaman
 * GET untuk tambah/ubah data, input, ekspor, unduh, cetak, dan pengaturan akun
 * (BlockReadOnlyWrites). Satu-satunya sumber aturan ini.
 */
class ReadOnlyAccess
{
    public const ROLE = 'trial';

    /** Role yang tampilannya dipinjam akun trial (menu, dashboard, cakupan data). */
    public const VIEWS_AS = 'headmaster';

    /** Aksi non-GET yang tetap boleh: keluar & berhenti impersonasi. */
    private const ALLOWED_WRITE_ROUTES = ['logout', 'impersonate.stop'];

    /**
     * Halaman GET yang ditolak (pola nama route): form tambah/ubah, input data, ekspor/unduh/cetak,
     * pesan WhatsApp ke wali, pengaturan akun & ganti role.
     */
    private const BLOCKED_ROUTE_PATTERNS = [
        '*.create', '*.edit', '*fast-input*', 'spreadsheet-input.*',
        '*export*', '*download*', '*print*', '*.pdf', 'quran.pdf', '*ummi-card*',
        'reports.whatsapp', 'profile.*', 'password.*', 'role.switch*',
    ];

    public static function applies(?User $user): bool
    {
        return (bool) $user?->isReadOnly();
    }

    public static function allows(Request $request): bool
    {
        $route = $request->route()?->getName();

        if (! $request->isMethodSafe()) {
            return $route !== null && in_array($route, self::ALLOWED_WRITE_ROUTES, true);
        }

        return $route === null || ! self::blocksRoute($route);
    }

    public static function blocksRoute(string $route): bool
    {
        foreach (self::BLOCKED_ROUTE_PATTERNS as $pattern) {
            if (fnmatch($pattern, $route)) {
                return true;
            }
        }

        return false;
    }
}
