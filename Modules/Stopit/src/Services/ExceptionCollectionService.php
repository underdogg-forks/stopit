<?php

namespace Modules\Stopit\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Stopit\Models\ExceptionRecord;
use Modules\Stopit\Contracts\ExceptionRepositoryContract;

class ExceptionCollectionService
{
    public function __construct(
        private ExceptionRepositoryContract $repository
    ) {}

    public function reportException(int $applicationId, $data): ExceptionRecord
    {
        if (empty($data->getExceptionClass())) {
            throw new InvalidArgumentException('Exception class is required');
        }

        if (empty($data->getMessage())) {
            throw new InvalidArgumentException('Message is required');
        }

        // Use Database transaction to handle race condition with unique constraint
        return DB::transaction(function () use ($applicationId, $data) {
            $existing = $this->repository->findByFingerprint(
                $applicationId,
                $data->getExceptionClass(),
                $data->getMessage()
            );

            if ($existing) {
                return $this->repository->incrementOccurrence($existing);
            }

            try {
                return $this->repository->insert($applicationId, $data);
            } catch (\Illuminate\Database\QueryException $e) {
                // If unique constraint violation occurs, retry finding the record
                if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                    $existing = $this->repository->findByFingerprint(
                        $applicationId,
                        $data->getExceptionClass(),
                        $data->getMessage()
                    );

                    if ($existing) {
                        return $this->repository->incrementOccurrence($existing);
                    }
                }

                throw $e;
            }
        });
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
