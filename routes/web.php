<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ClassRoomController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HafalanRecordController;
use App\Http\Controllers\HafalanTargetController;
use App\Http\Controllers\MurajaahRecordController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\QuickInputController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SystemNotificationController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'redirect'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/super-admin/dashboard', [DashboardController::class, 'superAdmin'])
        ->middleware('role:super_admin')
        ->name('super-admin.dashboard');

    Route::get('/admin/dashboard', [DashboardController::class, 'admin'])
        ->middleware('role:admin')
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

    Route::get('/notifications', [SystemNotificationController::class, 'index'])
        ->name('system-notifications.index');

    Route::post('/notifications/refresh', [SystemNotificationController::class, 'refresh'])
        ->name('system-notifications.refresh');

    Route::patch('/notifications/mark-all-as-read', [SystemNotificationController::class, 'markAllAsRead'])
        ->name('system-notifications.mark-all-as-read');

    Route::patch('/notifications/{systemNotification}/mark-as-read', [SystemNotificationController::class, 'markAsRead'])
        ->name('system-notifications.mark-as-read');

    Route::delete('/notifications/{systemNotification}', [SystemNotificationController::class, 'destroy'])
        ->name('system-notifications.destroy');

    Route::middleware(['role:super_admin,admin'])->group(function () {
        Route::resource('programs', ProgramController::class);
        Route::resource('class-rooms', ClassRoomController::class);
        Route::resource('teachers', TeacherController::class);
        Route::resource('parents', ParentController::class);
        Route::resource('students', StudentController::class);

        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');

        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])
            ->name('audit-logs.show');
    });

    Route::middleware(['role:super_admin,admin,teacher'])->group(function () {
        Route::get('/quick-inputs', [QuickInputController::class, 'index'])
            ->name('quick-inputs.index');

        Route::post('/quick-inputs/hafalan', [QuickInputController::class, 'storeHafalan'])
            ->name('quick-inputs.hafalan.store');

        Route::post('/quick-inputs/murajaah', [QuickInputController::class, 'storeMurajaah'])
            ->name('quick-inputs.murajaah.store');

        Route::resource('hafalan-records', HafalanRecordController::class);
        Route::resource('murajaah-records', MurajaahRecordController::class);

        Route::get('/reports', [ReportController::class, 'index'])
            ->name('reports.index');

        Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])
            ->name('reports.export.csv');

        Route::patch('/hafalan-targets/{hafalanTarget}/complete', [HafalanTargetController::class, 'complete'])
            ->name('hafalan-targets.complete');

        Route::resource('hafalan-targets', HafalanTargetController::class)
            ->parameters([
                'hafalan-targets' => 'hafalanTarget',
            ]);
    });

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

require __DIR__.'/auth.php';