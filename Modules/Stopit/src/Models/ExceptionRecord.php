<?php

namespace Modules\Stopit\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Enums\Severity;

class ExceptionRecord extends Model
{
    use HasFactory;

    protected $table = 'exceptions';

    protected $guarded = [];

    // Relationships (alphabetical)

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    protected function casts(): array
    {
        return [
            'context'           => 'array',
            'first_occurred_at' => 'datetime',
            'is_resolved'       => 'boolean',
            'last_occurred_at'  => 'datetime',
            'occurrence_count'  => 'integer',
            'severity'          => Severity::class,
        ];
    }

    protected static function newFactory(): \Modules\Stopit\Database\Factories\ExceptionRecordFactory
    {
        return \Modules\Stopit\Database\Factories\ExceptionRecordFactory::new();
    }
}

