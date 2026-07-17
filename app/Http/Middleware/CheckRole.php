<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->role) {
            abort(403, 'Akses ditolak. Role akun tidak valid.');
        }

        // Cek status aktif terlebih dahulu — user nonaktif harus dilogout
        // sebelum dilakukan pemeriksaan role apapun.
        if (! $user->isActive()) {
            if ($request->expectsJson()) {
                abort(403, 'Akun tidak aktif. Silakan hubungi administrator.');
            }

            auth()->logout();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => 'Akun Anda sedang nonaktif.',
                ]);
        }

        if (! in_array($user->role->name, $roles, true)) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk halaman ini.');
        }

        return $next($request);
    }
}
