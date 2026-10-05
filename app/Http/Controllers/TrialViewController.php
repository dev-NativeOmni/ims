<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom;
use App\Models\ParentProfile;
use App\Models\Role;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Support\ReadOnlyAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * "Lihat sebagai" untuk akun trial (lihat ReadOnlyAccess): pilih role apa pun, tetap lihat saja.
 */
class TrialViewController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $trial = $this->trialUser($request);
        abort_unless($trial, 403, 'Fitur ini hanya untuk akun Trial.');

        $role = Role::query()
            ->where('name', (string) $request->input('role'))
            ->where('name', '!=', ReadOnlyAccess::ROLE)
            ->first();
        if (! $role) {
            return back()->with('error', 'Role tidak ditemukan.');
        }

        if (in_array($role->name, ReadOnlyAccess::ACCOUNT_SCOPED_ROLES, true)) {
            $sample = self::sampleAccount($role);
            if (! $sample) {
                return back()->with('error', 'Belum ada akun aktif dengan data untuk role '.$role->display_name.'.');
            }

            Auth::login($sample);
            $request->session()->regenerate();
            session([
                ReadOnlyAccess::SESSION_KEY => $trial->id,
                ReadOnlyAccess::VIEW_ROLE_KEY => $role->name,
                'active_role_id' => $role->id,
            ]);

            return redirect()->route('dashboard');
        }

        // Role pimpinan: kembali ke akun trial sendiri dan pinjam tampilan role itu.
        if (Auth::id() !== $trial->id) {
            Auth::login($trial);
            $request->session()->regenerate();
        }
        session()->forget([ReadOnlyAccess::SESSION_KEY, 'active_role_id']);
        session([ReadOnlyAccess::VIEW_ROLE_KEY => $role->name]);

        return redirect()->route('dashboard');
    }

    /**
     * Akun trial asal: user yang login sendiri, atau pemilik sesi akun contoh.
     */
    private function trialUser(Request $request): ?User
    {
        $user = $request->user();
        if ($user?->isReadOnly()) {
            return $user;
        }

        $trialId = (int) session(ReadOnlyAccess::SESSION_KEY);
        $trial = $trialId > 0 ? User::find($trialId) : null;

        return $trial?->isReadOnly() && $trial->isActive() ? $trial : null;
    }

    /**
     * Akun contoh yang paling banyak datanya untuk role per akun: guru dengan murid bimbingan
     * terbanyak, orangtua dengan anak terbanyak, santri aktif pertama, wali/pendamping kelas.
     */
    public static function sampleAccount(Role $role): ?User
    {
        $hasRole = fn (Builder $users) => $users->where('status', 'active')
            ->where(fn ($q) => $q->where('role_id', $role->id)->orWhereHas('roles', fn ($r) => $r->where('roles.id', $role->id)));

        return match ($role->name) {
            'teacher' => TeacherProfile::query()
                ->whereHas('user', $hasRole)
                ->withCount(['students' => fn ($q) => $q->where('status', 'active')])
                ->orderByDesc('students_count')->orderBy('id')
                ->first()?->user,
            'parent' => ParentProfile::query()
                ->whereHas('user', $hasRole)
                ->withCount('students')
                ->orderByDesc('students_count')->orderBy('id')
                ->first()?->user,
            'student' => Student::query()
                ->where('status', 'active')
                ->whereHas('user', $hasRole)
                ->orderBy('id')
                ->first()?->user,
            'wali_kelas' => $hasRole(User::query())
                ->whereIn('id', ClassRoom::query()->whereNotNull('wali_kelas_user_id')->select('wali_kelas_user_id'))
                ->orderBy('id')
                ->first(),
            'pendamping_adab' => $hasRole(User::query())
                ->where(fn ($q) => $q->whereHas('pendampingClasses')
                    ->orWhereIn('id', ClassRoom::query()->whereNotNull('pendamping_adab_id')->select('pendamping_adab_id')))
                ->orderBy('id')
                ->first(),
            default => null,
        };
    }
}
