<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ClassRoomController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HafalanRecordController;
use App\Http\Controllers\HafalanTargetController;
use App\Http\Controllers\MurajaahRecordController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\QuickInputController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SystemNotificationController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\QuranPdfController;
use App\Http\Controllers\QuranMushafController;
use App\Http\Controllers\AdabController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdmin\UserManagementController;

Route::get('/', function () {
    return view('welcome');
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

    Route::get('/supervisor/dashboard', [DashboardController::class, 'supervisor'])
        ->middleware('role:supervisor')
        ->name('supervisor.dashboard');

    /*
    |--------------------------------------------------------------------------
    | System Notifications
    |--------------------------------------------------------------------------
    | Index/show/read/delete boleh diakses semua user login.
    | Create/store/edit/update hanya admin dan super admin.
    |--------------------------------------------------------------------------
    */

    Route::prefix('system-notifications')
        ->name('system-notifications.')
        ->group(function () {
            Route::get('/', [SystemNotificationController::class, 'index'])
                ->name('index');

            Route::patch('/mark-all-read', [SystemNotificationController::class, 'markAllAsRead'])
                ->name('mark-all-read');

            Route::middleware(['role:super_admin,admin'])->group(function () {
                Route::get('/create', [SystemNotificationController::class, 'create'])
                    ->name('create');

                Route::post('/', [SystemNotificationController::class, 'store'])
                    ->name('store');

                Route::get('/{systemNotification}/edit', [SystemNotificationController::class, 'edit'])
                    ->name('edit');

                Route::patch('/{systemNotification}', [SystemNotificationController::class, 'update'])
                    ->name('update');
            });

            Route::get('/{systemNotification}', [SystemNotificationController::class, 'show'])
                ->name('show');

            Route::patch('/{systemNotification}/mark-as-read', [SystemNotificationController::class, 'markAsRead'])
                ->name('mark-as-read');

            Route::delete('/{systemNotification}', [SystemNotificationController::class, 'destroy'])
                ->name('destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Area
    |--------------------------------------------------------------------------
    */

    Route::middleware(['role:super_admin,admin'])->group(function () {
        Route::resource('programs', ProgramController::class);
        Route::resource('class-rooms', ClassRoomController::class);
        Route::resource('teachers', TeacherController::class);
        Route::resource('parents', ParentController::class);
        Route::get('students/export', [StudentController::class, 'export'])
            ->middleware('role:super_admin')
            ->name('students.export');
        Route::post('students/import', [StudentController::class, 'import'])
            ->middleware('role:super_admin')
            ->name('students.import');
        Route::resource('students', StudentController::class);

        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index');

        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])
            ->name('audit-logs.show');

        Route::prefix('database-backups')
            ->name('database-backups.')
            ->group(function () {
                Route::get('/', [DatabaseBackupController::class, 'index'])
                    ->name('index');

                Route::post('/', [DatabaseBackupController::class, 'store'])
                    ->name('store');

                Route::get('/{filename}/download', [DatabaseBackupController::class, 'download'])
                    ->name('download');

                Route::delete('/{filename}', [DatabaseBackupController::class, 'destroy'])
                    ->name('destroy');
            });
    });

    Route::middleware(['role:super_admin'])->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'edit', 'update']);
    });

        // Super Admin user management routes
        Route::prefix('superadmin')->name('superadmin.')->middleware(['role:super_admin'])->group(function () {
            Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
            Route::post('users/{id}/force-reset', [UserManagementController::class, 'forceReset'])->name('users.force-reset');
        });

    /*
    |--------------------------------------------------------------------------
    | Hafalan, Murajaah, Target, Quick Input
    |--------------------------------------------------------------------------
    */

    Route::middleware(['role:super_admin,admin,teacher'])->group(function () {
        Route::resource('hafalan-records', HafalanRecordController::class);
        Route::resource('murajaah-records', MurajaahRecordController::class);

        Route::patch('/hafalan-targets/{hafalanTarget}/complete', [HafalanTargetController::class, 'complete'])
            ->name('hafalan-targets.complete');

        Route::patch('/hafalan-targets/{hafalanTarget}/mark-missed', [HafalanTargetController::class, 'markMissed'])
            ->name('hafalan-targets.mark-missed');

        Route::resource('hafalan-targets', HafalanTargetController::class);

        Route::get('/quick-inputs', [QuickInputController::class, 'index'])
            ->name('quick-inputs.index');

        Route::post('/quick-inputs/hafalan', [QuickInputController::class, 'storeHafalan'])
            ->name('quick-inputs.hafalan.store');

        Route::post('/quick-inputs/murajaah', [QuickInputController::class, 'storeMurajaah'])
            ->name('quick-inputs.murajaah.store');
    });

    /*
    |--------------------------------------------------------------------------
    | Progress & Reports
    |--------------------------------------------------------------------------
    */

    Route::middleware(['role:super_admin,admin,teacher,parent,student'])->group(function () {
        Route::get('/progress', [ProgressController::class, 'index'])
            ->name('progress.index');

        Route::get('/progress/{student}', [ProgressController::class, 'show'])
            ->name('progress.show');

        Route::get('/reports', [ReportController::class, 'index'])
            ->name('reports.index');

        Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])
            ->name('reports.export.csv');

        Route::get('/reports/student/{student}', [ReportController::class, 'student'])
            ->name('reports.student');

        Route::get('/reports/student/{student}/export/csv', [ReportController::class, 'exportStudentCsv'])
            ->name('reports.student.export.csv');
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

    Route::get('/quran-pdf', [QuranPdfController::class, 'index'])
        ->name('quran.pdf');

    Route::post('/quran-pdf/config', [QuranPdfController::class, 'updateConfig'])
        ->middleware('role:super_admin,admin')
        ->name('quran.pdf.config');

    Route::get('/mushaf', [QuranMushafController::class, 'index'])
        ->name('quran.mushaf');

    /*
    |--------------------------------------------------------------------------
    | Adab
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:super_admin,admin,supervisor,teacher,parent,student'])->group(function () {
        Route::get('/adab', [AdabController::class, 'index'])->name('adab.index');
        Route::get('/adab/student/{student}', [AdabController::class, 'show'])->name('adab.show');
        Route::get('/adab/student/{student}/create', [AdabController::class, 'create'])->name('adab.create');
        Route::post('/adab/student/{student}', [AdabController::class, 'store'])->name('adab.store');
        
        Route::middleware(['role:super_admin,admin,supervisor'])->group(function () {
            Route::delete('/adab/{adabRecord}', [AdabController::class, 'destroy'])->name('adab.destroy');
        });
    });
});

require __DIR__ . '/auth.php';
/*
|--------------------------------------------------------------------------
| Local Development API Tester
|--------------------------------------------------------------------------
|
| Hanya aktif di APP_ENV=local.
| Jangan dipakai sebagai halaman publik production.
|
*/
if (app()->environment('local', 'testing')) {
    Route::get('/dev/api-tester', function () {
        return view('dev.api-tester');
    })
        ->middleware(['auth', 'role:super_admin'])
        ->name('dev.api-tester');

    Route::get('/dev/api-docs', function () {
        return view('dev.api-docs');
    })
        ->middleware(['auth', 'role:super_admin'])
        ->name('dev.api-docs');

    Route::get('/dev/openapi.yaml', function () {
        $path = base_path('docs/api-v1-openapi.yaml');
        if (! file_exists($path)) {
            abort(404);
        }
        return response(file_get_contents($path), 200, [
            'Content-Type' => 'text/yaml',
        ]);
    })
        ->middleware(['auth', 'role:super_admin'])
        ->name('dev.openapi-yaml');
}