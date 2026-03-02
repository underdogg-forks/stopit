<?php

namespace Stopit\src\Providers\Services;

use Illuminate\Support\Collection;
use Stopit\src\Providers\Repositories\Contracts\ExceptionRepositoryContract;

class DashboardService
{
    public function __construct(
        private ExceptionRepositoryContract $repository
    ) {}

    public function getRecentExceptions(int $applicationId): Collection
    {
        return $this->repository->getRecent($applicationId, 10);
    }

    public function getCountBySeverity(int $applicationId): array
    {
        if ($applicationId === 0) {
            return [
                'info'     => 0,
                'warning'  => 0,
                'error'    => 0,
                'critical' => 0,
            ];
        }

        return $this->repository->getCountBySeverity($applicationId);
    }

    public function getCountByClass(int $applicationId): array
    {
        if ($applicationId === 0) {
            return [];
        }

        return $this->repository->getCountByClass($applicationId);
    }
}
