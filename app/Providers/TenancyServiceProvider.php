<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Tenancy\Identification\Drivers\Http\Providers\IdentificationProvider;

class TenancyServiceProvider extends ServiceProvider
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
        // Register the tenancy identification provider
        $this->app->register(IdentificationProvider::class);
    }
}
