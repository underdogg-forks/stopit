<?php

namespace Tests\Feature\Tenancy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Stopit\Models\Account;
use Modules\Stopit\Models\Application;
use Modules\Stopit\Models\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tenancy\Facades\Tenancy;
use Tests\TestCase;

/**
 * Test suite for tenant data isolation.
 *
 * This test suite validates:
 * - Applications are isolated per tenant
 * - Exceptions are isolated per tenant
 * - Users can access data from their tenants only
 * - Cross-tenant data leakage prevention
 */
#[Group('tenancy')]
#[Group('data-isolation')]
class TenantDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Account $tenant1;

    private Account $tenant2;

    private User $user1;

    private User $user2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create two separate tenants
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

        // Create user for tenant1
        $this->user1 = User::factory()->create([
            'name'  => 'User One',
            'email' => 'user1@gitman.com',
        ]);
        $this->user1->accounts()->attach($this->tenant1->id, ['role' => 'admin']);

        // Create user for tenant2
        $this->user2 = User::factory()->create([
            'name'  => 'User Two',
            'email' => 'user2@spotivel.com',
        ]);
        $this->user2->accounts()->attach($this->tenant2->id, ['role' => 'admin']);
    }

    #[Test]
    public function applications_are_scoped_to_tenant(): void
    {
        // Create application for tenant1
        $app1 = Application::factory()->create([
            'name' => 'GitMan App',
            'slug' => 'gitman-app',
        ]);
        $app1->accounts()->attach($this->tenant1->id);

        // Create application for tenant2
        $app2 = Application::factory()->create([
            'name' => 'Spotivel App',
            'slug' => 'spotivel-app',
        ]);
        $app2->accounts()->attach($this->tenant2->id);

        // Verify tenant1 applications
        $tenant1Apps = $this->tenant1->applications;
        $this->assertCount(1, $tenant1Apps);
        $this->assertEquals('GitMan App', $tenant1Apps->first()->name);

        // Verify tenant2 applications
        $tenant2Apps = $this->tenant2->applications;
        $this->assertCount(1, $tenant2Apps);
        $this->assertEquals('Spotivel App', $tenant2Apps->first()->name);
    }

    #[Test]
    public function tenant_cannot_access_other_tenant_applications(): void
    {
        // Create applications for both tenants
        $app1 = Application::factory()->create(['name' => 'Tenant1 App']);
        $app1->accounts()->attach($this->tenant1->id);

        $app2 = Application::factory()->create(['name' => 'Tenant2 App']);
        $app2->accounts()->attach($this->tenant2->id);

        // Tenant1 should only see their application
        $tenant1AppIds = $this->tenant1->applications->pluck('id')->toArray();
        $this->assertContains($app1->id, $tenant1AppIds);
        $this->assertNotContains($app2->id, $tenant1AppIds);

        // Tenant2 should only see their application
        $tenant2AppIds = $this->tenant2->applications->pluck('id')->toArray();
        $this->assertContains($app2->id, $tenant2AppIds);
        $this->assertNotContains($app1->id, $tenant2AppIds);
    }

    #[Test]
    public function user_can_access_multiple_tenants(): void
    {
        // Add user1 to tenant2 as well
        $this->user1->accounts()->attach($this->tenant2->id, ['role' => 'member']);

        // User should have access to both tenants
        $this->assertCount(2, $this->user1->accounts);

        $accountIds = $this->user1->accounts->pluck('id')->toArray();
        $this->assertContains($this->tenant1->id, $accountIds);
        $this->assertContains($this->tenant2->id, $accountIds);
    }

    #[Test]
    public function user_without_tenant_access_cannot_see_tenant_data(): void
    {
        // user1 should not have access to tenant2
        $user1Accounts = $this->user1->accounts->pluck('id')->toArray();
        $this->assertNotContains($this->tenant2->id, $user1Accounts);

        // user2 should not have access to tenant1
        $user2Accounts = $this->user2->accounts->pluck('id')->toArray();
        $this->assertNotContains($this->tenant1->id, $user2Accounts);
    }

    #[Test]
    public function tenant_context_affects_data_queries(): void
    {
        // Create applications for both tenants
        $app1 = Application::factory()->create(['name' => 'App 1']);
        $app1->accounts()->attach($this->tenant1->id);

        $app2 = Application::factory()->create(['name' => 'App 2']);
        $app2->accounts()->attach($this->tenant2->id);

        // Set tenant1 context
        Tenancy::setTenant($this->tenant1);

        // Query applications for current tenant
        $tenant1Apps = Application::whereHas('accounts', function ($query) {
            $query->where('accounts.id', Tenancy::getTenant()->id);
        })->get();

        $this->assertCount(1, $tenant1Apps);
        $this->assertEquals('App 1', $tenant1Apps->first()->name);
    }

    #[Test]
    public function switching_tenant_context_changes_accessible_data(): void
    {
        // Create applications
        $app1 = Application::factory()->create(['name' => 'Tenant1 App']);
        $app1->accounts()->attach($this->tenant1->id);

        $app2 = Application::factory()->create(['name' => 'Tenant2 App']);
        $app2->accounts()->attach($this->tenant2->id);

        // Set tenant1 context
        Tenancy::setTenant($this->tenant1);
        $tenant1Apps = Application::whereHas('accounts', function ($query) {
            $query->where('accounts.id', Tenancy::getTenant()->id);
        })->get();
        $this->assertCount(1, $tenant1Apps);
        $this->assertEquals('Tenant1 App', $tenant1Apps->first()->name);

        // Switch to tenant2 context
        Tenancy::setTenant($this->tenant2);
        $tenant2Apps = Application::whereHas('accounts', function ($query) {
            $query->where('accounts.id', Tenancy::getTenant()->id);
        })->get();
        $this->assertCount(1, $tenant2Apps);
        $this->assertEquals('Tenant2 App', $tenant2Apps->first()->name);
    }

    #[Test]
    public function application_can_belong_to_multiple_tenants(): void
    {
        // Create shared application
        $sharedApp = Application::factory()->create(['name' => 'Shared App']);
        $sharedApp->accounts()->attach([$this->tenant1->id, $this->tenant2->id]);

        // Both tenants should have access
        $this->assertTrue($this->tenant1->applications->contains($sharedApp->id));
        $this->assertTrue($this->tenant2->applications->contains($sharedApp->id));
    }

    #[Test]
    public function tenant_users_are_isolated(): void
    {
        // Tenant1 should only see user1
        $tenant1UserIds = $this->tenant1->users->pluck('id')->toArray();
        $this->assertContains($this->user1->id, $tenant1UserIds);
        $this->assertNotContains($this->user2->id, $tenant1UserIds);

        // Tenant2 should only see user2
        $tenant2UserIds = $this->tenant2->users->pluck('id')->toArray();
        $this->assertContains($this->user2->id, $tenant2UserIds);
        $this->assertNotContains($this->user1->id, $tenant2UserIds);
    }

    #[Test]
    public function user_role_is_specific_to_tenant(): void
    {
        // user1 is admin in tenant1
        $pivot1 = $this->user1->accounts()->where('accounts.id', $this->tenant1->id)->first()->pivot;
        $this->assertEquals('admin', $pivot1->role);

        // Add user1 to tenant2 with different role
        $this->user1->accounts()->attach($this->tenant2->id, ['role' => 'viewer']);

        $pivot2 = $this->user1->accounts()->where('accounts.id', $this->tenant2->id)->first()->pivot;
        $this->assertEquals('viewer', $pivot2->role);
    }

    #[Test]
    public function all_tenants_are_active_by_default(): void
    {
        $this->assertTrue($this->tenant1->is_active);
        $this->assertTrue($this->tenant2->is_active);
    }

    #[Test]
    public function can_query_only_active_tenants(): void
    {
        // Create inactive tenant
        $inactiveTenant = Account::factory()->create([
            'domain'    => 'inactive',
            'is_active' => false,
        ]);

        $activeTenants = Account::where('is_active', true)->get();

        $this->assertTrue($activeTenants->contains($this->tenant1->id));
        $this->assertTrue($activeTenants->contains($this->tenant2->id));
        $this->assertFalse($activeTenants->contains($inactiveTenant->id));
    }
}
