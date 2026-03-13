<?php

namespace Modules\Stopit;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Stopit\Contracts\ApplicationRepositoryContract;
use Modules\Stopit\Contracts\ExceptionRepositoryContract;
use Modules\Stopit\Repositories\ApplicationRepository;
use Modules\Stopit\Repositories\ExceptionRepository;

class StopitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ExceptionRepositoryContract::class, ExceptionRepository::class);
        $this->app->bind(ApplicationRepositoryContract::class, ApplicationRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Database/Migrations');

        $this->loadViewsFrom(__DIR__ . '/resources/views', 'stopit');

        Route::prefix('api')
            ->middleware(['api'])
            ->group(function () {
                require __DIR__ . '/Routes/Api/api.php';
            });
    }
}
