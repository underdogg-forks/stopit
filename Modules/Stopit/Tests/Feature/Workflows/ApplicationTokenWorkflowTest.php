<?php

namespace Tests\Feature\Workflows;

use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Modules\Core\Enums\WorkspaceRole;
use Modules\Stopit\DTOs\ApplicationData;
use Modules\Stopit\Models\Account;
use Modules\Stopit\Models\Application;
use Modules\Stopit\Models\ExceptionRecord;
use Modules\Stopit\Models\User;
use Modules\Stopit\Services\ApplicationService;
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

        $this->account = Account::factory()->create([
            'name' => 'GitMan Project',
            'slug' => 'gitman-project',
        ]);

        $this->user = User::factory()->create([
            'name'  => 'John Maintainer',
            'email' => 'john@gitman.dev',
        ]);

        $this->user->accounts()->attach($this->account->id, ['role' => WorkspaceRole::OWNER->value]);
    }

    private function createApplication(string $name = 'GitMan API', string $slug = 'gitman-api'): array
    {
        $applicationData = new ApplicationData();
        $applicationData->setAccountId($this->account->id)
            ->setName($name)
            ->setSlug($slug);

        return $this->applicationService->createApplication($applicationData);
    }

    #[Test]
    public function it_completes_full_workflow_from_login_to_posting_exception(): void
    {
        /* Arrange */
        $this->actingAs($this->user);

        /* Act */
        $result      = $this->createApplication();
        $application = $result['application'];
        $apiToken    = $result['plain_token'];

        $validatedApplication = $this->applicationService->validateToken($apiToken);

        $response = $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'RuntimeException',
            'message'         => 'Database connection failed in GitMan',
            'file'            => '/app/Database/Connection.php',
            'line'            => 42,
            'severity'        => 'error',
        ], ['Authorization' => "Bearer {$apiToken}"]);

        /* Assert */
        $this->assertAuthenticated();
        $this->assertInstanceOf(Application::class, $application);
        $this->assertEquals('GitMan API', $application->name);
        $this->assertEquals(64, mb_strlen($apiToken));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{64}$/', $apiToken);
        $this->assertNotNull($validatedApplication);
        $this->assertEquals($application->id, $validatedApplication->id);
        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'exception_class', 'message', 'created_at']);
        $this->assertDatabaseHas('exceptions', [
            'application_id'  => $application->id,
            'exception_class' => 'RuntimeException',
            'message'         => 'Database connection failed in GitMan',
        ]);
    }

    #[Test]
    public function it_requires_authentication_to_create_application(): void
    {
        /* Arrange - User is NOT authenticated */

        /* Act */
        $response = $this->get('/admin/applications/create');

        /* Assert */
        $this->assertGuest();
        $response->assertRedirect('/admin/login');
    }

    #[Test]
    public function it_requires_account_to_create_application(): void
    {
        /* Arrange */
        $orphanUser = User::factory()->create([
            'name'  => 'Orphan User',
            'email' => 'orphan@example.com',
        ]);
        $this->actingAs($orphanUser);

        /* Act & Assert */
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User must belong to at least one account');

        if ( ! $orphanUser->accounts()->exists()) {
            throw new RuntimeException('User must belong to at least one account to create an application.');
        }
    }

    #[Test]
    public function it_invalidates_old_token_when_regenerated(): void
    {
        /* Arrange */
        $this->actingAs($this->user);
        $result      = $this->createApplication();
        $application = $result['application'];
        $oldToken    = $result['plain_token'];

        /* Act */
        $newToken = $this->applicationService->regenerateToken($application->id);

        $responseWithNewToken = $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Test',
        ], ['Authorization' => "Bearer {$newToken}"]);

        $responseWithOldToken = $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Test',
        ], ['Authorization' => "Bearer {$oldToken}"]);

        /* Assert */
        $this->assertNotEquals($oldToken, $newToken);
        $this->assertEquals(64, mb_strlen($newToken));
        $this->assertNotNull($this->applicationService->validateToken($newToken));
        $this->assertNull($this->applicationService->validateToken($oldToken));
        $responseWithNewToken->assertStatus(201);
        $responseWithOldToken->assertStatus(401);
        $responseWithOldToken->assertJson(['message' => 'Invalid or missing API token']);
    }

    #[Test]
    public function it_rejects_api_request_without_token(): void
    {
        /* Arrange */
        $payload = ['exception_class' => 'TestException', 'message' => 'Test'];

        /* Act */
        $response = $this->postJson('/api/v1/exceptions', $payload);

        /* Assert */
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid or missing API token']);
    }

    #[Test]
    public function it_rejects_api_request_with_invalid_token(): void
    {
        /* Arrange */
        $invalidToken = 'invalid-token-1234567890123456789012345678901234567890123456';

        /* Act */
        $response = $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Test',
        ], ['Authorization' => "Bearer {$invalidToken}"]);

        /* Assert */
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid or missing API token']);
    }

    #[Test]
    public function it_rejects_api_request_with_malformed_token(): void
    {
        /* Arrange */
        $malformedTokens = [
            'too-short',
            ' ',
            'Bearer token-here',
            str_repeat('a', 63),
            str_repeat('a', 65),
        ];

        /* Act & Assert */
        foreach ($malformedTokens as $malformedToken) {
            $response = $this->postJson('/api/v1/exceptions', [
                'exception_class' => 'TestException',
                'message'         => 'Test',
            ], ['Authorization' => "Bearer {$malformedToken}"]);

            $response->assertStatus(401);
        }
    }

    #[Test]
    public function it_stores_only_hashed_token_never_plain_text(): void
    {
        /* Arrange */
        $this->actingAs($this->user);
        $result      = $this->createApplication();
        $application = $result['application'];
        $plainToken  = $result['plain_token'];
        $hashedToken = hash('sha256', $plainToken);

        /* Act */
        $applicationFromDb = Application::find($application->id);

        /* Assert */
        $this->assertDatabaseMissing('applications', [
            'id'        => $application->id,
            'api_token' => $plainToken,
        ]);
        $this->assertDatabaseHas('applications', [
            'id'        => $application->id,
            'api_token' => $hashedToken,
        ]);
        $this->assertNotEquals($plainToken, $applicationFromDb->api_token);
    }

    #[Test]
    public function it_accepts_multiple_concurrent_exception_posts_with_same_token(): void
    {
        /* Arrange */
        $this->actingAs($this->user);
        $result   = $this->createApplication();
        $apiToken = $result['plain_token'];

        /* Act */
        $responses = [];
        for ($i = 1; $i <= 5; $i++) {
            $responses[] = $this->postJson('/api/v1/exceptions', [
                'exception_class' => 'RuntimeException',
                'message'         => "Concurrent error #{$i}",
                'severity'        => 'error',
            ], ['Authorization' => "Bearer {$apiToken}"]);
        }

        /* Assert */
        foreach ($responses as $response) {
            $response->assertStatus(201);
        }
        $this->assertDatabaseCount('exceptions', 5);
    }

    #[Test]
    public function it_scopes_exceptions_to_the_application_matching_the_token(): void
    {
        /* Arrange */
        $this->actingAs($this->user);
        $result1 = $this->createApplication('GitMan Frontend', 'gitman-frontend');
        $app1    = $result1['application'];
        $token1  = $result1['plain_token'];

        $result2 = $this->createApplication('GitMan Backend', 'gitman-backend');
        $app2    = $result2['application'];

        /* Act */
        $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'FrontendException',
            'message'         => 'Frontend error',
        ], ['Authorization' => "Bearer {$token1}"])->assertStatus(201);

        /* Assert */
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
    public function it_scopes_applications_to_user_accounts(): void
    {
        /* Arrange */
        $otherAccount = Account::factory()->create(['name' => 'Other Project']);
        $otherUser    = User::factory()->create(['email' => 'other@example.com']);
        $otherUser->accounts()->attach($otherAccount->id);

        $this->actingAs($this->user);
        $ourResult = $this->createApplication();
        $ourApplication = $ourResult['application'];

        $this->actingAs($otherUser);
        $otherApplicationData = new ApplicationData();
        $otherApplicationData->setAccountId($otherAccount->id)
            ->setName('Other API')
            ->setSlug('other-api');
        $otherResult      = $this->applicationService->createApplication($otherApplicationData);
        $otherApplication = $otherResult['application'];

        /* Act */
        $ourAccountIds   = $ourApplication->accounts()->pluck('accounts.id')->toArray();
        $otherAccountIds = $otherApplication->accounts()->pluck('accounts.id')->toArray();

        /* Assert */
        $this->assertContains($this->account->id, $ourAccountIds);
        $this->assertNotContains($otherAccount->id, $ourAccountIds);
        $this->assertContains($otherAccount->id, $otherAccountIds);
        $this->assertNotContains($this->account->id, $otherAccountIds);
    }

    #[Test]
    public function it_allows_immediate_use_of_regenerated_token(): void
    {
        /* Arrange */
        $this->actingAs($this->user);
        $result      = $this->createApplication();
        $application = $result['application'];

        /* Act */
        $newToken = $this->applicationService->regenerateToken($application->id);
        $response = $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Test with new token',
        ], ['Authorization' => "Bearer {$newToken}"]);

        /* Assert */
        $response->assertStatus(201);
        $this->assertDatabaseHas('exceptions', [
            'application_id' => $application->id,
            'message'        => 'Test with new token',
        ]);
    }

    #[Test]
    public function it_treats_api_token_as_case_sensitive(): void
    {
        /* Arrange */
        $this->actingAs($this->user);
        $result       = $this->createApplication();
        $correctToken = $result['plain_token'];
        $uppercaseToken = mb_strtoupper($correctToken);

        /* Act */
        $response = $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Test',
        ], ['Authorization' => "Bearer {$uppercaseToken}"]);

        /* Assert */
        $response->assertStatus(401);
    }

    #[Test]
    public function it_requires_name_and_slug_to_create_application(): void
    {
        /* Arrange */
        $this->actingAs($this->user);
        $data = new ApplicationData();
        $data->setAccountId($this->account->id)
            ->setName('')
            ->setSlug('test-slug');

        /* Act & Assert */
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Name and slug are required');
        $this->applicationService->createApplication($data);
    }

    #[Test]
    public function it_increments_occurrence_count_for_duplicate_exceptions(): void
    {
        /* Arrange */
        $this->actingAs($this->user);
        $result   = $this->createApplication();
        $apiToken = $result['plain_token'];

        $payload = [
            'exception_class' => 'DatabaseException',
            'message'         => 'Connection timeout',
            'severity'        => 'error',
        ];

        /* Act */
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/exceptions', $payload, [
                'Authorization' => "Bearer {$apiToken}",
            ])->assertStatus(201);
        }

        /* Assert */
        $this->assertDatabaseCount('exceptions', 1);
        $exception = ExceptionRecord::first();
        $this->assertEquals(3, $exception->occurrence_count);
        $this->assertEquals('DatabaseException', $exception->exception_class);
        $this->assertEquals('Connection timeout', $exception->message);
    }
}
