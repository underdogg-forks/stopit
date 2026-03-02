<?php

namespace Modules\Stopit;

use Illuminate\Support\ServiceProvider;
use Modules\Stopit\Repositories\ApplicationRepository;
use Modules\Stopit\Repositories\Contracts\ApplicationRepositoryContract;
use Modules\Stopit\Repositories\Contracts\ExceptionRepositoryContract;
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
        $this->loadMigrationsFrom(__DIR__ . '/Database/migrations');

        $this->loadViewsFrom(__DIR__ . '/resources/views', 'stopit');

        $this->app->make('router')->group([
            'prefix'     => 'api',
            'middleware' => ['api'],
        ], function ($router) {
            require __DIR__ . '/routes/api.php';
        });
    }
}
