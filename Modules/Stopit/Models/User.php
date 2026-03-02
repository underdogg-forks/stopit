<?php

namespace Modules\Stopit\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Core\Traits\BelongsToAccount;

class User extends Authenticatable implements FilamentUser
{
    use BelongsToAccount;
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'account_id',
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    protected static function newFactory()
    {
        return \Modules\Stopit\Database\Factories\UserFactory::new();
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
