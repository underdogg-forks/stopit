<?php

namespace Modules\Stopit\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'api_token',
    ];

    protected $hidden = [
        'api_token',
    ];

    public function accounts(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'account_application')
            ->withTimestamps();
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(ExceptionRecord::class, 'application_id');
    }

    protected static function newFactory()
    {
        return \Modules\Stopit\Database\Factories\ApplicationFactory::new();
    }
}
