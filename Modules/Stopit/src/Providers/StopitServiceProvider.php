<?php

namespace Stopit\src\Providers;

use Illuminate\Support\ServiceProvider;
use Stopit\src\Providers\Repositories\ApplicationRepository;
use Stopit\src\Providers\Repositories\Contracts\ApplicationRepositoryContract;
use Stopit\src\Providers\Repositories\Contracts\ExceptionRepositoryContract;
use Stopit\src\Providers\Repositories\ExceptionRepository;

class StopitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ExceptionRepositoryContract::class, ExceptionRepository::class);
        $this->app->bind(ApplicationRepositoryContract::class, ApplicationRepository::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        $this->loadViewsFrom(__DIR__ . '/resources/views', 'stopit');

        $this->app->make('router')->group([
            'prefix'     => 'api',
            'middleware' => ['api'],
        ], function ($router) {
            require __DIR__ . '/Routes/Api/api.php';
        });
    }
}
