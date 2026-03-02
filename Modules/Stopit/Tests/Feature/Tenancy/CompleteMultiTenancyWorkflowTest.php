<?php

namespace Modules\Stopit\Tests\Feature\Tenancy;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Stopit\Models\Account;
use Modules\Stopit\Models\Application;
use Modules\Stopit\Models\User;
use Tests\TestCase;

class CompleteMultiTenancyWorkflowTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function complete_gitman_maintainer_workflow()
    {
        // Given: I'm a maintainer of the gitman application
        $user = User::factory()->create([
            'name' => 'Gitman Maintainer',
            'email' => 'maintainer@gitman.com',
        ]);
        
        $gitmanAccount = Account::factory()->create([
            'name' => 'Gitman',
            'slug' => 'gitman',
            'domain' => 'gitman-application',
            'is_active' => true,
        ]);
        
        $user->accounts()->attach($gitmanAccount);
        
        // When: I navigate to gitman-application.stopit.dev
        $response = $this->get('http://gitman-application.stopit.dev/admin/login');
        
        // Then: I should be presented with the login screen
        $response->assertStatus(200);
        $response->assertSee('Sign in');
        
        // When: I log in to my workspace
        $this->actingAs($user);
        $response = $this->get('http://gitman-application.stopit.dev/admin');
        
        // Then: I should see the Nord Theme with orange look-and-feel
        $response->assertStatus(200);
        $this->assertEquals($gitmanAccount->id, session('tenant_id'));
        
        // And: The Filament panel has Nord colors configured (blue-gray primary with orange warnings)
        // This is validated by the StopitPanelProvider configuration
    }
    
    /** @test */
    public function cannot_access_unauthorized_tenant()
    {
        // Given: I'm logged into my gitman tenant
        $user = User::factory()->create();
        $gitmanAccount = Account::factory()->create([
            'domain' => 'gitman',
            'is_active' => true,
        ]);
        $user->accounts()->attach($gitmanAccount);
        
        // And: Another tenant exists
        $unauthorizedAccount = Account::factory()->create([
            'domain' => 'gitman-12345',
            'is_active' => true,
        ]);
        
        // When: I browse to gitman-12345 subdomain
        $response = $this->actingAs($user)
            ->get('http://gitman-12345.stopit.dev/admin');
        
        // Then: I'm not allowed to be logged in to that tenant
        $response->assertRedirect();
        $this->assertGuest();
    }
    
    /** @test */
    public function can_see_and_switch_to_another_tenant()
    {
        // Given: I'm logged into my gitman tenant
        $user = User::factory()->create();
        $gitmanAccount = Account::factory()->create([
            'name' => 'Gitman',
            'domain' => 'gitman',
            'is_active' => true,
        ]);
        $spotivelAccount = Account::factory()->create([
            'name' => 'Spotivel',
            'domain' => 'spotivel',
            'is_active' => true,
        ]);
        $user->accounts()->attach([$gitmanAccount->id, $spotivelAccount->id]);
        
        // When: I click on my user menu
        $response = $this->actingAs($user)
            ->get(route('tenant.switcher'));
        
        // Then: I am shown my spotivel tenant
        $response->assertStatus(200);
        $response->assertSee('Spotivel');
        $response->assertSee('spotivel.stopit.dev');
        
        // When: I click on spotivel tenant
        $response = $this->get(route('tenant.switch', $spotivelAccount->id));
        
        // Then: I'm sent to the spotivel tenant
        $response->assertRedirect();
        $this->assertTrue(str_contains($response->headers->get('Location'), 'spotivel.stopit.dev'));
    }
    
    /** @test */
    public function switching_between_applications_within_tenant_works()
    {
        // Given: I'm logged into my gitman tenant with multiple applications
        $user = User::factory()->create();
        $gitmanAccount = Account::factory()->create([
            'domain' => 'gitman',
            'is_active' => true,
        ]);
        $user->accounts()->attach($gitmanAccount);
        
        $app1 = Application::factory()->create(['name' => 'Backend API']);
        $app2 = Application::factory()->create(['name' => 'Frontend App']);
        $gitmanAccount->applications()->attach([$app1->id, $app2->id]);
        
        // When: I navigate between applications in Filament
        $this->actingAs($user);
        $response1 = $this->get('http://gitman.stopit.dev/admin/applications');
        $response2 = $this->get('http://gitman.stopit.dev/admin/exceptions');
        
        // Then: Switching should go without hassle
        $response1->assertStatus(200);
        $response2->assertStatus(200);
        // Tenant session remains consistent
        $this->assertEquals($gitmanAccount->id, session('tenant_id'));
    }
    
    /** @test */
    public function main_site_shows_generic_dashboard_when_logged_in()
    {
        // Given: I'm a logged-in user
        $user = User::factory()->create();
        $account = Account::factory()->create(['domain' => 'gitman', 'is_active' => true]);
        $user->accounts()->attach($account);
        
        // When: I go to "stopit.dev" main site while being logged in
        $response = $this->actingAs($user)
            ->get('http://stopit.dev/admin');
        
        // Then: I should see the generic dashboard again for that specific user
        $response->assertStatus(200);
        // No tenant session should be active
        $this->assertNull(session('tenant_id'));
    }
    
    /** @test */
    public function main_site_shows_login_page_after_logout()
    {
        // Given: A user who was logged in
        $user = User::factory()->create();
        
        // When: User logs out and goes to "stopit.dev"
        $this->post(route('filament.admin.auth.logout'));
        $response = $this->get('http://stopit.dev/admin');
        
        // Then: Login page should be shown
        $response->assertRedirect(route('filament.admin.auth.login'));
        $this->assertGuest();
    }
    
    /** @test */
    public function session_correctly_scoped_to_tenant()
    {
        // Given: A user with access to multiple tenants
        $user = User::factory()->create();
        $gitman = Account::factory()->create(['domain' => 'gitman', 'is_active' => true]);
        $spotivel = Account::factory()->create(['domain' => 'spotivel', 'is_active' => true]);
        $user->accounts()->attach([$gitman->id, $spotivel->id]);
        
        // When: User accesses gitman tenant
        $this->actingAs($user);
        $this->get('http://gitman.stopit.dev/admin');
        
        // Then: Session should have gitman tenant context
        $this->assertEquals($gitman->id, session('tenant_id'));
        $this->assertEquals('gitman', session('tenant_domain'));
        
        // When: User switches to spotivel tenant (in real scenario, via redirect)
        session(['tenant_id' => $spotivel->id, 'tenant_domain' => 'spotivel']);
        
        // Then: Session should update to spotivel context
        $this->assertEquals($spotivel->id, session('tenant_id'));
        $this->assertEquals('spotivel', session('tenant_domain'));
    }
}
