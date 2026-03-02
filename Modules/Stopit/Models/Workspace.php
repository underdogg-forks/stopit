<?php

namespace Modules\Stopit\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class Workspace extends Pivot
{
    protected $fillable = [
        'user_id',
        'account_id',
        'role',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
