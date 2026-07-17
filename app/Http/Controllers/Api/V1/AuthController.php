<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\NewAccessToken;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()
            ->with('role')
            ->where('username', $validated['username'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return ApiResponse::error(
                message: 'Username atau password tidak valid.',
                errors: [
                    'username' => [
                        'Username atau password tidak valid.',
                    ],
                ],
                status: 401
            );
        }

        if (($user->status ?? 'active') !== 'active') {
            return ApiResponse::error(
                message: 'Akun tidak aktif. Silakan hubungi administrator.',
                errors: [
                    'account' => [
                        'User account is inactive.',
                    ],
                ],
                status: 403
            );
        }

        $deviceName = $validated['device_name'] ?? 'API Client';

        $tokenExpirationDays = max(
            1,
            (int) config('hafizplus.api.token_expiration_days', 30)
        );

        $expiresAt = now()->addDays($tokenExpirationDays);

        $newAccessToken = $user->createToken($deviceName);

        $this->applyTokenExpiration($newAccessToken, $expiresAt);
        $this->pruneUserTokens($user);

        $user->load('role');

        return ApiResponse::success(
            data: [
                'access_token' => $newAccessToken->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => $expiresAt->toISOString(),
                'expires_in_days' => $tokenExpirationDays,
                'user' => $this->userPayload($user),
            ],
            message: 'Login berhasil.'
        );
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('role');

        return ApiResponse::success(
            data: [
                'user' => $this->userPayload($user),
                'current_token' => $this->currentTokenPayload($request),
            ],
            message: 'Data user aktif berhasil diambil.'
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();

        if ($token && method_exists($token, 'delete')) {
            $token->delete();
        }

        return ApiResponse::success(
            data: null,
            message: 'Logout berhasil.'
        );
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return ApiResponse::success(
            data: null,
            message: 'Logout semua device berhasil.'
        );
    }

    public function tokens(Request $request): JsonResponse
    {
        $currentToken = $request->user()->currentAccessToken();

        $tokens = $request->user()->tokens()->latest('id')->get()->map(function ($token) use ($currentToken) {
            return [
                'id' => $token->id,
                'name' => $token->name ?? 'API Client',
                'abilities' => $token->abilities,
                'is_current' => $currentToken && $currentToken->id === $token->id,
                'is_expired' => $token->expires_at ? $token->expires_at->isPast() : false,
                'last_used_at' => $token->last_used_at ? $token->last_used_at->toISOString() : null,
                'expires_at' => $token->expires_at ? $token->expires_at->toISOString() : null,
                'created_at' => $token->created_at ? $token->created_at->toISOString() : null,
                'updated_at' => $token->updated_at ? $token->updated_at->toISOString() : null,
            ];
        });

        $maxActiveTokens = max(
            1,
            (int) config('hafizplus.api.max_active_tokens_per_user', 5)
        );

        $tokenExpirationDays = max(
            1,
            (int) config('hafizplus.api.token_expiration_days', 30)
        );

        return ApiResponse::success(
            data: [
                'tokens' => $tokens,
            ],
            message: 'Daftar token aktif berhasil diambil.',
            meta: [
                'total' => $tokens->count(),
                'max_active_tokens' => $maxActiveTokens,
                'token_expiration_days' => $tokenExpirationDays,
            ]
        );
    }

    public function logoutOtherDevices(Request $request): JsonResponse
    {
        $currentToken = $request->user()->currentAccessToken();

        if ($currentToken) {
            $count = $request->user()->tokens()
                ->where('id', '!=', $currentToken->id)
                ->delete();
        } else {
            $count = 0;
        }

        return ApiResponse::success(
            data: [
                'revoked_tokens' => $count,
            ],
            message: 'Token device lain berhasil dicabut.'
        );
    }

    public function revokeToken(Request $request, string $token): JsonResponse
    {
        if (! ctype_digit($token)) {
            return ApiResponse::error(
                message: 'Token tidak ditemukan.',
                status: 404
            );
        }

        $tokenModel = $request->user()->tokens()->find((int) $token);

        if (! $tokenModel) {
            return ApiResponse::error(
                message: 'Token tidak ditemukan.',
                status: 404
            );
        }

        $tokenModel->delete();

        return ApiResponse::success(
            data: null,
            message: 'Token berhasil dicabut.'
        );
    }

    private function applyTokenExpiration(NewAccessToken $newAccessToken, mixed $expiresAt): void
    {
        if (! Schema::hasColumn('personal_access_tokens', 'expires_at')) {
            return;
        }

        $newAccessToken->accessToken
            ->forceFill([
                'expires_at' => $expiresAt,
            ])
            ->save();
    }

    private function pruneUserTokens(User $user): void
    {
        if (Schema::hasColumn('personal_access_tokens', 'expires_at')) {
            $user->tokens()
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->delete();
        }

        $maxActiveTokens = max(
            1,
            (int) config('hafizplus.api.max_active_tokens_per_user', 5)
        );

        $tokenIdsToKeep = $user->tokens()
            ->latest('id')
            ->limit($maxActiveTokens)
            ->pluck('id')
            ->all();

        if (! empty($tokenIdsToKeep)) {
            $user->tokens()
                ->whereNotIn('id', $tokenIdsToKeep)
                ->delete();
        }
    }

    private function currentTokenPayload(Request $request): array
    {
        $token = $request->user()?->currentAccessToken();

        if (! $token || ! method_exists($token, 'toArray')) {
            return [
                'name' => null,
                'last_used_at' => null,
                'expires_at' => null,
            ];
        }

        return [
            'name' => $token->name ?? null,
            'last_used_at' => $token->last_used_at?->toISOString(),
            'expires_at' => $token->expires_at?->toISOString(),
        ];
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'status' => $user->status ?? null,
            'role' => $user->role ? [
                'id' => $user->role->id,
                'name' => $user->role->name,
                'display_name' => $user->role->display_name ?? $user->role->name,
            ] : null,
        ];
    }
}
