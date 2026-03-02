<?php

namespace Stopit\src\Providers\Repositories;

use Stopit\src\Providers\Models\Application;
use Stopit\src\Providers\Repositories\Contracts\ApplicationRepositoryContract;

class ApplicationRepository implements ApplicationRepositoryContract
{
    public function findByToken(string $hashedToken): ?Application
    {
        return Application::where('api_token', $hashedToken)->first();
    }

    public function insert(string $name, string $slug, string $hashedToken): Application
    {
        return Application::create([
            'name'      => $name,
            'slug'      => $slug,
            'api_token' => $hashedToken,
        ]);
    }

    public function updateToken(int $id, string $hashedToken): bool
    {
        return Application::where('id', $id)
            ->update(['api_token' => $hashedToken]) > 0;
    }
}
