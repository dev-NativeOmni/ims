<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()
            ->with([
                'role',
                'teacherProfile',
                'parentProfile',
                'studentProfile',
            ])
            ->where('email', $validated['email'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return ApiResponse::error(
                message: 'Email atau password salah.',
                errors: [
                    'email' => [
                        'Email atau password salah.',
                    ],
                ],
                status: 422
            );
        }

        if (! $user->isActive()) {
            return ApiResponse::error(
                message: 'Akun tidak aktif. Hubungi admin.',
                status: 403
            );
        }

        $deviceName = $validated['device_name']
            ?? $request->userAgent()
            ?? 'HafizPlus API Client';

        $token = $user->createToken($deviceName)->plainTextToken;

        return ApiResponse::success(
            data: [
                'token_type' => 'Bearer',
                'access_token' => $token,
                'user' => (new UserResource($user))->resolve($request),
            ],
            message: 'Login berhasil.'
        );
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()
            ->load([
                'role',
                'teacherProfile',
                'parentProfile',
                'studentProfile',
            ]);

        return ApiResponse::success(
            data: [
                'user' => (new UserResource($user))->resolve($request),
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
}