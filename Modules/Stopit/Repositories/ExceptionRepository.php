<?php

namespace Modules\Stopit\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Stopit\DTOs\ExceptionData;
use Modules\Stopit\Models\ExceptionRecord;
use Modules\Stopit\Repositories\Contracts\ExceptionRepositoryContract;

class ExceptionRepository implements ExceptionRepositoryContract
{
    public function insert(int $applicationId, ExceptionData $data): ExceptionRecord
    {
        $now = now();

        return ExceptionRecord::create([
            'application_id'    => $applicationId,
            'exception_class'   => $data->getExceptionClass(),
            'message'           => $data->getMessage(),
            'file'              => $data->getFile(),
            'line'              => $data->getLine(),
            'stack_trace'       => $data->getStackTrace(),
            'request_method'    => $data->getRequestMethod(),
            'request_url'       => $data->getRequestUrl(),
            'headers'           => $data->getHeaders(),
            'user_agent'        => $data->getUserAgent(),
            'ip_address'        => $data->getIpAddress(),
            'user_id'           => $data->getUserId(),
            'context'           => $data->getContext(),
            'severity'          => $data->getSeverity(),
            'occurrence_count'  => 1,
            'is_resolved'       => false,
            'first_occurred_at' => $now,
            'last_occurred_at'  => $now,
        ]);
    }

    public function incrementOccurrence(ExceptionRecord $exception): ExceptionRecord
    {
        $exception->increment('occurrence_count');
        $exception->update([
            'last_occurred_at' => now(),
        ]);

        return $exception->fresh();
    }

    public function findByFingerprint(int $applicationId, string $exceptionClass, string $message): ?ExceptionRecord
    {
        return ExceptionRecord::where('application_id', $applicationId)
            ->where('exception_class', $exceptionClass)
            ->where('message', $message)
            ->first();
    }

    public function markAsResolved(int $id): bool
    {
        return ExceptionRecord::where('id', $id)
            ->update(['is_resolved' => true]) > 0;
    }

    public function getGrouped(int $applicationId, bool $unresolvedOnly = false): Collection
    {
        $query = ExceptionRecord::where('application_id', $applicationId);

        if ($unresolvedOnly) {
            $query->where('is_resolved', false);
        }

        return $query->orderBy('last_occurred_at', 'desc')->get();
    }

    public function getRecent(int $applicationId, int $limit = 10): Collection
    {
        return ExceptionRecord::where('application_id', $applicationId)
            ->orderBy('last_occurred_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function getCountBySeverity(int $applicationId): array
    {
        $counts = ExceptionRecord::where('application_id', $applicationId)
            ->select('severity', DB::raw('COUNT(*) as count'))
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        return [
            'info'     => $counts['info'] ?? 0,
            'warning'  => $counts['warning'] ?? 0,
            'error'    => $counts['error'] ?? 0,
            'critical' => $counts['critical'] ?? 0,
        ];
    }

    public function getCountByClass(int $applicationId): array
    {
        return ExceptionRecord::where('application_id', $applicationId)
            ->select('exception_class', DB::raw('COUNT(*) as count'))
            ->groupBy('exception_class')
            ->orderBy('count', 'desc')
            ->pluck('count', 'exception_class')
            ->toArray();
    }
}
