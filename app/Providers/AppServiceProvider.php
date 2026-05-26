<?php

namespace App\Providers;

use App\Models\ClassRoom;
use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\ParentProfile;
use App\Models\Program;
use App\Models\Student;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Observers\HafalanRecordObserver;
use App\Observers\ModelAuditObserver;
use App\Policies\HafalanRecordPolicy;
use App\Policies\HafalanTargetPolicy;
use App\Policies\MurajaahRecordPolicy;
use App\Policies\StudentPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | API Rate Limiter
        |--------------------------------------------------------------------------
        |
        | Dipakai oleh middleware throttle:api di routes/api.php.
        | Tanpa ini, Laravel akan error:
        | "Rate limiter [api] is not defined"
        |
        */
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        /*
        |--------------------------------------------------------------------------
        | Domain Observers
        |--------------------------------------------------------------------------
        */
        HafalanRecord::observe(HafalanRecordObserver::class);

        /*
        |--------------------------------------------------------------------------
        | Audit Observers
        |--------------------------------------------------------------------------
        */
        User::observe(ModelAuditObserver::class);
        Program::observe(ModelAuditObserver::class);
        ClassRoom::observe(ModelAuditObserver::class);
        TeacherProfile::observe(ModelAuditObserver::class);
        ParentProfile::observe(ModelAuditObserver::class);
        Student::observe(ModelAuditObserver::class);
        HafalanRecord::observe(ModelAuditObserver::class);
        MurajaahRecord::observe(ModelAuditObserver::class);
        HafalanTarget::observe(ModelAuditObserver::class);

        /*
        |--------------------------------------------------------------------------
        | Policies
        |--------------------------------------------------------------------------
        */
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(HafalanRecord::class, HafalanRecordPolicy::class);
        Gate::policy(MurajaahRecord::class, MurajaahRecordPolicy::class);
        Gate::policy(HafalanTarget::class, HafalanTargetPolicy::class);
    }
}