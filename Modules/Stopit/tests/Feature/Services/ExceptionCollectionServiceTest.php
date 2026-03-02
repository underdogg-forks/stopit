<?php

namespace Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Stopit\src\Providers\Models\Account;
use Stopit\src\Providers\Models\Application;
use Stopit\src\Providers\Models\ExceptionRecord;
use Stopit\src\Providers\Services\ExceptionCollectionService;
use Stopit\src\Providers\src\DTOs\ExceptionData;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('services')]
#[Group('exception-collection-service')]
class ExceptionCollectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExceptionCollectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ExceptionCollectionService::class);
    }

    #[Test]
    public function it_creates_new_exception_record(): void
    {
        /* Arrange */
        $account     = Account::factory()->create();
        $application = Application::factory()->create();
        $application->accounts()->attach($account->id);

        $data = new ExceptionData();
        $data->setExceptionClass('RuntimeException')
            ->setMessage('Test error')
            ->setSeverity('error');

        /* Act */
        $exception = $this->service->reportException($application->id, $data);

        /* Assert */
        $this->assertInstanceOf(ExceptionRecord::class, $exception);
        $this->assertEquals('RuntimeException', $exception->exception_class);
        $this->assertEquals('Test error', $exception->message);
        $this->assertEquals(1, $exception->occurrence_count);

        $this->assertDatabaseHas('exceptions', [
            'application_id'   => $application->id,
            'exception_class'  => 'RuntimeException',
            'message'          => 'Test error',
            'occurrence_count' => 1,
        ]);
    }

    #[Test]
    public function it_increments_occurrence_count_for_same_fingerprint(): void
    {
        /* Arrange */
        $account     = Account::factory()->create();
        $application = Application::factory()->create();
        $application->accounts()->attach($account->id);

        $data = new ExceptionData();
        $data->setExceptionClass('RuntimeException')
            ->setMessage('Same error')
            ->setSeverity('error');

        /* Act */
        $exception1 = $this->service->reportException($application->id, $data);
        $exception2 = $this->service->reportException($application->id, $data);

        /* Assert */
        $this->assertEquals($exception1->id, $exception2->id);
        $this->assertEquals(2, $exception2->occurrence_count);
        $this->assertDatabaseCount('exceptions', 1);
    }

    #[Test]
    public function it_creates_separate_records_for_different_messages(): void
    {
        /* Arrange */
        $account     = Account::factory()->create();
        $application = Application::factory()->create();
        $application->accounts()->attach($account->id);

        $data1 = new ExceptionData();
        $data1->setExceptionClass('RuntimeException')
            ->setMessage('Error 1')
            ->setSeverity('error');

        $data2 = new ExceptionData();
        $data2->setExceptionClass('RuntimeException')
            ->setMessage('Error 2')
            ->setSeverity('error');

        /* Act */
        $exception1 = $this->service->reportException($application->id, $data1);
        $exception2 = $this->service->reportException($application->id, $data2);

        /* Assert */
        $this->assertNotEquals($exception1->id, $exception2->id);
        $this->assertDatabaseCount('exceptions', 2);
    }

    #[Test]
    public function it_marks_exception_as_resolved(): void
    {
        /* Arrange */
        $account     = Account::factory()->create();
        $application = Application::factory()->create();
        $application->accounts()->attach($account->id);
        $exception = ExceptionRecord::factory()->create([
            'application_id' => $application->id,
            'is_resolved'    => false,
        ]);

        /* Act */
        $result = $this->service->markAsResolved($exception->id);

        /* Assert */
        $this->assertTrue($result);
        $this->assertDatabaseHas('exceptions', [
            'id'          => $exception->id,
            'is_resolved' => true,
        ]);
    }

    #[Test]
    public function it_throws_exception_when_exception_class_is_empty(): void
    {
        /* Arrange */
        $account     = Account::factory()->create();
        $application = Application::factory()->create();
        $application->accounts()->attach($account->id);

        $data = new ExceptionData();
        $data->setExceptionClass('')
            ->setMessage('Test error')
            ->setSeverity('error');

        /* Act & Assert */
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exception class is required');

        $this->service->reportException($application->id, $data);
    }

    #[Test]
    public function it_returns_grouped_exceptions(): void
    {
        /* Arrange */
        $account     = Account::factory()->create();
        $application = Application::factory()->create();
        $application->accounts()->attach($account->id);

        ExceptionRecord::factory()->count(5)->create([
            'application_id' => $application->id,
            'is_resolved'    => false,
        ]);

        ExceptionRecord::factory()->count(2)->create([
            'application_id' => $application->id,
            'is_resolved'    => true,
        ]);

        /* Act */
        $allExceptions  = $this->service->getGroupedExceptions($application->id, false);
        $unresolvedOnly = $this->service->getGroupedExceptions($application->id, true);

        /* Assert */
        $this->assertCount(7, $allExceptions);
        $this->assertCount(5, $unresolvedOnly);
    }
}
