<?php

namespace Modules\Stopit\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'account_application')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspaces')
            ->withPivot('role')
            ->withTimestamps();
    }

    protected static function newFactory()
    {
        return \Modules\Stopit\Database\Factories\AccountFactory::new();
    }
}
