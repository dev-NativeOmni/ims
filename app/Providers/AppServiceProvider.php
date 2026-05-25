<?php

namespace App\Providers;

use App\Models\HafalanRecord;
use App\Models\HafalanTarget;
use App\Models\MurajaahRecord;
use App\Models\Student;
use App\Observers\HafalanRecordObserver;
use App\Policies\HafalanRecordPolicy;
use App\Policies\HafalanTargetPolicy;
use App\Policies\MurajaahRecordPolicy;
use App\Policies\StudentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        HafalanRecord::observe(HafalanRecordObserver::class);

        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(HafalanRecord::class, HafalanRecordPolicy::class);
        Gate::policy(MurajaahRecord::class, MurajaahRecordPolicy::class);
        Gate::policy(HafalanTarget::class, HafalanTargetPolicy::class);
    }
}