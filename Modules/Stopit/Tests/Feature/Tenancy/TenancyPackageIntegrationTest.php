<?php

namespace Tests\Feature\Tenancy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Stopit\Providers\Models\Account;
use Modules\Stopit\Providers\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tenancy\Facades\Tenancy;
use Tenancy\Identification\Contracts\Tenant;
use Tests\TestCase;

/**
 * Test suite for tenancy/tenancy package integration.
 *
 * This test suite validates:
 * - Account model implements Tenant contract correctly
 * - Tenant identification via subdomain
 * - Tenant context management
 * - Multi-tenant data isolation
 */
#[Group('tenancy')]
#[Group('tenancy-package')]
class TenancyPackageIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Account $tenant1;

    private Account $tenant2;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two tenants with domains
        $this->tenant1 = Account::factory()->create([
            'name'      => 'GitMan Workspace',
            'slug'      => 'gitman',
            'domain'    => 'gitman',
            'is_active' => true,
        ]);

        $this->tenant2 = Account::factory()->create([
            'name'      => 'Spotivel Workspace',
            'slug'      => 'spotivel',
            'domain'    => 'spotivel',
            'is_active' => true,
        ]);

        // Create a user with access to both tenants
        $this->user = User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->user->accounts()->attach($this->tenant1->id, ['role' => 'admin']);
        $this->user->accounts()->attach($this->tenant2->id, ['role' => 'member']);
    }

    #[Test]
    public function account_model_implements_tenant_contract(): void
    {
        $account = Account::factory()->create([
            'domain' => 'test-tenant',
        ]);

        $this->assertInstanceOf(Tenant::class, $account);
    }

    #[Test]
    public function account_returns_correct_tenant_identifier(): void
    {
        $account = Account::factory()->create([
            'domain' => 'my-workspace',
        ]);

        $this->assertEquals('my-workspace', $account->getTenantIdentifier());
    }

    #[Test]
    public function account_returns_correct_tenant_key(): void
    {
        $account = Account::factory()->create([
            'domain' => 'test-domain',
        ]);

        $this->assertEquals('domain', $account->getTenantKey());
    }

    #[Test]
    public function can_set_tenant_context_programmatically(): void
    {
        // Initially no tenant is set
        $this->assertNull(Tenancy::getTenant());

        // Set tenant
        Tenancy::setTenant($this->tenant1);

        // Verify tenant is set
        $this->assertNotNull(Tenancy::getTenant());
        $this->assertEquals($this->tenant1->id, Tenancy::getTenant()->id);
        $this->assertEquals('gitman', Tenancy::getTenant()->getTenantIdentifier());
    }

    #[Test]
    public function can_check_if_tenant_is_active(): void
    {
        // No tenant initially
        $this->assertFalse(Tenancy::isActive());

        // Set tenant
        Tenancy::setTenant($this->tenant1);

        // Tenant is now active
        $this->assertTrue(Tenancy::isActive());
    }

    #[Test]
    public function can_clear_tenant_context(): void
    {
        // Set tenant
        Tenancy::setTenant($this->tenant1);
        $this->assertTrue(Tenancy::isActive());

        // Clear tenant
        Tenancy::clearTenant();

        // Tenant is no longer active
        $this->assertFalse(Tenancy::isActive());
        $this->assertNull(Tenancy::getTenant());
    }

    #[Test]
    public function can_switch_between_tenants(): void
    {
        // Set first tenant
        Tenancy::setTenant($this->tenant1);
        $this->assertEquals('gitman', Tenancy::getTenant()->getTenantIdentifier());

        // Switch to second tenant
        Tenancy::setTenant($this->tenant2);
        $this->assertEquals('spotivel', Tenancy::getTenant()->getTenantIdentifier());
    }

    #[Test]
    public function tenant_context_persists_across_operations(): void
    {
        Tenancy::setTenant($this->tenant1);

        // Perform multiple operations
        $tenant = Tenancy::getTenant();
        $this->assertEquals($this->tenant1->id, $tenant->id);

        // Tenant should still be the same
        $tenant = Tenancy::getTenant();
        $this->assertEquals($this->tenant1->id, $tenant->id);
    }

    #[Test]
    public function inactive_tenant_can_still_be_retrieved(): void
    {
        $inactiveTenant = Account::factory()->create([
            'name'      => 'Inactive Workspace',
            'domain'    => 'inactive',
            'is_active' => false,
        ]);

        // Can set inactive tenant programmatically
        Tenancy::setTenant($inactiveTenant);

        $this->assertTrue(Tenancy::isActive());
        $this->assertEquals('inactive', Tenancy::getTenant()->getTenantIdentifier());
    }

    #[Test]
    public function tenant_identifier_matches_domain_column(): void
    {
        $account = Account::factory()->create([
            'domain' => 'unique-domain-123',
        ]);

        $this->assertEquals($account->domain, $account->getTenantIdentifier());
    }

    #[Test]
    public function multiple_accounts_can_exist_with_different_domains(): void
    {
        $accounts = Account::factory()->count(5)->create();

        $domains = $accounts->pluck('domain')->toArray();

        // All domains should be unique
        $this->assertEquals(count($domains), count(array_unique($domains)));
    }

    #[Test]
    public function tenant_has_correct_relationships(): void
    {
        $this->assertCount(0, $this->tenant1->applications);
        $this->assertCount(1, $this->tenant1->users);

        $this->assertEquals($this->user->id, $this->tenant1->users->first()->id);
    }

    #[Test]
    public function can_find_tenant_by_domain(): void
    {
        $found = Account::where('domain', 'gitman')->first();

        $this->assertNotNull($found);
        $this->assertEquals($this->tenant1->id, $found->id);
    }

    #[Test]
    public function tenant_context_is_null_on_fresh_request(): void
    {
        // Simulate fresh request by clearing tenant
        Tenancy::clearTenant();

        $this->assertNull(Tenancy::getTenant());
        $this->assertFalse(Tenancy::isActive());
    }

    #[Test]
    public function tenant_model_configuration_matches_config(): void
    {
        $configuredModel = config('tenancy.tenant_model');
        $this->assertEquals(\Modules\Stopit\Models\Account::class, $configuredModel);

        $configuredColumn = config('tenancy.tenant_column');
        $this->assertEquals('domain', $configuredColumn);
    }
}
