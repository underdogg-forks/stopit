<?php

namespace Modules\Stopit\Providers\src\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BelongsToManyAccounts
{
    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query->whereHas('accounts', function (Builder $q) use ($accountId) {
            $q->where('accounts.id', $accountId);
        });
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->whereHas('accounts', function (Builder $q) use ($userId) {
            $q->whereHas('users', function (Builder $userQuery) use ($userId) {
                $userQuery->where('users.id', $userId);
            });
        });
    }
}
