<?php

namespace App\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiExceptionRenderer
{
    public static function render(Throwable $exception, Request $request): ?JsonResponse
    {
        if (! self::shouldRenderJson($request)) {
            return null;
        }

        $requestId = $request->headers->get('X-Request-Id') ?: (string) Str::uuid();

        $meta = [
            'request_id' => $requestId,
            'timestamp' => now()->toISOString(),
        ];

        $response = match (true) {
            $exception instanceof ValidationException => self::validationError($exception, $meta),

            $exception instanceof AuthenticationException => ApiResponse::error(
                message: 'Unauthenticated.',
                errors: [],
                status: 401,
                meta: $meta
            ),

            $exception instanceof AuthorizationException => ApiResponse::error(
                message: 'Anda tidak memiliki izin untuk mengakses resource ini.',
                errors: [],
                status: 403,
                meta: $meta
            ),

            $exception instanceof ModelNotFoundException,
            $exception instanceof NotFoundHttpException => ApiResponse::error(
                message: 'Resource tidak ditemukan.',
                errors: [],
                status: 404,
                meta: $meta
            ),

            $exception instanceof MethodNotAllowedHttpException => ApiResponse::error(
                message: 'Method HTTP tidak diizinkan untuk endpoint ini.',
                errors: [],
                status: 405,
                meta: $meta
            ),

            $exception instanceof ThrottleRequestsException => ApiResponse::error(
                message: 'Terlalu banyak request. Silakan coba lagi nanti.',
                errors: [],
                status: 429,
                meta: $meta
            ),

            $exception instanceof HttpExceptionInterface => ApiResponse::error(
                message: self::httpExceptionMessage($exception),
                errors: [],
                status: $exception->getStatusCode(),
                meta: $meta
            ),

            default => self::serverError($exception, $meta),
        };

        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }

    private static function shouldRenderJson(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    private static function validationError(ValidationException $exception, array $meta): JsonResponse
    {
        return ApiResponse::error(
            message: 'Validasi gagal.',
            errors: $exception->errors(),
            status: 422,
            meta: $meta
        );
    }

    private static function serverError(Throwable $exception, array $meta): JsonResponse
    {
        Log::error('Unhandled API exception', [
            'request_id' => $meta['request_id'] ?? null,
            'exception' => $exception::class,
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        if (config('app.debug')) {
            $meta['debug'] = [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        return ApiResponse::error(
            message: 'Terjadi kesalahan pada server.',
            errors: [],
            status: 500,
            meta: $meta
        );
    }

    private static function httpExceptionMessage(HttpExceptionInterface $exception): string
    {
        $message = trim((string) $exception->getMessage());

        if ($message !== '') {
            return $message;
        }

        return match ($exception->getStatusCode()) {
            400 => 'Request tidak valid.',
            401 => 'Unauthenticated.',
            403 => 'Anda tidak memiliki izin untuk mengakses resource ini.',
            404 => 'Resource tidak ditemukan.',
            405 => 'Method HTTP tidak diizinkan untuk endpoint ini.',
            419 => 'Sesi telah kedaluwarsa.',
            429 => 'Terlalu banyak request. Silakan coba lagi nanti.',
            default => 'Terjadi kesalahan pada request.',
        };
    }
}
