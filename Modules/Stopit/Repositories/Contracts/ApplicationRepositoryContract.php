<?php

namespace Modules\Stopit\Repositories\Contracts;

use Modules\Core\Contracts\RepositoryContract;
use Modules\Stopit\Models\Application;

interface ApplicationRepositoryContract extends RepositoryContract
{
    public function findByToken(string $hashedToken): ?Application;

    public function insert(int $accountId, string $name, string $slug, string $hashedToken): Application;

    public function updateToken(int $id, string $hashedToken): bool;
}
