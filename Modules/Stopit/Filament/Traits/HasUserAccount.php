<?php

namespace Modules\Stopit\Filament\Traits;

trait HasUserAccount
{
    protected function getUserAccountId(): ?int
    {
        $user = auth()->user();
        if ( ! $user) {
            return null;
        }

        // Get the first account ordered by ID for consistency
        $account = $user->accounts()->orderBy('id')->first();

        return $account?->id;
    }
}
