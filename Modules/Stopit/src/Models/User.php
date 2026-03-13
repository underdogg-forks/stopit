<?php

namespace Modules\Stopit\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relationships (alphabetical)

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'workspaces')
            ->using(Workspace::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    protected static function newFactory(): \Modules\Stopit\Database\Factories\UserFactory
    {
        return \Modules\Stopit\Database\Factories\UserFactory::new();
    }
}

