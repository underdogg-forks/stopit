<?php

namespace Modules\Stopit\Repositories;

use Modules\Stopit\Models\Application;
use Modules\Stopit\Repositories\Contracts\ApplicationRepositoryContract;

class ApplicationRepository implements ApplicationRepositoryContract
{
    public function findByToken(string $hashedToken): ?Application
    {
        return Application::where('api_token', $hashedToken)->first();
    }

    public function insert(string $name, string $slug, string $hashedToken): Application
    {
        return Application::create([
            'name'       => $name,
            'slug'       => $slug,
            'api_token'  => $hashedToken,
        ]);
    }

    public function updateToken(int $id, string $hashedToken): bool
    {
        return Application::where('id', $id)
            ->update(['api_token' => $hashedToken]) > 0;
    }
}
