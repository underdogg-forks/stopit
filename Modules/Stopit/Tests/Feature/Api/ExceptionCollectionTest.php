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

    private string $plainToken  = 'test-token-12345678901234567890123456789012345678901234567890';
    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $account           = Account::factory()->create();
        $this->application = Application::factory()->create([
            'api_token' => hash('sha256', $this->plainToken),
        ]);
        $this->application->accounts()->attach($account->id);
    }

    #[Test]
    public function it_accepts_valid_exception_with_bearer_token(): void
    {
        /* Arrange */
        $payload = [
            'exception_class' => 'RuntimeException',
            'message'         => 'Database connection failed',
            'file'            => '/app/Database.php',
            'line'            => 42,
            'severity'        => Severity::ERROR->value,
        ];

        /* Act */
        $response = $this->postJson('/api/v1/exceptions', $payload, [
            'Authorization' => "Bearer {$this->plainToken}",
        ]);

        /* Assert */
        $response->assertStatus(201);
        $response->assertJsonStructure(['id', 'exception_class', 'message', 'created_at']);
        $this->assertDatabaseHas('exceptions', [
            'application_id'  => $this->application->id,
            'exception_class' => 'RuntimeException',
            'message'         => 'Database connection failed',
        ]);
    }

    #[Test]
    public function it_returns_401_when_no_bearer_token_provided(): void
    {
        /* Arrange */
        $payload = ['exception_class' => 'RuntimeException', 'message' => 'Test error'];

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
        $payload = ['exception_class' => 'RuntimeException', 'message' => 'Test error'];

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
        $payload = ['message' => 'Test error'];

        /* Act */
        $response = $this->postJson('/api/v1/exceptions', $payload, [
            'Authorization' => "Bearer {$this->plainToken}",
        ]);

        /* Assert */
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['exception_class']);
    }

    #[Test]
    public function it_increments_occurrence_count_for_duplicate_exceptions(): void
    {
        /* Arrange */
        $payload = [
            'exception_class' => 'RuntimeException',
            'message'         => 'Same error',
            'severity'        => Severity::ERROR->value,
        ];

        /* Act */
        $this->postJson('/api/v1/exceptions', $payload, [
            'Authorization' => "Bearer {$this->plainToken}",
        ]);
        $this->postJson('/api/v1/exceptions', $payload, [
            'Authorization' => "Bearer {$this->plainToken}",
        ]);

        /* Assert */
        $this->assertDatabaseCount('exceptions', 1);
        $this->assertEquals(2, ExceptionRecord::first()->occurrence_count);
    }
}
