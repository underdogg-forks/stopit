<?php

namespace Stopit\src\Providers\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use BelongsToAccount;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'workspaces')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    protected static function newFactory()
    {
        return \Stopit\src\Providers\Database\factories\UserFactory::new();
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
