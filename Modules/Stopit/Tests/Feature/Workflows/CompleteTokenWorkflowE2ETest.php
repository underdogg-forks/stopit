<?php

namespace Tests\Feature\Workflows;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
 * End-to-End Feature Test for Complete Token Workflow.
 *
 * Tests the complete user journey from login to posting exceptions:
 * 1. GitMan maintainer logs in
 * 2. Creates an application
 * 3. Views and copies the Bearer token
 * 4. Uses the token to post exceptions to the API
 */
#[Group('workflows')]
#[Group('e2e')]
#[Group('token-workflow-ui')]
class CompleteTokenWorkflowE2ETest extends TestCase
{
    use RefreshDatabase;

    private User $maintainer;

    private Account $gitmanAccount;

    private ApplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ApplicationService::class);

        $this->gitmanAccount = Account::factory()->create([
            'name' => 'GitMan Project',
            'slug' => 'gitman-project',
        ]);

        $this->maintainer = User::factory()->create([
            'name'  => 'GitMan Maintainer',
            'email' => 'maintainer@gitman.dev',
        ]);

        $this->maintainer->accounts()->attach($this->gitmanAccount->id, [
            'role' => WorkspaceRole::OWNER->value,
        ]);
    }

    private function createApplication(string $name, string $slug): array
    {
        $createData = new ApplicationData();
        $createData->setAccountId($this->gitmanAccount->id)
            ->setName($name)
            ->setSlug($slug);

        return $this->service->createApplication($createData);
    }

    #[Test]
    public function it_completes_full_login_create_token_and_post_exception_workflow(): void
    {
        /* Arrange */
        $this->actingAs($this->maintainer);

        /* Act */
        $result      = $this->createApplication('GitMan Production', 'gitman-production');
        $application = $result['application'];
        $bearerToken = $result['plain_token'];

        $hashedToken  = hash('sha256', $bearerToken);
        $validatedApp = $this->service->validateToken($bearerToken);

        $response = $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'RuntimeException',
            'message'         => 'Database connection failed in GitMan production',
            'file'            => '/var/www/gitman/src/Database/Connection.php',
            'line'            => 127,
            'severity'        => 'error',
        ], [
            'Authorization' => "Bearer {$bearerToken}",
            'Accept'        => 'application/json',
        ]);

        /* Assert */
        $this->assertAuthenticated();
        $this->assertInstanceOf(Application::class, $application);
        $this->assertDatabaseHas('applications', ['name' => 'GitMan Production', 'slug' => 'gitman-production']);
        $this->assertDatabaseHas('account_application', ['account_id' => $this->gitmanAccount->id, 'application_id' => $application->id]);
        $this->assertNotNull($bearerToken);
        $this->assertEquals(64, mb_strlen($bearerToken));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]{64}$/', $bearerToken);
        $this->assertDatabaseHas('applications', ['id' => $application->id, 'api_token' => $hashedToken]);
        $this->assertNotNull($validatedApp);
        $this->assertEquals($application->id, $validatedApp->id);
        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'exception_class', 'message', 'created_at']);
        $this->assertEquals('RuntimeException', $response->json('exception_class'));
        $this->assertDatabaseHas('exceptions', [
            'application_id'  => $application->id,
            'exception_class' => 'RuntimeException',
            'message'         => 'Database connection failed in GitMan production',
        ]);
        $exception = ExceptionRecord::where('application_id', $application->id)->first();
        $this->assertNotNull($exception);
        $this->assertEquals(1, $exception->occurrence_count);
    }

    #[Test]
    public function it_allows_maintainer_to_regenerate_token_and_invalidates_old_one(): void
    {
        /* Arrange */
        $this->actingAs($this->maintainer);
        $result      = $this->createApplication('GitMan Staging', 'gitman-staging');
        $application = $result['application'];
        $oldToken    = $result['plain_token'];

        /* Act */
        $newToken = $this->service->regenerateToken($application->id);

        $responseWithOldToken = $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Test with old token',
        ], ['Authorization' => "Bearer {$oldToken}"]);

        $responseWithNewToken = $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Test with new token',
            'severity'        => 'info',
        ], ['Authorization' => "Bearer {$newToken}"]);

        /* Assert */
        $this->assertNotEquals($oldToken, $newToken);
        $this->assertEquals(64, mb_strlen($newToken));
        $this->assertNotNull($this->service->validateToken($newToken));
        $this->assertNull($this->service->validateToken($oldToken));
        $responseWithOldToken->assertStatus(401);
        $responseWithOldToken->assertJson(['message' => 'Invalid or missing API token']);
        $responseWithNewToken->assertStatus(201);
    }

    #[Test]
    public function it_verifies_token_can_be_used_as_displayed_in_ui(): void
    {
        /* Arrange */
        $this->actingAs($this->maintainer);
        $result = $this->createApplication('GitMan Dev', 'gitman-dev');
        $token  = $result['plain_token'];

        /* Act */
        $response = $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'RuntimeException',
            'message'         => 'An error occurred',
            'file'            => '/path/to/file.php',
            'line'            => 42,
            'severity'        => 'error',
        ], ['Authorization' => "Bearer {$token}"]);

        /* Assert */
        $response->assertStatus(201);
    }

    #[Test]
    public function it_allows_posting_multiple_distinct_exceptions_with_same_token(): void
    {
        /* Arrange */
        $this->actingAs($this->maintainer);
        $result = $this->createApplication('GitMan API', 'gitman-api');
        $token  = $result['plain_token'];

        /* Act */
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/api/v1/exceptions', [
                'exception_class' => 'TestException',
                'message'         => "Test exception #{$i}",
                'severity'        => 'warning',
            ], ['Authorization' => "Bearer {$token}"]);

            $response->assertStatus(201);
        }

        /* Assert */
        $this->assertDatabaseCount('exceptions', 5);
    }

    #[Test]
    public function it_increments_occurrence_count_for_duplicate_exceptions(): void
    {
        /* Arrange */
        $this->actingAs($this->maintainer);
        $result  = $this->createApplication('GitMan API', 'gitman-api');
        $token   = $result['plain_token'];
        $payload = [
            'exception_class' => 'ConnectionException',
            'message'         => 'Connection timeout',
            'severity'        => 'error',
        ];

        /* Act */
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/exceptions', $payload, [
                'Authorization' => "Bearer {$token}",
            ])->assertStatus(201);
        }

        /* Assert */
        $this->assertDatabaseCount('exceptions', 1);
        $exception = ExceptionRecord::first();
        $this->assertEquals(3, $exception->occurrence_count);
    }

    #[Test]
    public function it_scopes_exceptions_to_the_application_matching_the_token(): void
    {
        /* Arrange */
        $this->actingAs($this->maintainer);

        $result1 = $this->createApplication('GitMan Frontend', 'gitman-frontend');
        $app1    = $result1['application'];
        $token1  = $result1['plain_token'];

        $result2 = $this->createApplication('GitMan Backend', 'gitman-backend');
        $app2    = $result2['application'];

        /* Act */
        $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'FrontendError',
            'message'         => 'React component failed',
        ], ['Authorization' => "Bearer {$token1}"])->assertStatus(201);

        /* Assert */
        $this->assertDatabaseHas('exceptions', [
            'application_id' => $app1->id,
            'message'        => 'React component failed',
        ]);
        $this->assertDatabaseMissing('exceptions', [
            'application_id' => $app2->id,
            'message'        => 'React component failed',
        ]);
    }

    #[Test]
    public function it_redirects_unauthenticated_user_away_from_create_application(): void
    {
        /* Arrange - User is not logged in */

        /* Act */
        $response = $this->get('/admin/applications/create');

        /* Assert */
        $this->assertGuest();
        $response->assertRedirect('/admin/login');
    }

    #[Test]
    public function it_prevents_user_without_account_from_creating_application(): void
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
    public function it_rejects_api_requests_without_bearer_token(): void
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
    public function it_rejects_api_requests_with_invalid_bearer_token(): void
    {
        /* Arrange */
        $invalidToken = str_repeat('a', 64);

        /* Act */
        $response = $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Test',
        ], ['Authorization' => "Bearer {$invalidToken}"]);

        /* Assert */
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid or missing API token']);
    }
}