<?php

namespace Modules\Stopit\Repositories\Contracts;

use Illuminate\Support\Collection;
use Modules\Core\Contracts\RepositoryContract;
use Modules\Stopit\DTOs\ExceptionData;
use Modules\Stopit\Models\ExceptionRecord;

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
}
