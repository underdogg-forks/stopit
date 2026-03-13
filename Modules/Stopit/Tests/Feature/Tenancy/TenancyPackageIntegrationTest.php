<?php

namespace Tests\Feature\Tenancy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Enums\WorkspaceRole;
use Modules\Stopit\Models\Account;
use Modules\Stopit\Models\User;
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

        $this->user = User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->user->accounts()->attach($this->tenant1->id, ['role' => WorkspaceRole::ADMIN->value]);
        $this->user->accounts()->attach($this->tenant2->id, ['role' => WorkspaceRole::MEMBER->value]);
    }

    #[Test]
    public function it_implements_tenant_contract(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['domain' => 'test-tenant']);

        /* Act & Assert */
        $this->assertInstanceOf(Tenant::class, $account);
    }

    #[Test]
    public function it_returns_correct_tenant_identifier(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['domain' => 'my-workspace']);

        /* Act */
        $identifier = $account->getTenantIdentifier();

        /* Assert */
        $this->assertEquals('my-workspace', $identifier);
    }

    #[Test]
    public function it_returns_correct_tenant_key(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['domain' => 'test-domain']);

        /* Act */
        $key = $account->getTenantKey();

        /* Assert */
        $this->assertEquals('domain', $key);
    }

    #[Test]
    public function it_can_set_tenant_context_programmatically(): void
    {
        /* Arrange */
        $this->assertNull(Tenancy::getTenant());

        /* Act */
        Tenancy::setTenant($this->tenant1);

        /* Assert */
        $this->assertNotNull(Tenancy::getTenant());
        $this->assertEquals($this->tenant1->id, Tenancy::getTenant()->id);
        $this->assertEquals('gitman', Tenancy::getTenant()->getTenantIdentifier());
    }

    #[Test]
    public function it_can_check_if_tenant_is_active(): void
    {
        /* Arrange */
        $this->assertFalse(Tenancy::isActive());

        /* Act */
        Tenancy::setTenant($this->tenant1);

        /* Assert */
        $this->assertTrue(Tenancy::isActive());
    }

    #[Test]
    public function it_can_clear_tenant_context(): void
    {
        /* Arrange */
        Tenancy::setTenant($this->tenant1);
        $this->assertTrue(Tenancy::isActive());

        /* Act */
        Tenancy::clearTenant();

        /* Assert */
        $this->assertFalse(Tenancy::isActive());
        $this->assertNull(Tenancy::getTenant());
    }

    #[Test]
    public function it_can_switch_between_tenants(): void
    {
        /* Arrange */
        Tenancy::setTenant($this->tenant1);

        /* Act */
        Tenancy::setTenant($this->tenant2);

        /* Assert */
        $this->assertEquals('spotivel', Tenancy::getTenant()->getTenantIdentifier());
    }

    #[Test]
    public function it_persists_tenant_context_across_operations(): void
    {
        /* Arrange */
        Tenancy::setTenant($this->tenant1);

        /* Act */
        $first  = Tenancy::getTenant();
        $second = Tenancy::getTenant();

        /* Assert */
        $this->assertEquals($this->tenant1->id, $first->id);
        $this->assertEquals($this->tenant1->id, $second->id);
    }

    #[Test]
    public function it_allows_retrieval_of_inactive_tenant(): void
    {
        /* Arrange */
        $inactiveTenant = Account::factory()->create([
            'name'      => 'Inactive Workspace',
            'domain'    => 'inactive',
            'is_active' => false,
        ]);
        $baseDomain = config('tenancy.central_domains')[0] ?? 'stopit.dev';

        /* Act */
        $response = $this->get('/', ['HTTP_HOST' => "inactive.{$baseDomain}"]);

        /* Assert */
        $this->assertTrue(Tenancy::isActive());
        $this->assertEquals('inactive', Tenancy::getTenant()->getTenantIdentifier());
    }

    #[Test]
    public function it_uses_domain_as_tenant_identifier(): void
    {
        /* Arrange */
        $account = Account::factory()->create(['domain' => 'unique-domain-123']);

        /* Act */
        $identifier = $account->getTenantIdentifier();

        /* Assert */
        $this->assertEquals($account->domain, $identifier);
    }

    #[Test]
    public function it_allows_multiple_accounts_with_different_domains(): void
    {
        /* Arrange */
        $accounts = Account::factory()->count(5)->create();

        /* Act */
        $domains = $accounts->pluck('domain')->toArray();

        /* Assert */
        $this->assertEquals(count($domains), count(array_unique($domains)));
    }

    #[Test]
    public function it_has_correct_relationships(): void
    {
        /* Arrange & Act */
        $applications = $this->tenant1->applications;
        $users        = $this->tenant1->users;

        /* Assert */
        $this->assertCount(0, $applications);
        $this->assertCount(1, $users);
        $this->assertEquals($this->user->id, $users->first()->id);
    }

    #[Test]
    public function it_can_find_tenant_by_domain(): void
    {
        /* Arrange */

        /* Act */
        $found = Account::where('domain', 'gitman')->first();

        /* Assert */
        $this->assertNotNull($found);
        $this->assertEquals($this->tenant1->id, $found->id);
    }

    #[Test]
    public function it_starts_with_null_tenant_context_on_fresh_request(): void
    {
        /* Arrange */
        Tenancy::clearTenant();

        /* Act */
        $tenant = Tenancy::getTenant();

        /* Assert */
        $this->assertNull($tenant);
        $this->assertFalse(Tenancy::isActive());
    }

    #[Test]
    public function it_has_model_configuration_matching_config(): void
    {
        /* Arrange & Act */
        $configuredModel  = config('tenancy.tenant_model');
        $configuredColumn = config('tenancy.tenant_column');

        /* Assert */
        $this->assertEquals(\Modules\Stopit\Models\Account::class, $configuredModel);
        $this->assertEquals('domain', $configuredColumn);
    }
}