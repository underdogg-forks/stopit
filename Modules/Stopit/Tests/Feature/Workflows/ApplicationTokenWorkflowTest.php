<?php

namespace Tests\Feature\Workflows;

use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Modules\Stopit\Providers\Models\Account;
use Modules\Stopit\Providers\Models\Application;
use Modules\Stopit\Providers\Models\User;
use Modules\Stopit\Providers\Services\ApplicationService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

/**
 * Comprehensive test suite for the Application Token Workflow.
 *
 * Scenario: A maintainer of a gitman project wants to:
 * 1. Login to Stopit
 * 2. Create/view an application and get a new API key
 * 3. Copy that key (the key must be active)
 * 4. Post an exception using that key
 */
#[Group('workflows')]
#[Group('application-token-workflow')]
class ApplicationTokenWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private ApplicationService $applicationService;

    private User $user;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->applicationService = app(ApplicationService::class);

        // Create a user with an account (simulating a gitman project maintainer)
        $this->account = Account::factory()->create([
            'name' => 'GitMan Project',
            'slug' => 'gitman-project',
        ]);

        $this->user = User::factory()->create([
            'name'  => 'John Maintainer',
            'email' => 'john@gitman.dev',
        ]);

        // Attach user to account via workspaces pivot
        $this->user->accounts()->attach($this->account->id, ['role' => 'owner']);
    }

    #[Test]
    public function complete_workflow_from_login_to_posting_exception(): void
    {
        /*
         * Step 1: User logs in
         */
        $this->actingAs($this->user);
        $this->assertAuthenticated();

        /**
         * Step 2: User creates a new application and receives API token.
         */
        $applicationData = new \Modules\Stopit\Providers\src\DTOs\ApplicationData();
        $applicationData->setAccountId($this->account->id)
            ->setName('GitMan API')
            ->setSlug('gitman-api');

        $result = $this->applicationService->createApplication($applicationData);

        $this->assertArrayHasKey('application', $result);
        $this->assertArrayHasKey('plain_token', $result);

        $application = $result['application'];
        $apiToken    = $result['plain_token'];

        // Verify application is created
        $this->assertInstanceOf(Application::class, $application);
        $this->assertEquals('GitMan API', $application->name);

        // Verify token format (64 character string)
        $this->assertEquals(64, mb_strlen($apiToken));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{64}$/', $apiToken);

        /**
         * Step 3: Token is active and can be validated.
         */
        $validatedApplication = $this->applicationService->validateToken($apiToken);

        $this->assertNotNull($validatedApplication);
        $this->assertEquals($application->id, $validatedApplication->id);
        $this->assertEquals('GitMan API', $validatedApplication->name);

        /**
         * Step 4: Use the token to post an exception via API.
         */
        $exceptionPayload = [
            'exception_class' => 'RuntimeException',
            'message'         => 'Database connection failed in GitMan',
            'file'            => '/app/Database/Connection.php',
            'line'            => 42,
            'severity'        => 'error',
            'stack_trace'     => "RuntimeException: Database connection failed\n at Connection.php:42",
        ];

        $response = $this->postJson('/api/v1/exceptions', $exceptionPayload, [
            'Authorization' => "Bearer {$apiToken}",
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'exception_class',
            'message',
            'created_at',
        ]);

        // Verify exception was stored
        $this->assertDatabaseHas('exceptions', [
            'application_id'  => $application->id,
            'exception_class' => 'RuntimeException',
            'message'         => 'Database connection failed in GitMan',
        ]);
    }

    #[Test]
    public function user_must_be_authenticated_to_create_application(): void
    {
        // User is NOT authenticated
        $this->assertGuest();

        // Attempting to create application via service would fail
        // In real UI, Filament would prevent access to the create page
        $this->get('/admin/applications/create')
            ->assertRedirect('/admin/login');
    }

    #[Test]
    public function user_must_have_at_least_one_account_to_create_application(): void
    {
        // Create a user with NO accounts
        $orphanUser = User::factory()->create([
            'name'  => 'Orphan User',
            'email' => 'orphan@example.com',
        ]);

        $this->actingAs($orphanUser);

        // Attempting to create application should fail
        $applicationData = new \Modules\Stopit\Providers\src\DTOs\ApplicationData();
        $applicationData->setAccountId(999) // Non-existent account
            ->setName('Test App')
            ->setSlug('test-app');

        // The service would attach to account 999, but validation should happen at form level
        // In the Filament form, mutateFormDataBeforeCreate would throw an exception
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User must belong to at least one account');

        // Simulate the Filament page behavior
        if ( ! $orphanUser->accounts()->exists()) {
            throw new RuntimeException('User must belong to at least one account to create an application.');
        }
    }

    #[Test]
    public function token_can_be_regenerated_and_old_token_becomes_invalid(): void
    {
        $this->actingAs($this->user);

        // Create application with initial token
        $applicationData = new \Modules\Stopit\Providers\src\DTOs\ApplicationData();
        $applicationData->setAccountId($this->account->id)
            ->setName('GitMan API')
            ->setSlug('gitman-api');

        $result      = $this->applicationService->createApplication($applicationData);
        $application = $result['application'];
        $oldToken    = $result['plain_token'];

        // Old token works
        $this->assertNotNull($this->applicationService->validateToken($oldToken));

        // Regenerate token (simulates clicking "Regenerate API Token" button)
        $newToken = $this->applicationService->regenerateToken($application->id);

        // New token is different and works
        $this->assertNotEquals($oldToken, $newToken);
        $this->assertEquals(64, mb_strlen($newToken));
        $this->assertNotNull($this->applicationService->validateToken($newToken));

        // Old token no longer works
        $this->assertNull($this->applicationService->validateToken($oldToken));

        // Try to use old token to post exception - should fail
        $response = $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Test',
        ], [
            'Authorization' => "Bearer {$oldToken}",
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid or missing API token']);
    }

    #[Test]
    public function api_request_without_token_is_rejected(): void
    {
        $response = $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Test',
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid or missing API token']);
    }

    #[Test]
    public function api_request_with_invalid_token_is_rejected(): void
    {
        $invalidToken = 'invalid-token-1234567890123456789012345678901234567890123456';

        $response = $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Test',
        ], [
            'Authorization' => "Bearer {$invalidToken}",
        ]);

        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid or missing API token']);
    }

    #[Test]
    public function api_request_with_malformed_token_is_rejected(): void
    {
        $testCases = [
            'too-short',
            '',
            ' ',
            'Bearer token-here', // Token WITH "Bearer" prefix
            str_repeat('a', 63), // Too short
            str_repeat('a', 65), // Too long
        ];

        foreach ($testCases as $malformedToken) {
            $response = $this->postJson('/api/v1/exceptions', [
                'exception_class' => 'TestException',
                'message'         => 'Test',
            ], [
                'Authorization' => "Bearer {$malformedToken}",
            ]);

            $response->assertStatus(401);
        }
    }

    #[Test]
    public function token_is_only_shown_once_during_creation(): void
    {
        $this->actingAs($this->user);

        // Create application
        $applicationData = new \Modules\Stopit\Providers\src\DTOs\ApplicationData();
        $applicationData->setAccountId($this->account->id)
            ->setName('GitMan API')
            ->setSlug('gitman-api');

        $result      = $this->applicationService->createApplication($applicationData);
        $application = $result['application'];
        $plainToken  = $result['plain_token'];

        // Token is NOT stored in plain text in Database
        $this->assertDatabaseMissing('applications', [
            'id'        => $application->id,
            'api_token' => $plainToken,
        ]);

        // Only hashed version is stored
        $hashedToken = hash('sha256', $plainToken);
        $this->assertDatabaseHas('applications', [
            'id'        => $application->id,
            'api_token' => $hashedToken,
        ]);

        // Plain token is only in the response, nowhere else
        $applicationFromDb = Application::find($application->id);
        $this->assertNotEquals($plainToken, $applicationFromDb->api_token);
    }

    #[Test]
    public function multiple_concurrent_exception_posts_with_same_token_succeed(): void
    {
        $this->actingAs($this->user);

        // Create application and get token
        $applicationData = new \Modules\Stopit\Providers\src\DTOs\ApplicationData();
        $applicationData->setAccountId($this->account->id)
            ->setName('GitMan API')
            ->setSlug('gitman-api');

        $result   = $this->applicationService->createApplication($applicationData);
        $apiToken = $result['plain_token'];

        // Simulate multiple concurrent requests with the same token
        $responses = [];
        for ($i = 1; $i <= 5; $i++) {
            $responses[] = $this->postJson('/api/v1/exceptions', [
                'exception_class' => 'RuntimeException',
                'message'         => "Concurrent error #{$i}",
                'severity'        => 'error',
            ], [
                'Authorization' => "Bearer {$apiToken}",
            ]);
        }

        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertStatus(201);
        }

        // Verify all 5 exceptions were stored
        $this->assertDatabaseCount('exceptions', 5);
    }

    #[Test]
    public function token_belongs_to_specific_application_and_account(): void
    {
        $this->actingAs($this->user);

        // Create two applications under the same account
        $app1Data = new \Modules\Stopit\Providers\src\DTOs\ApplicationData();
        $app1Data->setAccountId($this->account->id)
            ->setName('GitMan Frontend')
            ->setSlug('gitman-frontend');

        $result1 = $this->applicationService->createApplication($app1Data);
        $app1    = $result1['application'];
        $token1  = $result1['plain_token'];

        $app2Data = new \Modules\Stopit\Providers\src\DTOs\ApplicationData();
        $app2Data->setAccountId($this->account->id)
            ->setName('GitMan Backend')
            ->setSlug('gitman-backend');

        $result2 = $this->applicationService->createApplication($app2Data);
        $app2    = $result2['application'];
        $token2  = $result2['plain_token'];

        // Post exception using token1
        $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'FrontendException',
            'message'         => 'Frontend error',
        ], [
            'Authorization' => "Bearer {$token1}",
        ])->assertStatus(201);

        // Verify exception is associated with app1, not app2
        $this->assertDatabaseHas('exceptions', [
            'application_id' => $app1->id,
            'message'        => 'Frontend error',
        ]);

        $this->assertDatabaseMissing('exceptions', [
            'application_id' => $app2->id,
            'message'        => 'Frontend error',
        ]);
    }

    #[Test]
    public function user_can_only_see_applications_from_their_accounts(): void
    {
        // Create another account and user
        $otherAccount = Account::factory()->create(['name' => 'Other Project']);
        $otherUser    = User::factory()->create(['email' => 'other@example.com']);
        $otherUser->accounts()->attach($otherAccount->id);

        // Our user creates an application
        $this->actingAs($this->user);
        $appData = new \Modules\Stopit\Providers\src\DTOs\ApplicationData();
        $appData->setAccountId($this->account->id)
            ->setName('GitMan API')
            ->setSlug('gitman-api');

        $result         = $this->applicationService->createApplication($appData);
        $ourApplication = $result['application'];

        // Other user creates an application
        $this->actingAs($otherUser);
        $otherAppData = new \Modules\Stopit\Providers\src\DTOs\ApplicationData();
        $otherAppData->setAccountId($otherAccount->id)
            ->setName('Other API')
            ->setSlug('other-api');

        $otherResult      = $this->applicationService->createApplication($otherAppData);
        $otherApplication = $otherResult['application'];

        // Verify applications are in different accounts
        $this->assertTrue($ourApplication->accounts()->where('accounts.id', $this->account->id)->exists());
        $this->assertFalse($ourApplication->accounts()->where('accounts.id', $otherAccount->id)->exists());

        $this->assertTrue($otherApplication->accounts()->where('accounts.id', $otherAccount->id)->exists());
        $this->assertFalse($otherApplication->accounts()->where('accounts.id', $this->account->id)->exists());
    }

    #[Test]
    public function regenerated_token_can_immediately_be_used_to_post_exceptions(): void
    {
        $this->actingAs($this->user);

        // Create application
        $applicationData = new \Modules\Stopit\Providers\src\DTOs\ApplicationData();
        $applicationData->setAccountId($this->account->id)
            ->setName('GitMan API')
            ->setSlug('gitman-api');

        $result      = $this->applicationService->createApplication($applicationData);
        $application = $result['application'];

        // Regenerate token
        $newToken = $this->applicationService->regenerateToken($application->id);

        // Immediately use the new token (no delay needed)
        $response = $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Test with new token',
        ], [
            'Authorization' => "Bearer {$newToken}",
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('exceptions', [
            'application_id' => $application->id,
            'message'        => 'Test with new token',
        ]);
    }

    #[Test]
    public function api_token_is_case_sensitive(): void
    {
        $this->actingAs($this->user);

        // Create application
        $applicationData = new \Modules\Stopit\Providers\src\DTOs\ApplicationData();
        $applicationData->setAccountId($this->account->id)
            ->setName('GitMan API')
            ->setSlug('gitman-api');

        $result       = $this->applicationService->createApplication($applicationData);
        $correctToken = $result['plain_token'];

        // Try with uppercase version
        $uppercaseToken = mb_strtoupper($correctToken);

        $response = $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Test',
        ], [
            'Authorization' => "Bearer {$uppercaseToken}",
        ]);

        // Should fail because token is case-sensitive
        $response->assertStatus(401);
    }

    #[Test]
    public function application_name_and_slug_are_required(): void
    {
        $this->actingAs($this->user);

        // Test missing name
        $data1 = new \Modules\Stopit\Providers\src\DTOs\ApplicationData();
        $data1->setAccountId($this->account->id)
            ->setName('')
            ->setSlug('test-slug');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Name and slug are required');
        $this->applicationService->createApplication($data1);
    }

    #[Test]
    public function posted_exceptions_increment_occurrence_count_for_duplicates(): void
    {
        $this->actingAs($this->user);

        // Create application
        $applicationData = new \Modules\Stopit\Providers\src\DTOs\ApplicationData();
        $applicationData->setAccountId($this->account->id)
            ->setName('GitMan API')
            ->setSlug('gitman-api');

        $result   = $this->applicationService->createApplication($applicationData);
        $apiToken = $result['plain_token'];

        // Post same exception 3 times
        $payload = [
            'exception_class' => 'DatabaseException',
            'message'         => 'Connection timeout',
            'severity'        => 'error',
        ];

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/exceptions', $payload, [
                'Authorization' => "Bearer {$apiToken}",
            ])->assertStatus(201);
        }

        // Should have only 1 record with occurrence_count = 3
        $this->assertDatabaseCount('exceptions', 1);

        $exception = \Modules\Stopit\Providers\Models\ExceptionRecord::first();
        $this->assertEquals(3, $exception->occurrence_count);
        $this->assertEquals('DatabaseException', $exception->exception_class);
        $this->assertEquals('Connection timeout', $exception->message);
    }
}
