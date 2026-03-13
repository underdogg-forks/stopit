<?php

namespace Modules\Stopit\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Core\Enums\WorkspaceRole;
use Tenancy\Identification\Contracts\Tenant;

class Account extends Model implements Tenant
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Relationships (alphabetical)

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'account_application')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspaces')
            ->using(Workspace::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    // Tenant contract methods

    /**
     * Get the unique identifier for the tenant.
     */
    public function getTenantIdentifier(): string
    {
        return $this->domain;
    }

    /**
     * Get the key name for the tenant identifier.
     */
    public function getTenantKey(): string
    {
        return 'domain';
    }

    protected static function newFactory(): \Modules\Stopit\Database\Factories\AccountFactory
    {
        return \Modules\Stopit\Database\Factories\AccountFactory::new();
    }
}

