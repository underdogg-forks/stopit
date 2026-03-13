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
    /**
     * This provider is kept for backwards compatibility but intentionally
     * does not register bindings. Use Modules\Stopit\Providers\StopitServiceProvider instead.
     */
    public function register(): void
    {
        // No-op: bindings are handled by the canonical module service provider.
    }

    /**
     * This provider is kept for backwards compatibility but intentionally
     * does not register routes, migrations, or views to avoid duplication.
     */
    public function boot(): void
    {
        // No-op: bootstrapping is handled by the canonical module service provider.
    }
}
