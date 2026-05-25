<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ClassRoomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HafalanRecordController;
use App\Http\Controllers\HafalanTargetController;
use App\Http\Controllers\InternalNotificationController;
use App\Http\Controllers\MurajaahRecordController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\QuickInputController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'redirect'])
        ->name('dashboard');

    Route::get('/super-admin/dashboard', [DashboardController::class, 'superAdmin'])
        ->middleware('role:super_admin')
        ->name('super-admin.dashboard');

    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->middleware('role:super_admin,admin')
        ->name('admin.dashboard');

    Route::get('/teacher/dashboard', [DashboardController::class, 'teacher'])
        ->middleware('role:teacher')
        ->name('teacher.dashboard');

    Route::get('/parent/dashboard', [DashboardController::class, 'parent'])
        ->middleware('role:parent')
        ->name('parent.dashboard');

    Route::get('/student/dashboard', [DashboardController::class, 'student'])
        ->middleware('role:student')
        ->name('student.dashboard');

    /*
    |--------------------------------------------------------------------------
    | Notifications - Primary Route
    |--------------------------------------------------------------------------
    | Route utama notifikasi internal.
    */
    Route::get('/notifications', [InternalNotificationController::class, 'index'])
        ->name('notifications.index');

    Route::post('/notifications/sync', [InternalNotificationController::class, 'sync'])
        ->name('notifications.sync');

    Route::patch('/notifications/mark-all-read', [InternalNotificationController::class, 'markAllAsRead'])
        ->name('notifications.mark-all-read');

    Route::patch('/notifications/{notification}/mark-as-read', [InternalNotificationController::class, 'markAsRead'])
        ->name('notifications.mark-as-read');

    Route::delete('/notifications/{notification}', [InternalNotificationController::class, 'destroy'])
        ->name('notifications.destroy');

    /*
    |--------------------------------------------------------------------------
    | Notifications - Compatibility Alias
    |--------------------------------------------------------------------------
    | Alias ini dibuat supaya navigation.blade.php yang memakai
    | system-notifications.* tidak error.
    */
    Route::get('/system-notifications', [InternalNotificationController::class, 'index'])
        ->name('system-notifications.index');

    Route::post('/system-notifications/sync', [InternalNotificationController::class, 'sync'])
        ->name('system-notifications.sync');

    Route::patch('/system-notifications/mark-all-read', [InternalNotificationController::class, 'markAllAsRead'])
        ->name('system-notifications.mark-all-read');

    Route::patch('/system-notifications/read-all', [InternalNotificationController::class, 'markAllAsRead'])
        ->name('system-notifications.read-all');

    Route::patch('/system-notifications/{notification}/mark-as-read', [InternalNotificationController::class, 'markAsRead'])
        ->name('system-notifications.mark-as-read');

    Route::patch('/system-notifications/{notification}/read', [InternalNotificationController::class, 'markAsRead'])
        ->name('system-notifications.read');

    Route::delete('/system-notifications/{notification}', [InternalNotificationController::class, 'destroy'])
        ->name('system-notifications.destroy');

    /*
    |--------------------------------------------------------------------------
    | Master Data - Super Admin & Admin
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:super_admin,admin'])->group(function () {
        Route::resource('programs', ProgramController::class);

        Route::resource('class-rooms', ClassRoomController::class)
            ->parameters([
                'class-rooms' => 'classRoom',
            ]);

        Route::resource('teachers', TeacherController::class);
        Route::resource('parents', ParentController::class);
        Route::resource('students', StudentController::class);

        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');

        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])
            ->name('audit-logs.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Hafalan, Murajaah, Target, Quick Input - Admin & Teacher
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:super_admin,admin,teacher'])->group(function () {
        Route::get('/quick-inputs', [QuickInputController::class, 'index'])
            ->name('quick-inputs.index');

        Route::post('/quick-inputs/hafalan', [QuickInputController::class, 'storeHafalan'])
            ->name('quick-inputs.hafalan.store');

        Route::post('/quick-inputs/murajaah', [QuickInputController::class, 'storeMurajaah'])
            ->name('quick-inputs.murajaah.store');

        Route::resource('hafalan-records', HafalanRecordController::class)
            ->parameters([
                'hafalan-records' => 'hafalanRecord',
            ]);

        Route::resource('murajaah-records', MurajaahRecordController::class)
            ->parameters([
                'murajaah-records' => 'murajaahRecord',
            ]);

        Route::patch('/hafalan-targets/{hafalanTarget}/complete', [HafalanTargetController::class, 'complete'])
            ->name('hafalan-targets.complete');

        Route::resource('hafalan-targets', HafalanTargetController::class)
            ->parameters([
                'hafalan-targets' => 'hafalanTarget',
            ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Reports - All Authenticated Roles
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:super_admin,admin,teacher,parent,student'])->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])
            ->name('reports.index');

        Route::get('/reports/student/{student}', [ReportController::class, 'student'])
            ->name('reports.student');

        Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])
            ->name('reports.export.csv');
    });

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

require __DIR__.'/auth.php';