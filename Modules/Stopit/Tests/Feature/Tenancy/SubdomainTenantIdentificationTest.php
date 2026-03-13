<?php

namespace Tests\Feature\Tenancy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Enums\WorkspaceRole;
use Modules\Stopit\Models\Account;
use Modules\Stopit\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Test suite for subdomain-based tenant identification.
 *
 * This test suite validates:
 * - Tenant identification via subdomain in HTTP requests
 * - Central domain behavior (no tenant)
 * - Invalid subdomain handling
 * - Multi-tenant isolation
 */
#[Group('tenancy')]
#[Group('subdomain-identification')]
class SubdomainTenantIdentificationTest extends TestCase
{
    use RefreshDatabase;

    private Account $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Account::factory()->create([
            'name'      => 'GitMan Workspace',
            'slug'      => 'gitman',
            'domain'    => 'gitman',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'name'  => 'Tenant User',
            'email' => 'user@gitman.com',
        ]);

        $this->user->accounts()->attach($this->tenant->id, ['role' => WorkspaceRole::ADMIN->value]);
    }

    #[Test]
    public function it_can_access_application_via_subdomain(): void
    {
        /* Arrange */
        $baseDomain = config('tenancy.central_domains')[0] ?? 'stopit.dev';

        /* Act */
        $response = $this->get('/', ['HTTP_HOST' => "gitman.{$baseDomain}"]);

        /* Assert */
        $this->assertEquals(200, $response->status());
    }

    #[Test]
    public function it_has_no_tenant_context_on_central_domain(): void
    {
        /* Arrange */
        $baseDomain = config('tenancy.central_domains')[0] ?? 'stopit.dev';

        /* Act */
        $response = $this->get('/', ['HTTP_HOST' => $baseDomain]);

        /* Assert */
        $this->assertEquals(302, $response->status());
    }

    #[Test]
    public function it_returns_appropriate_response_for_invalid_subdomain(): void
    {
        /* Arrange */
        $baseDomain = config('tenancy.central_domains')[0] ?? 'stopit.dev';

        /* Act */
        $response = $this->get('/', ['HTTP_HOST' => "nonexistent.{$baseDomain}"]);

        /* Assert */
        $this->assertEquals(404, $response->status());
    }

    #[Test]
    public function it_extracts_subdomain_correctly(): void
    {
        /* Arrange */
        $host = 'gitman.stopit.dev';

        /* Act */
        $parts = explode('.', $host);

        /* Assert */
        $this->assertGreaterThanOrEqual(3, count($parts));
        $this->assertEquals('gitman', $parts[0]);
    }

    #[Test]
    public function it_identifies_tenant_from_domain_column(): void
    {
        /* Arrange */

        /* Act */
        $found = Account::where('domain', 'gitman')
            ->where('is_active', true)
            ->first();

        /* Assert */
        $this->assertNotNull($found);
        $this->assertEquals($this->tenant->id, $found->id);
        $this->assertEquals('gitman', $found->domain);
    }

    #[Test]
    public function it_handles_inactive_tenant_subdomain(): void
    {
        /* Arrange */
        Account::factory()->create([
            'name'      => 'Inactive Tenant',
            'domain'    => 'inactive',
            'is_active' => false,
        ]);

        /* Act */
        $found = Account::where('domain', 'inactive')
            ->where('is_active', true)
            ->first();

        /* Assert */
        $this->assertNull($found);
    }

    #[Test]
    public function it_maps_multiple_subdomains_to_different_tenants(): void
    {
        /* Arrange */
        $tenant2 = Account::factory()->create([
            'name'      => 'Spotivel Workspace',
            'domain'    => 'spotivel',
            'is_active' => true,
        ]);

        /* Act */
        $found1 = Account::where('domain', 'gitman')->first();
        $found2 = Account::where('domain', 'spotivel')->first();

        /* Assert */
        $this->assertNotNull($found1);
        $this->assertNotNull($found2);
        $this->assertNotEquals($found1->id, $found2->id);
    }

    #[Test]
    public function it_enforces_unique_domain_column(): void
    {
        /* Arrange */
        $this->assertNotNull($this->tenant);

        /* Act & Assert */
        $this->expectException(\Illuminate\Database\QueryException::class);
        Account::factory()->create(['domain' => 'gitman']);
    }

    #[Test]
    public function it_can_find_tenant_by_identifier(): void
    {
        /* Arrange */
        $identifier = $this->tenant->getTenantIdentifier();

        /* Act */
        $found = Account::where('domain', $identifier)->first();

        /* Assert */
        $this->assertNotNull($found);
        $this->assertEquals($this->tenant->id, $found->id);
    }

    #[Test]
    public function it_has_central_domains_configured(): void
    {
        /* Arrange & Act */
        $centralDomains = config('tenancy.central_domains');

        /* Assert */
        $this->assertIsArray($centralDomains);
        $this->assertNotEmpty($centralDomains);
        $this->assertContains('stopit.dev', $centralDomains);
    }

    #[Test]
    public function it_has_tenant_identification_driver_configured(): void
    {
        /* Arrange & Act */
        $driver = config('tenancy.identification_driver');

        /* Assert */
        $this->assertEquals('subdomain', $driver);
    }

    #[Test]
    public function it_has_tenant_model_configured_correctly(): void
    {
        /* Arrange & Act */
        $model = config('tenancy.tenant_model');

        /* Assert */
        $this->assertEquals(\Modules\Stopit\Models\Account::class, $model);
    }
}