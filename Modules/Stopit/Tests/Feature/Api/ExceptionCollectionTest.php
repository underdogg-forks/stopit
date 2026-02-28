<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Enums\Severity;
use Modules\Stopit\Models\Account;
use Modules\Stopit\Models\Application;
use Modules\Stopit\Models\ExceptionRecord;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('api')]
#[Group('exception-collection')]
class ExceptionCollectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_accepts_valid_exception_with_bearer_token(): void
    {
        /* Arrange */
        $account = Account::factory()->create();
        $plainToken = 'test-token-12345678901234567890123456789012345678901234567890';
        $hashedToken = hash('sha256', $plainToken);
        $application = Application::factory()->create([
            'account_id' => $account->id,
            'api_token' => $hashedToken,
        ]);

        $payload = [
            'exception_class' => 'RuntimeException',
            'message' => 'Database connection failed',
            'file' => '/app/Database.php',
            'line' => 42,
            'severity' => 'error',
        ];

        /* Act */
        $response = $this->postJson('/api/v1/exceptions', $payload, [
            'Authorization' => "Bearer {$plainToken}",
        ]);

        /* Assert */
        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'exception_class', 'message', 'created_at']);
        
        $this->assertDatabaseHas('exceptions', [
            'application_id' => $application->id,
            'exception_class' => 'RuntimeException',
            'message' => 'Database connection failed',
        ]);
    }

    #[Test]
    public function it_returns_401_when_no_bearer_token_provided(): void
    {
        /* Arrange */
        $payload = [
            'exception_class' => 'RuntimeException',
            'message' => 'Test error',
        ];

        /* Act */
        $response = $this->postJson('/api/v1/exceptions', $payload);

        /* Assert */
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid or missing API token']);
    }

    #[Test]
    public function it_returns_401_when_invalid_bearer_token_provided(): void
    {
        /* Arrange */
        $payload = [
            'exception_class' => 'RuntimeException',
            'message' => 'Test error',
        ];

        /* Act */
        $response = $this->postJson('/api/v1/exceptions', $payload, [
            'Authorization' => 'Bearer invalid-token',
        ]);

        /* Assert */
        $response->assertStatus(401);
        $response->assertJson(['message' => 'Invalid or missing API token']);
    }

    #[Test]
    public function it_returns_422_when_exception_class_is_missing(): void
    {
        /* Arrange */
        $account = Account::factory()->create();
        $plainToken = 'test-token-12345678901234567890123456789012345678901234567890';
        $hashedToken = hash('sha256', $plainToken);
        Application::factory()->create([
            'account_id' => $account->id,
            'api_token' => $hashedToken,
        ]);

        $payload = [
            'message' => 'Test error',
        ];

        /* Act */
        $response = $this->postJson('/api/v1/exceptions', $payload, [
            'Authorization' => "Bearer {$plainToken}",
        ]);

        /* Assert */
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['exception_class']);
    }

    #[Test]
    public function it_increments_occurrence_count_for_duplicate_exceptions(): void
    {
        /* Arrange */
        $account = Account::factory()->create();
        $plainToken = 'test-token-12345678901234567890123456789012345678901234567890';
        $hashedToken = hash('sha256', $plainToken);
        $application = Application::factory()->create([
            'account_id' => $account->id,
            'api_token' => $hashedToken,
        ]);

        $payload = [
            'exception_class' => 'RuntimeException',
            'message' => 'Same error',
            'severity' => 'error',
        ];

        /* Act */
        $this->postJson('/api/v1/exceptions', $payload, [
            'Authorization' => "Bearer {$plainToken}",
        ]);
        
        $this->postJson('/api/v1/exceptions', $payload, [
            'Authorization' => "Bearer {$plainToken}",
        ]);

        /* Assert */
        $this->assertDatabaseCount('exceptions', 1);
        
        $exception = ExceptionRecord::first();
        $this->assertEquals(2, $exception->occurrence_count);
    }
}
