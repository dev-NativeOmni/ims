<?php

namespace App\Http\Middleware;

use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiTokenIsNotExpired
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return ApiResponse::error(
                message: 'Unauthenticated.',
                errors: [],
                status: 401
            );
        }

        $token = $user->currentAccessToken();

        /*
        |--------------------------------------------------------------------------
        | Browser SPA / transient token guard
        |--------------------------------------------------------------------------
        |
        | Jika suatu saat API dipakai dengan cookie-based SPA Sanctum,
        | currentAccessToken bisa bukan PersonalAccessToken.
        | Untuk Bearer token mobile/API, token normalnya PersonalAccessToken.
        |
        */
        if (! $token instanceof PersonalAccessToken) {
            return $next($request);
        }

        if ($token->expires_at && $token->expires_at->isPast()) {
            $token->delete();

            return ApiResponse::error(
                message: 'Token sudah kedaluwarsa. Silakan login ulang.',
                errors: [
                    'token' => [
                        'Token expired.',
                    ],
                ],
                status: 401
            );
        }

        return $next($request);
    }
}