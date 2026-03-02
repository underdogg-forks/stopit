<?php

namespace Modules\Stopit\Services;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\Stopit\DTOs\ExceptionData;
use Modules\Stopit\Models\ExceptionRecord;
use Modules\Stopit\Repositories\Contracts\ExceptionRepositoryContract;

class ExceptionCollectionService
{
    public function __construct(
        private ExceptionRepositoryContract $repository
    ) {}

    public function reportException(int $applicationId, ExceptionData $data): ExceptionRecord
    {
        if (empty($data->getExceptionClass())) {
            throw new InvalidArgumentException('Exception class is required');
        }

        if (empty($data->getMessage())) {
            throw new InvalidArgumentException('Message is required');
        }

        $existing = $this->repository->findByFingerprint(
            $applicationId,
            $data->getExceptionClass(),
            $data->getMessage()
        );

        if ($existing) {
            return $this->repository->incrementOccurrence($existing);
        }

        return $this->repository->insert($applicationId, $data);
    }

    public function markAsResolved(int $exceptionId): bool
    {
        return $this->repository->markAsResolved($exceptionId);
    }

    public function getGroupedExceptions(int $applicationId, bool $unresolvedOnly = false): Collection
    {
        return $this->repository->getGrouped($applicationId, $unresolvedOnly);
    }
}
