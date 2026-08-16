<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateController extends Controller
{
    /**
     * Start impersonating a user (Super Admin only).
     */
    public function start(Request $request, User $user): RedirectResponse
    {
        $currentUser = Auth::user();

        // Ensure current user is Super Admin or is already impersonating
        if (! $currentUser || ($currentUser->role?->name !== 'super_admin' && ! session()->has('impersonated_by'))) {
            abort(403, 'Hanya Super Admin yang dapat menggunakan fitur impersonasi.');
        }

        if ($currentUser->id === $user->id) {
            return back()->with('error', 'Anda sudah masuk dengan akun ini.');
        }

        // Save original admin ID if not already impersonating
        if (! session()->has('impersonated_by')) {
            session(['impersonated_by' => $currentUser->id]);
        }

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Berhasil masuk sebagai '.$user->name.' ('.$user->role?->display_name.').');
    }

    /**
     * Stop impersonating and return to the original Super Admin account.
     */
    public function stop(Request $request): RedirectResponse
    {
        if (! session()->has('impersonated_by')) {
            return redirect()->route('dashboard');
        }

        $originalUserId = session('impersonated_by');
        session()->forget('impersonated_by');

        $originalUser = User::find($originalUserId);

        if ($originalUser) {
            Auth::login($originalUser);

            return redirect()->route('users.index')->with('success', 'Kembali ke sesi Super Admin ('.$originalUser->name.').');
        }

        return redirect()->route('dashboard')->with('info', 'Sesi impersonasi diakhiri.');
    }
}
