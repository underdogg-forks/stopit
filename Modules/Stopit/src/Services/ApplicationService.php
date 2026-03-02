<?php

namespace Stopit\src\Providers\Services;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Stopit\src\Providers\Models\Application;
use Stopit\src\Providers\Repositories\Contracts\ApplicationRepositoryContract;
use Stopit\src\Providers\src\DTOs\ApplicationData;
use RuntimeException;

class ApplicationService
{
    public function __construct(
        private ApplicationRepositoryContract $repository
    ) {}

    public function generateToken(): string
    {
        return Str::random(64);
    }

    public function validateToken(string $plainToken): ?Application
    {
        if (empty($plainToken)) {
            return null;
        }

        $hashedToken = $this->hashToken($plainToken);

        return $this->repository->findByToken($hashedToken);
    }

    public function createApplication(ApplicationData $data): array
    {
        if (empty($data->getName()) || empty($data->getSlug())) {
            throw new InvalidArgumentException('Name and slug are required');
        }

        $plainToken  = $this->generateToken();
        $hashedToken = $this->hashToken($plainToken);

        $application = $this->repository->insert(
            $data->getName(),
            $data->getSlug(),
            $hashedToken
        );

        // Attach application to the account
        $application->accounts()->attach($data->getAccountId());

        return [
            'application' => $application,
            'plain_token' => $plainToken,
        ];
    }

    public function regenerateToken(int $applicationId): string
    {
        $plainToken  = $this->generateToken();
        $hashedToken = $this->hashToken($plainToken);

        $success = $this->repository->updateToken($applicationId, $hashedToken);

        if ( ! $success) {
            throw new RuntimeException("Failed to regenerate token for application ID: {$applicationId}");
        }

        return $plainToken;
    }

    private function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
