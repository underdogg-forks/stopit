<?php

namespace Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Modules\Stopit\DTOs\ApplicationData;
use Modules\Stopit\Models\Account;
use Modules\Stopit\Models\Application;
use Modules\Stopit\Services\ApplicationService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('services')]
#[Group('application-service')]
class ApplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ApplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ApplicationService::class);
    }

    private function makeApplicationData(Account $account, string $name = 'Test App', string $slug = 'test-app'): ApplicationData
    {
        $data = new ApplicationData();
        $data->setAccountId($account->id)
            ->setName($name)
            ->setSlug($slug);

        return $data;
    }

    #[Test]
    public function it_creates_application_with_hashed_token(): void
    {
        /* Arrange */
        $account = Account::factory()->create();
        $data    = $this->makeApplicationData($account);

        /* Act */
        $result = $this->service->createApplication($data);

        /* Assert */
        $this->assertArrayHasKey('application', $result);
        $this->assertArrayHasKey('plain_token', $result);
        $this->assertInstanceOf(Application::class, $result['application']);
        $this->assertEquals(64, mb_strlen($result['plain_token']));
        $this->assertDatabaseHas('applications', ['name' => 'Test App', 'slug' => 'test-app']);
        $this->assertDatabaseHas('account_application', [
            'account_id'     => $account->id,
            'application_id' => $result['application']->id,
        ]);
    }

    #[Test]
    public function it_validates_token_correctly(): void
    {
        /* Arrange */
        $account    = Account::factory()->create();
        $result     = $this->service->createApplication($this->makeApplicationData($account));
        $plainToken = $result['plain_token'];

        /* Act */
        $application = $this->service->validateToken($plainToken);

        /* Assert */
        $this->assertInstanceOf(Application::class, $application);
        $this->assertEquals('Test App', $application->name);
    }

    #[Test]
    public function it_returns_null_for_invalid_token(): void
    {
        /* Arrange */
        $invalidToken = 'invalid-token-12345678901234567890123456789012345678901234';

        /* Act */
        $application = $this->service->validateToken($invalidToken);

        /* Assert */
        $this->assertNull($application);
    }

    #[Test]
    public function it_returns_null_for_empty_token(): void
    {
        /* Arrange */
        $emptyToken = '';

        /* Act */
        $application = $this->service->validateToken($emptyToken);

        /* Assert */
        $this->assertNull($application);
    }

    #[Test]
    public function it_regenerates_token_and_invalidates_old_token(): void
    {
        /* Arrange */
        $account     = Account::factory()->create();
        $result      = $this->service->createApplication($this->makeApplicationData($account));
        $oldToken    = $result['plain_token'];
        $application = $result['application'];

        /* Act */
        $newToken = $this->service->regenerateToken($application->id);

        /* Assert */
        $this->assertEquals(64, mb_strlen($newToken));
        $this->assertNotEquals($oldToken, $newToken);
        $this->assertNull($this->service->validateToken($oldToken));
        $this->assertInstanceOf(Application::class, $this->service->validateToken($newToken));
    }

    #[Test]
    public function it_throws_exception_when_name_or_slug_is_empty(): void
    {
        /* Arrange */
        $account = Account::factory()->create();
        $data    = new ApplicationData();
        $data->setAccountId($account->id)
            ->setName('')
            ->setSlug('test-slug');

        /* Act & Assert */
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Name and slug are required');
        $this->service->createApplication($data);
    }
}