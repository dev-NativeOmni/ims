<?php

namespace App\Http\Middleware;

use App\Support\ReadOnlyAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun trial hanya boleh melihat: tolak tambah/ubah/hapus, unggah, unduh, ekspor & cetak
 * (aturannya di ReadOnlyAccess).
 */
class BlockReadOnlyWrites
{
    public const MESSAGE = 'Akun Trial hanya dapat melihat data. Tambah, ubah, hapus, unggah, unduh, dan cetak tidak tersedia.';

    public function handle(Request $request, Closure $next): Response
    {
        if (ReadOnlyAccess::applies($request->user()) && ! ReadOnlyAccess::allows($request)) {
            if ($request->expectsJson() || ! $request->isMethodSafe()) {
                abort(403, self::MESSAGE);
            }

            // Halaman terlarang dibuka lewat tautan: kembali ke halaman sebelumnya dengan pesan.
            $previous = url()->previous();

            return redirect()->to($previous !== $request->fullUrl() ? $previous : route('dashboard'))
                ->with('error', self::MESSAGE)
                ->with('read_only_blocked', true);
        }

        return $next($request);
    }
}
