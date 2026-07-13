<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Models\ServiceApplication;
use App\Observers\ServiceApplicationObserver;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Fix for older MySQL/MariaDB versions
        Schema::defaultStringLength(191);
        ServiceApplication::observe(ServiceApplicationObserver::class);
    }
}