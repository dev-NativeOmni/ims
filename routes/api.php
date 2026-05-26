<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\HafalanRecordController;
use App\Http\Controllers\Api\V1\HafalanTargetController;
use App\Http\Controllers\Api\V1\MurajaahRecordController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\SurahController;
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
            | Dashboard Summary API
            |--------------------------------------------------------------------------
            */
            Route::get('/dashboard/admin', [DashboardController::class, 'admin'])
                ->name('dashboard.admin');

            Route::get('/dashboard/teacher', [DashboardController::class, 'teacher'])
                ->name('dashboard.teacher');

            Route::get('/dashboard/parent', [DashboardController::class, 'parent'])
                ->name('dashboard.parent');

            Route::get('/dashboard/student', [DashboardController::class, 'student'])
                ->name('dashboard.student');

            /*
            |--------------------------------------------------------------------------
            | Surah & Ayah Readonly API
            |--------------------------------------------------------------------------
            */
            Route::get('/surahs', [SurahController::class, 'index'])
                ->name('surahs.index');

            Route::get('/surahs/{surah}/ayahs', [SurahController::class, 'ayahs'])
                ->whereNumber('surah')
                ->name('surahs.ayahs');

            Route::get('/surahs/{surah}', [SurahController::class, 'show'])
                ->whereNumber('surah')
                ->name('surahs.show');

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

            /*
            |--------------------------------------------------------------------------
            | Hafalan Records Readonly API
            |--------------------------------------------------------------------------
            */
            Route::get('/hafalan-records', [HafalanRecordController::class, 'index'])
                ->name('hafalan-records.index');

            Route::get('/hafalan-records/{hafalanRecord}', [HafalanRecordController::class, 'show'])
                ->whereNumber('hafalanRecord')
                ->name('hafalan-records.show');

            /*
            |--------------------------------------------------------------------------
            | Murajaah Records Readonly API
            |--------------------------------------------------------------------------
            */
            Route::get('/murajaah-records', [MurajaahRecordController::class, 'index'])
                ->name('murajaah-records.index');

            Route::get('/murajaah-records/{murajaahRecord}', [MurajaahRecordController::class, 'show'])
                ->whereNumber('murajaahRecord')
                ->name('murajaah-records.show');

            /*
            |--------------------------------------------------------------------------
            | Hafalan Targets Readonly API
            |--------------------------------------------------------------------------
            */
            Route::get('/hafalan-targets', [HafalanTargetController::class, 'index'])
                ->name('hafalan-targets.index');

            Route::get('/hafalan-targets/{hafalanTarget}', [HafalanTargetController::class, 'show'])
                ->whereNumber('hafalanTarget')
                ->name('hafalan-targets.show');
        });
    });