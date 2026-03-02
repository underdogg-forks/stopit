<?php

namespace Stopit\src\Providers\Repositories\Contracts;

use Modules\Core\Contracts\RepositoryContract;
use Stopit\src\Providers\Models\Application;

interface ApplicationRepositoryContract extends RepositoryContract
{
    public function findByToken(string $hashedToken): ?Application;

    public function insert(string $name, string $slug, string $hashedToken): Application;

    public function updateToken(int $id, string $hashedToken): bool;
}
