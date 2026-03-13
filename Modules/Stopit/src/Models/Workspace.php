<?php

namespace Modules\Stopit\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Modules\Core\Enums\WorkspaceRole;

class Workspace extends Pivot
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'role'       => WorkspaceRole::class,
            'updated_at' => 'datetime',
        ];
    }
}

