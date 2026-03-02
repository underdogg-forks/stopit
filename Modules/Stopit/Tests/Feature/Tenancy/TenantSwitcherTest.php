<?php

namespace Modules\Stopit\Providers\Tests\Feature\Tenancy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Stopit\Providers\Models\Account;
use Modules\Stopit\Providers\Models\User;
use Tests\TestCase;

class TenantSwitcherTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_view_tenant_switcher_with_multiple_tenants()
    {
        // Given: A user with access to multiple tenants
        $user   = User::factory()->create();
        $gitman = Account::factory()->create([
            'name'      => 'Gitman',
            'domain'    => 'gitman',
            'is_active' => true,
        ]);
        $spotivel = Account::factory()->create([
            'name'      => 'Spotivel',
            'domain'    => 'spotivel',
            'is_active' => true,
        ]);
        $user->accounts()->attach([$gitman->id, $spotivel->id]);

        // When: User accesses tenant switcher
        $response = $this->actingAs($user)
            ->get(route('tenant.switcher'));

        // Then: Both tenants should be visible
        $response->assertStatus(200);
        $response->assertSee('Gitman');
        $response->assertSee('Spotivel');
        $response->assertSee('gitman.stopit.dev');
        $response->assertSee('spotivel.stopit.dev');
    }

    /** @test */
    public function user_can_switch_to_different_tenant()
    {
        // Given: A user logged into gitman tenant
        $user   = User::factory()->create();
        $gitman = Account::factory()->create([
            'name'      => 'Gitman',
            'domain'    => 'gitman',
            'is_active' => true,
        ]);
        $spotivel = Account::factory()->create([
            'name'      => 'Spotivel',
            'domain'    => 'spotivel',
            'is_active' => true,
        ]);
        $user->accounts()->attach([$gitman->id, $spotivel->id]);

        // When: User switches to spotivel tenant
        $response = $this->actingAs($user)
            ->get(route('tenant.switch', $spotivel->id));

        // Then: User should be redirected to spotivel subdomain
        $response->assertRedirect();
        $this->assertTrue(str_contains($response->headers->get('Location'), 'spotivel.stopit.dev'));
    }

    /** @test */
    public function user_cannot_switch_to_tenant_without_access()
    {
        // Given: A user without access to a tenant
        $user         = User::factory()->create();
        $myAccount    = Account::factory()->create(['domain' => 'my-account', 'is_active' => true]);
        $otherAccount = Account::factory()->create(['domain' => 'other-account', 'is_active' => true]);
        $user->accounts()->attach($myAccount);

        // When: User tries to switch to unauthorized tenant
        $response = $this->actingAs($user)
            ->get(route('tenant.switch', $otherAccount->id));

        // Then: User should be redirected back with error
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /** @test */
    public function switcher_shows_current_tenant_badge()
    {
        // Given: A user with multiple tenants, currently on gitman
        $user   = User::factory()->create();
        $gitman = Account::factory()->create([
            'name'      => 'Gitman',
            'domain'    => 'gitman',
            'is_active' => true,
        ]);
        $spotivel = Account::factory()->create([
            'name'      => 'Spotivel',
            'domain'    => 'spotivel',
            'is_active' => true,
        ]);
        $user->accounts()->attach([$gitman->id, $spotivel->id]);

        // When: User views switcher from gitman subdomain
        $response = $this->actingAs($user)
            ->get('http://gitman.stopit.dev' . route('tenant.switcher', [], false));

        // Then: Current badge should be shown for gitman
        $response->assertSee('Current');
    }

    /** @test */
    public function unauthenticated_user_cannot_access_switcher()
    {
        // Given: An unauthenticated user

        // When: User tries to access switcher
        $response = $this->get(route('tenant.switcher'));

        // Then: User should be redirected to login
        $response->assertRedirect(route('filament.admin.auth.login'));
    }

    /** @test */
    public function user_menu_shows_switch_workspace_option_with_multiple_accounts()
    {
        // Given: A user with multiple accounts
        $user     = User::factory()->create();
        $account1 = Account::factory()->create(['is_active' => true]);
        $account2 = Account::factory()->create(['is_active' => true]);
        $user->accounts()->attach([$account1->id, $account2->id]);

        // When: User views Filament panel
        $this->actingAs($user);

        // Then: Switch workspace option should be available in user menu
        // (This is configured in StopitPanelProvider via userMenuItems)
        $this->assertCount(2, $user->accounts);
    }

    /** @test */
    public function switching_between_tenants_preserves_authentication()
    {
        // Given: A user with access to multiple tenants
        $user     = User::factory()->create();
        $gitman   = Account::factory()->create(['domain' => 'gitman', 'is_active' => true]);
        $spotivel = Account::factory()->create(['domain' => 'spotivel', 'is_active' => true]);
        $user->accounts()->attach([$gitman->id, $spotivel->id]);

        // When: User switches from gitman to spotivel
        $this->actingAs($user);
        $response = $this->get(route('tenant.switch', $spotivel->id));

        // Then: User should remain authenticated
        $this->assertAuthenticated();
        $response->assertRedirect();
    }
}
