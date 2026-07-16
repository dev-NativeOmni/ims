<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserManagementController extends Controller
{


    /**
     * Show a list of all users.
     */
    public function index(): View
    {
        $users = User::orderBy('id')->get();
        return view('superadmin.users.index', compact('users'));
    }

    /**
     * Force‑reset a user’s password.
     * Generates a random temporary password, stores its hash in the `password`
     * column and the plain text in `plain_password` for the super‑admin to see.
     */
    public function forceReset(Request $request, int $id)
    {
        $user = User::findOrFail($id);

        // Generate a secure random password (8 alphanumeric characters)
        $plain = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);

        $user->password = Hash::make($plain);
        $user->plain_password = $plain;
        $user->save();

        // Return back to the list with a flash message containing the plain password
        return redirect()->route('superadmin.users.index')
            ->with('status', "Password untuk {$user->username} berhasil direset. Plain password: {$plain}");
    }
}
