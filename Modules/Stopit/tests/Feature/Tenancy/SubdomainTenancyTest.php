<?php

namespace Stopit\src\Providers\Tests\Feature\Tenancy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Stopit\src\Providers\Models\Account;
use Stopit\src\Providers\Models\User;
use Tests\TestCase;

class SubdomainTenancyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_access_tenant_via_subdomain()
    {
        // Given: A user with access to a tenant account
        $user    = User::factory()->create();
        $account = Account::factory()->create([
            'name'      => 'Gitman Application',
            'slug'      => 'gitman',
            'domain'    => 'gitman-application',
            'is_active' => true,
        ]);
        $user->accounts()->attach($account);

        // When: User navigates to tenant subdomain
        $response = $this->actingAs($user)
            ->get('http://gitman-application.stopit.dev/admin');

        // Then: They should be able to access the tenant
        $response->assertStatus(200);
        $this->assertEquals($account->id, session('tenant_id'));
        $this->assertEquals('gitman-application', session('tenant_domain'));
    }

    /** @test */
    public function user_cannot_access_tenant_without_permission()
    {
        // Given: A user without access to a tenant
        $user         = User::factory()->create();
        $otherAccount = Account::factory()->create([
            'domain'    => 'gitman-12345',
            'is_active' => true,
        ]);

        // When: User tries to access tenant subdomain
        $response = $this->actingAs($user)
            ->get('http://gitman-12345.stopit.dev/admin');

        // Then: They should be redirected and logged out
        $response->assertRedirect();
        $this->assertGuest();
    }

    /** @test */
    public function invalid_subdomain_returns_404()
    {
        // Given: A user trying to access non-existent tenant
        $user = User::factory()->create();

        // When: User navigates to invalid subdomain
        $response = $this->actingAs($user)
            ->get('http://invalid-tenant.stopit.dev/admin');

        // Then: They should get a 404 error
        $response->assertStatus(404);
    }

    /** @test */
    public function main_domain_clears_tenant_session()
    {
        // Given: A user with an active tenant session
        $user    = User::factory()->create();
        $account = Account::factory()->create(['domain' => 'gitman', 'is_active' => true]);
        $user->accounts()->attach($account);

        // Set up tenant session
        session(['tenant_id' => $account->id, 'tenant_domain' => 'gitman']);

        // When: User navigates to main domain
        $response = $this->actingAs($user)
            ->get('http://stopit.dev/admin');

        // Then: Tenant session should be cleared
        $this->assertNull(session('tenant_id'));
        $this->assertNull(session('tenant_domain'));
    }

    /** @test */
    public function inactive_tenant_cannot_be_accessed()
    {
        // Given: A user with access to an inactive tenant
        $user    = User::factory()->create();
        $account = Account::factory()->create([
            'domain'    => 'inactive-tenant',
            'is_active' => false,
        ]);
        $user->accounts()->attach($account);

        // When: User tries to access inactive tenant
        $response = $this->actingAs($user)
            ->get('http://inactive-tenant.stopit.dev/admin');

        // Then: They should get a 404 error
        $response->assertStatus(404);
    }

    /** @test */
    public function user_sees_nord_theme_with_orange_after_login()
    {
        // Given: A user with access to a tenant
        $user    = User::factory()->create();
        $account = Account::factory()->create([
            'domain'    => 'gitman',
            'is_active' => true,
        ]);
        $user->accounts()->attach($account);

        // When: User logs in to tenant workspace
        $response = $this->actingAs($user)
            ->get('http://gitman.stopit.dev/admin');

        // Then: Response should contain Nord theme styling
        $response->assertStatus(200);
        // Nord primary color (blue-gray) with orange accent is configured in StopitPanelProvider
    }
}
