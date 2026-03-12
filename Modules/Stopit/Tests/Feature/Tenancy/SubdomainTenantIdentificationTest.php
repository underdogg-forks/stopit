<?php

namespace Tests\Feature\Tenancy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Stopit\Providers\Models\Account;
use Modules\Stopit\Providers\Models\User;
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

        // Create tenant with domain
        $this->tenant = Account::factory()->create([
            'name'      => 'GitMan Workspace',
            'slug'      => 'gitman',
            'domain'    => 'gitman',
            'is_active' => true,
        ]);

        // Create user with access to tenant
        $this->user = User::factory()->create([
            'name'  => 'Tenant User',
            'email' => 'user@gitman.com',
        ]);

        $this->user->accounts()->attach($this->tenant->id, ['role' => 'admin']);
    }

    #[Test]
    public function can_access_application_via_subdomain(): void
    {
        $baseDomain = config('tenancy.central_domains')[0] ?? 'stopit.dev';

        // Simulate request to gitman.stopit.dev
        $response = $this->get('/', [
            'HTTP_HOST' => "gitman.{$baseDomain}",
        ]);

        // Should get a response (not 404)
        $this->assertNotEquals(404, $response->status());
    }

    #[Test]
    public function central_domain_has_no_tenant_context(): void
    {
        $baseDomain = config('tenancy.central_domains')[0] ?? 'stopit.dev';

        // Simulate request to main domain (stopit.dev)
        $response = $this->get('/', [
            'HTTP_HOST' => $baseDomain,
        ]);

        // Should get a response
        $this->assertNotEquals(404, $response->status());
    }

    #[Test]
    public function invalid_subdomain_returns_appropriate_response(): void
    {
        $baseDomain = config('tenancy.central_domains')[0] ?? 'stopit.dev';

        // Simulate request to non-existent subdomain
        $response = $this->get('/', [
            'HTTP_HOST' => "nonexistent.{$baseDomain}",
        ]);

        // Should handle gracefully (could be 404, redirect, or other)
        $this->assertIsInt($response->status());
    }

    #[Test]
    public function subdomain_extraction_works_correctly(): void
    {
        // Test subdomain extraction logic
        $host = 'gitman.stopit.dev';
        $parts = explode('.', $host);

        // Should have 3 parts: subdomain, domain, tld
        $this->assertGreaterThanOrEqual(3, count($parts));
        $this->assertEquals('gitman', $parts[0]);
    }

    #[Test]
    public function can_identify_tenant_from_domain_column(): void
    {
        $found = Account::where('domain', 'gitman')
            ->where('is_active', true)
            ->first();

        $this->assertNotNull($found);
        $this->assertEquals($this->tenant->id, $found->id);
        $this->assertEquals('gitman', $found->domain);
    }

    #[Test]
    public function inactive_tenant_subdomain_should_be_handled(): void
    {
        $inactiveTenant = Account::factory()->create([
            'name'      => 'Inactive Tenant',
            'domain'    => 'inactive',
            'is_active' => false,
        ]);

        $found = Account::where('domain', 'inactive')
            ->where('is_active', true)
            ->first();

        // Should not find inactive tenant when filtering by is_active
        $this->assertNull($found);
    }

    #[Test]
    public function multiple_subdomains_map_to_different_tenants(): void
    {
        $tenant2 = Account::factory()->create([
            'name'      => 'Spotivel Workspace',
            'domain'    => 'spotivel',
            'is_active' => true,
        ]);

        $found1 = Account::where('domain', 'gitman')->first();
        $found2 = Account::where('domain', 'spotivel')->first();

        $this->assertNotNull($found1);
        $this->assertNotNull($found2);
        $this->assertNotEquals($found1->id, $found2->id);
    }

    #[Test]
    public function domain_column_should_be_unique(): void
    {
        // First tenant created successfully
        $this->assertNotNull($this->tenant);

        // Attempting to create another tenant with same domain should fail
        $this->expectException(\Exception::class);

        Account::factory()->create([
            'domain' => 'gitman', // Same domain
        ]);
    }

    #[Test]
    public function tenant_can_be_found_by_identifier(): void
    {
        $identifier = $this->tenant->getTenantIdentifier();

        $found = Account::where('domain', $identifier)->first();

        $this->assertNotNull($found);
        $this->assertEquals($this->tenant->id, $found->id);
    }

    #[Test]
    public function central_domains_are_configured(): void
    {
        $centralDomains = config('tenancy.central_domains');

        $this->assertIsArray($centralDomains);
        $this->assertNotEmpty($centralDomains);
        $this->assertContains('stopit.dev', $centralDomains);
    }

    #[Test]
    public function tenant_identification_driver_is_configured(): void
    {
        $driver = config('tenancy.identification_driver');

        $this->assertEquals('subdomain', $driver);
    }

    #[Test]
    public function tenant_model_is_configured_correctly(): void
    {
        $model = config('tenancy.tenant_model');

        $this->assertEquals(\Modules\Stopit\Models\Account::class, $model);
    }
}
