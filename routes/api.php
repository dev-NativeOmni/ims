<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\StudentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->as('api.v1.')
    ->middleware(['throttle:api'])
    ->group(function () {
        Route::post('/auth/login', [AuthController::class, 'login'])
            ->name('auth.login');

        Route::middleware(['auth:sanctum'])->group(function () {
            /*
            |--------------------------------------------------------------------------
            | Auth
            |--------------------------------------------------------------------------
            */
            Route::get('/auth/me', [AuthController::class, 'me'])
                ->name('auth.me');

            Route::post('/auth/logout', [AuthController::class, 'logout'])
                ->name('auth.logout');

            /*
            |--------------------------------------------------------------------------
            | Students Readonly API
            |--------------------------------------------------------------------------
            */
            Route::get('/students', [StudentController::class, 'index'])
                ->name('students.index');

            Route::get('/students/{student}/progress', [StudentController::class, 'progress'])
                ->whereNumber('student')
                ->name('students.progress');

            Route::get('/students/{student}', [StudentController::class, 'show'])
                ->whereNumber('student')
                ->name('students.show');
        });
    });