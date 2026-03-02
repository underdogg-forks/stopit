<?php

namespace Stopit\src\Providers\Repositories\Contracts;

use Illuminate\Support\Collection;
use Modules\Core\Contracts\RepositoryContract;
use Stopit\src\Providers\Models\ExceptionRecord;
use Stopit\src\Providers\src\DTOs\ExceptionData;

interface ExceptionRepositoryContract extends RepositoryContract
{
    public function insert(int $applicationId, ExceptionData $data): ExceptionRecord;

    public function incrementOccurrence(ExceptionRecord $exception): ExceptionRecord;

    public function findByFingerprint(int $applicationId, string $exceptionClass, string $message): ?ExceptionRecord;

    public function markAsResolved(int $id): bool;

    public function getGrouped(int $applicationId, bool $unresolvedOnly = false): Collection;

    public function getRecent(int $applicationId, int $limit = 10): Collection;

    public function getCountBySeverity(int $applicationId): array;

    public function getCountByClass(int $applicationId): array;

    public function getCountBySeverityForAccount(int $accountId): array;

    public function getCountByClassForAccount(int $accountId): array;

    public function getRecentForAccount(int $accountId, int $limit = 10): Collection;
}
