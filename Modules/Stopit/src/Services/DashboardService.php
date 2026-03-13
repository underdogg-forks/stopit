<?php

namespace Modules\Stopit\Services;

use Illuminate\Support\Collection;
use Modules\Core\Enums\Severity;
use Modules\Stopit\Contracts\ExceptionRepositoryContract;

class DashboardService
{
    public function __construct(
        private ExceptionRepositoryContract $repository
    ) {}

    public function getRecentExceptions(int $applicationId, ?int $accountId = null): Collection
    {
        if ($applicationId > 0) {
            return $this->repository->getRecent($applicationId, 10);
        }

        if ($accountId !== null && $accountId > 0) {
            return $this->repository->getRecentForAccount($accountId, 10);
        }

        return collect();
    }

    public function getCountBySeverity(int $applicationId, ?int $accountId = null): array
    {
        if ($applicationId > 0) {
            return $this->repository->getCountBySeverity($applicationId);
        }

        if ($accountId !== null && $accountId > 0) {
            return $this->repository->getCountBySeverityForAccount($accountId);
        }

        return array_reduce(
            Severity::cases(),
            fn ($carry, $severity) => $carry + [$severity->value => 0],
            []
        );
    }

    public function getCountByClass(int $applicationId, ?int $accountId = null): array
    {
        if ($applicationId > 0) {
            return $this->repository->getCountByClass($applicationId);
        }

        if ($accountId !== null && $accountId > 0) {
            return $this->repository->getCountByClassForAccount($accountId);
        }

        return [];
    }
}