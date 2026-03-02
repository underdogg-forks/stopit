<?php

namespace Modules\Stopit\Services;

use Illuminate\Support\Collection;
use Modules\Stopit\Repositories\Contracts\ExceptionRepositoryContract;

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
            // Return aggregated counts for all applications the user has access to
            // This should be scoped by account in the caller
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
            // Return aggregated counts for all applications the user has access to
            // This should be scoped by account in the caller
            return [];
        }

        return $this->repository->getCountByClass($applicationId);
    }

    public function getAggregatedCountBySeverity(array $applicationIds): array
    {
        $aggregated = [
            'info' => 0,
            'warning' => 0,
            'error' => 0,
            'critical' => 0,
        ];

        foreach ($applicationIds as $appId) {
            $counts = $this->repository->getCountBySeverity($appId);
            foreach ($counts as $severity => $count) {
                $aggregated[$severity] += $count;
            }
        }

        return $aggregated;
    }
}
