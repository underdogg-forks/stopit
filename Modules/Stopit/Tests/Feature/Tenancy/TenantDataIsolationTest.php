<?php

namespace Tests\Feature\Tenancy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Enums\WorkspaceRole;
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

        $this->user1 = User::factory()->create([
            'name'  => 'User One',
            'email' => 'user1@gitman.com',
        ]);
        $this->user1->accounts()->attach($this->tenant1->id, ['role' => WorkspaceRole::ADMIN->value]);

        $this->user2 = User::factory()->create([
            'name'  => 'User Two',
            'email' => 'user2@spotivel.com',
        ]);
        $this->user2->accounts()->attach($this->tenant2->id, ['role' => WorkspaceRole::ADMIN->value]);
    }

    protected function tearDown(): void
    {
        Tenancy::clearTenant();
        parent::tearDown();
    }

    #[Test]
    public function it_scopes_applications_to_tenant(): void
    {
        /* Arrange */
        $app1 = Application::factory()->create(['name' => 'GitMan App', 'slug' => 'gitman-app']);
        $app1->accounts()->attach($this->tenant1->id);

        $app2 = Application::factory()->create(['name' => 'Spotivel App', 'slug' => 'spotivel-app']);
        $app2->accounts()->attach($this->tenant2->id);

        /* Act */
        $tenant1Apps = $this->tenant1->applications;
        $tenant2Apps = $this->tenant2->applications;

        /* Assert */
        $this->assertCount(1, $tenant1Apps);
        $this->assertEquals('GitMan App', $tenant1Apps->first()->name);
        $this->assertCount(1, $tenant2Apps);
        $this->assertEquals('Spotivel App', $tenant2Apps->first()->name);
    }

    #[Test]
    public function it_prevents_tenant_from_accessing_other_tenant_applications(): void
    {
        /* Arrange */
        $app1 = Application::factory()->create(['name' => 'Tenant1 App']);
        $app1->accounts()->attach($this->tenant1->id);

        $app2 = Application::factory()->create(['name' => 'Tenant2 App']);
        $app2->accounts()->attach($this->tenant2->id);

        /* Act */
        $tenant1AppIds = $this->tenant1->applications->pluck('id')->toArray();
        $tenant2AppIds = $this->tenant2->applications->pluck('id')->toArray();

        /* Assert */
        $this->assertContains($app1->id, $tenant1AppIds);
        $this->assertNotContains($app2->id, $tenant1AppIds);
        $this->assertContains($app2->id, $tenant2AppIds);
        $this->assertNotContains($app1->id, $tenant2AppIds);
    }

    #[Test]
    public function it_allows_user_to_access_multiple_tenants(): void
    {
        /* Arrange */
        $this->user1->accounts()->attach($this->tenant2->id, ['role' => WorkspaceRole::MEMBER->value]);

        /* Act */
        $accountIds = $this->user1->accounts->pluck('id')->toArray();

        /* Assert */
        $this->assertCount(2, $this->user1->accounts);
        $this->assertContains($this->tenant1->id, $accountIds);
        $this->assertContains($this->tenant2->id, $accountIds);
    }

    #[Test]
    public function it_prevents_user_without_access_from_seeing_tenant_data(): void
    {
        /* Arrange */

        /* Act */
        $user1Accounts = $this->user1->accounts->pluck('id')->toArray();
        $user2Accounts = $this->user2->accounts->pluck('id')->toArray();

        /* Assert */
        $this->assertNotContains($this->tenant2->id, $user1Accounts);
        $this->assertNotContains($this->tenant1->id, $user2Accounts);
    }

    #[Test]
    public function it_uses_tenant_context_to_scope_data_queries(): void
    {
        /* Arrange */
        $app1 = Application::factory()->create(['name' => 'App 1']);
        $app1->accounts()->attach($this->tenant1->id);

        $app2 = Application::factory()->create(['name' => 'App 2']);
        $app2->accounts()->attach($this->tenant2->id);

        /* Act */
        Tenancy::setTenant($this->tenant1);
        $tenant1Apps = Application::whereHas('accounts', function ($query) {
            $query->where('accounts.id', Tenancy::getTenant()->id);
        })->get();

        /* Assert */
        $this->assertCount(1, $tenant1Apps);
        $this->assertEquals('App 1', $tenant1Apps->first()->name);
    }

    #[Test]
    public function it_changes_accessible_data_when_switching_tenant_context(): void
    {
        /* Arrange */
        $app1 = Application::factory()->create(['name' => 'Tenant1 App']);
        $app1->accounts()->attach($this->tenant1->id);

        $app2 = Application::factory()->create(['name' => 'Tenant2 App']);
        $app2->accounts()->attach($this->tenant2->id);

        /* Act - Tenant 1 */
        Tenancy::setTenant($this->tenant1);
        $tenant1Apps = Application::whereHas('accounts', function ($query) {
            $query->where('accounts.id', Tenancy::getTenant()->id);
        })->get();

        /* Act - Tenant 2 */
        Tenancy::setTenant($this->tenant2);
        $tenant2Apps = Application::whereHas('accounts', function ($query) {
            $query->where('accounts.id', Tenancy::getTenant()->id);
        })->get();

        /* Assert */
        $this->assertCount(1, $tenant1Apps);
        $this->assertEquals('Tenant1 App', $tenant1Apps->first()->name);
        $this->assertCount(1, $tenant2Apps);
        $this->assertEquals('Tenant2 App', $tenant2Apps->first()->name);
    }

    #[Test]
    public function it_allows_application_to_belong_to_multiple_tenants(): void
    {
        /* Arrange */
        $sharedApp = Application::factory()->create(['name' => 'Shared App']);

        /* Act */
        $sharedApp->accounts()->attach([$this->tenant1->id, $this->tenant2->id]);

        /* Assert */
        $this->assertTrue($this->tenant1->applications->contains($sharedApp->id));
        $this->assertTrue($this->tenant2->applications->contains($sharedApp->id));
    }

    #[Test]
    public function it_isolates_tenant_users(): void
    {
        /* Arrange */

        /* Act */
        $tenant1UserIds = $this->tenant1->users->pluck('id')->toArray();
        $tenant2UserIds = $this->tenant2->users->pluck('id')->toArray();

        /* Assert */
        $this->assertContains($this->user1->id, $tenant1UserIds);
        $this->assertNotContains($this->user2->id, $tenant1UserIds);
        $this->assertContains($this->user2->id, $tenant2UserIds);
        $this->assertNotContains($this->user1->id, $tenant2UserIds);
    }

    #[Test]
    public function it_scopes_user_role_to_specific_tenant(): void
    {
        /* Arrange */
        $this->user1->accounts()->attach($this->tenant2->id, ['role' => WorkspaceRole::VIEWER->value]);

        /* Act */
        $pivot1 = $this->user1->accounts()->where('accounts.id', $this->tenant1->id)->first()->pivot;
        $pivot2 = $this->user1->accounts()->where('accounts.id', $this->tenant2->id)->first()->pivot;

        /* Assert */
        $this->assertEquals(WorkspaceRole::ADMIN->value, $pivot1->role);
        $this->assertEquals(WorkspaceRole::VIEWER->value, $pivot2->role);
    }

    #[Test]
    public function it_marks_all_tenants_as_active_by_default(): void
    {
        /* Arrange */
        $tenantWithImplicitDefault = Account::create([
            'name'   => 'Default Active Workspace',
            'slug'   => 'default-active-workspace',
            'domain' => 'default-active-workspace',
        ]);

        /* Act */
        $tenantWithImplicitDefault = $tenantWithImplicitDefault->fresh();

        /* Assert */
        $this->assertTrue((bool) $tenantWithImplicitDefault->is_active);
    }

    #[Test]
    public function it_can_query_only_active_tenants(): void
    {
        /* Arrange */
        $inactiveTenant = Account::factory()->create([
            'domain'    => 'inactive',
            'is_active' => false,
        ]);

        /* Act */
        $activeTenants = Account::where('is_active', true)->get();

        /* Assert */
        $this->assertTrue($activeTenants->contains($this->tenant1->id));
        $this->assertTrue($activeTenants->contains($this->tenant2->id));
        $this->assertFalse($activeTenants->contains($inactiveTenant->id));
    }
}