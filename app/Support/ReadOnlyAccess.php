<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Akun "lihat saja" untuk pengunjung dari luar (role `trial`).
 *
 * Akun trial bisa "melihat sebagai" role apa pun (TrialViewController):
 * - role pimpinan: tampilan role itu dipinjam langsung (User::currentRole()), tanpa akun lain;
 * - role yang datanya per akun (ACCOUNT_SCOPED_ROLES): masuk sebagai satu akun contoh dari role
 *   itu dengan sesi SESSION_KEY, jadi tetap lihat saja.
 * Selama lihat saja, hanya halaman yang boleh dibuka: semua permintaan selain GET/HEAD ditolak,
 * begitu juga halaman GET untuk tambah/ubah data, input, ekspor, unduh, cetak, akun & sistem
 * (BlockReadOnlyWrites). Satu-satunya sumber aturan ini.
 */
class ReadOnlyAccess
{
    public const ROLE = 'trial';

    /** Role bawaan yang tampilannya dipinjam akun trial (menu, dashboard, cakupan data). */
    public const VIEWS_AS = 'headmaster';

    /** Sesi akun contoh: berisi id akun trial asal. */
    public const SESSION_KEY = 'read_only_trial_user_id';

    /** Sesi role yang sedang dipinjam akun trial (tanpa akun contoh). */
    public const VIEW_ROLE_KEY = 'read_only_view_role';

    /** Role yang datanya bergantung pada akun (murid bimbingan, anak, kelas) => perlu akun contoh. */
    public const ACCOUNT_SCOPED_ROLES = ['teacher', 'parent', 'student', 'wali_kelas', 'pendamping_adab'];

    /** Aksi non-GET yang tetap boleh: keluar, berhenti impersonasi, ganti "lihat sebagai". */
    private const ALLOWED_WRITE_ROUTES = ['logout', 'impersonate.stop', 'trial.view-as'];

    /**
     * Halaman GET yang ditolak (pola nama route): form tambah/ubah, input data, ekspor/unduh/cetak,
     * pesan WhatsApp ke wali, pengaturan akun & ganti role.
     */
    private const BLOCKED_ROUTE_PATTERNS = [
        '*.create', '*.edit', '*fast-input*', 'spreadsheet-input.*',
        '*export*', '*download*', '*print*', '*.pdf', 'quran.pdf', '*ummi-card*',
        'reports.whatsapp', 'profile.*', 'password.*', 'role.switch*',
        // Akun & sistem (mis. daftar user memuat password): tidak untuk pengunjung walau melihat sebagai Super Admin.
        'users.*', 'superadmin.*', 'database-backups.*', 'audit-logs.*', 'dev.*', 'impersonate.start', 'students.dapodik.*',
    ];

    public static function applies(?User $user): bool
    {
        return $user !== null && ($user->isReadOnly() || (int) session(self::SESSION_KEY) > 0);
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
