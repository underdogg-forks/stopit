<?php

namespace Modules\Stopit\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Traits\BelongsToAccount;

class Application extends Model
{
    use BelongsToAccount;
    use HasFactory;

    protected $fillable = [
        'account_id',
        'name',
        'slug',
        'api_token',
    ];

    protected $hidden = [
        'api_token',
    ];

    public function exceptions(): HasMany
    {
        return $this->hasMany(ExceptionRecord::class, 'application_id');
    }

    protected static function newFactory()
    {
        return \Modules\Stopit\Database\Factories\ApplicationFactory::new();
    }
}
