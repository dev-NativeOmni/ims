<?php

namespace App\Providers;

use App\Models\HafalanRecord;
use App\Observers\HafalanRecordObserver;
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
    }
}