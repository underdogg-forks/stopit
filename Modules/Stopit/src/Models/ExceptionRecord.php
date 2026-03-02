<?php

namespace Stopit\src\Providers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Enums\Severity;

class ExceptionRecord extends Model
{
    use HasFactory;

    protected $table = 'exceptions';

    protected $fillable = [
        'application_id',
        'exception_class',
        'message',
        'file',
        'line',
        'stack_trace',
        'request_method',
        'request_url',
        'headers',
        'user_agent',
        'ip_address',
        'user_id',
        'context',
        'severity',
        'occurrence_count',
        'is_resolved',
        'first_occurred_at',
        'last_occurred_at',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    protected static function newFactory()
    {
        return \Stopit\src\Providers\Database\factories\ExceptionRecordFactory::new();
    }

    protected function casts(): array
    {
        return [
            'context'           => 'array',
            'severity'          => Severity::class,
            'occurrence_count'  => 'integer',
            'is_resolved'       => 'boolean',
            'first_occurred_at' => 'datetime',
            'last_occurred_at'  => 'datetime',
        ];
    }
}
