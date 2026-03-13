<?php

namespace Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Modules\Core\Enums\Severity;
use Modules\Stopit\DTOs\ExceptionData;
use Modules\Stopit\Models\Account;
use Modules\Stopit\Models\Application;
use Modules\Stopit\Models\ExceptionRecord;
use Modules\Stopit\Services\ExceptionCollectionService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('services')]
#[Group('exception-collection-service')]
class ExceptionCollectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExceptionCollectionService $service;

    private Application $application;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service     = app(ExceptionCollectionService::class);
        $account           = Account::factory()->create();
        $this->application = Application::factory()->create();
        $this->application->accounts()->attach($account->id);
    }

    private function makeExceptionData(
        string $class = 'RuntimeException',
        string $message = 'Test error',
        Severity $severity = Severity::ERROR
    ): ExceptionData {
        $data = new ExceptionData();
        $data->setExceptionClass($class)
            ->setMessage($message)
            ->setSeverity($severity->value);

        return $data;
    }

    #[Test]
    public function it_creates_new_exception_record(): void
    {
        /* Arrange */
        $data = $this->makeExceptionData();

        /* Act */
        $exception = $this->service->reportException($this->application->id, $data);

        /* Assert */
        $this->assertInstanceOf(ExceptionRecord::class, $exception);
        $this->assertEquals('RuntimeException', $exception->exception_class);
        $this->assertEquals('Test error', $exception->message);
        $this->assertEquals(1, $exception->occurrence_count);
        $this->assertDatabaseHas('exceptions', [
            'application_id'   => $this->application->id,
            'exception_class'  => 'RuntimeException',
            'message'          => 'Test error',
            'occurrence_count' => 1,
        ]);
    }

    #[Test]
    public function it_increments_occurrence_count_for_same_fingerprint(): void
    {
        /* Arrange */
        $data = $this->makeExceptionData(message: 'Same error');

        /* Act */
        $exception1 = $this->service->reportException($this->application->id, $data);
        $exception2 = $this->service->reportException($this->application->id, $data);

        /* Assert */
        $this->assertEquals($exception1->id, $exception2->id);
        $this->assertEquals(2, $exception2->occurrence_count);
        $this->assertDatabaseCount('exceptions', 1);
    }

    #[Test]
    public function it_creates_separate_records_for_different_messages(): void
    {
        /* Arrange */
        $data1 = $this->makeExceptionData(message: 'Error 1');
        $data2 = $this->makeExceptionData(message: 'Error 2');

        /* Act */
        $exception1 = $this->service->reportException($this->application->id, $data1);
        $exception2 = $this->service->reportException($this->application->id, $data2);

        /* Assert */
        $this->assertNotEquals($exception1->id, $exception2->id);
        $this->assertDatabaseCount('exceptions', 2);
    }

    #[Test]
    public function it_creates_separate_records_for_different_exception_classes(): void
    {
        /* Arrange */
        $data1 = $this->makeExceptionData('RuntimeException', 'Same message');
        $data2 = $this->makeExceptionData('InvalidArgumentException', 'Same message');

        /* Act */
        $exception1 = $this->service->reportException($this->application->id, $data1);
        $exception2 = $this->service->reportException($this->application->id, $data2);

        /* Assert */
        $this->assertNotEquals($exception1->id, $exception2->id);
        $this->assertDatabaseCount('exceptions', 2);
    }

    #[Test]
    public function it_marks_exception_as_resolved(): void
    {
        /* Arrange */
        $exception = ExceptionRecord::factory()->create([
            'application_id' => $this->application->id,
            'is_resolved'    => false,
        ]);

        /* Act */
        $result = $this->service->markAsResolved($exception->id);

        /* Assert */
        $this->assertTrue($result);
        $this->assertDatabaseHas('exceptions', ['id' => $exception->id, 'is_resolved' => true]);
    }

    #[Test]
    public function it_throws_exception_when_exception_class_is_empty(): void
    {
        /* Arrange */
        $data = $this->makeExceptionData(class: '');

        /* Act & Assert */
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Exception class is required');
        $this->service->reportException($this->application->id, $data);
    }

    #[Test]
    public function it_returns_grouped_exceptions_including_resolved_and_unresolved(): void
    {
        /* Arrange */
        // Create some with duplicate fingerprints to verify grouping
        ExceptionRecord::factory()->count(3)->create([
            'application_id' => $this->application->id,
            'is_resolved'    => false,
            'fingerprint'    => 'unique-fingerprint-1',
        ]);
        ExceptionRecord::factory()->count(2)->create([
            'application_id' => $this->application->id,
            'is_resolved'    => false,
            'fingerprint'    => 'unique-fingerprint-2',
        ]);
        ExceptionRecord::factory()->count(2)->create([
            'application_id' => $this->application->id,
            'is_resolved'    => true,
            'fingerprint'    => 'unique-fingerprint-3',
        ]);

        /* Act */
        $allExceptions  = $this->service->getGroupedExceptions($this->application->id, false);
        $unresolvedOnly = $this->service->getGroupedExceptions($this->application->id, true);

        /* Assert */
        $this->assertCount(7, $allExceptions);
        $this->assertCount(5, $unresolvedOnly);
    }

    #[Test]
    public function it_returns_grouped_exceptions_as_collection_of_exception_records(): void
    {
        /* Arrange */
        // Create with duplicate fingerprints to test collision handling
        ExceptionRecord::factory()->create([
            'application_id' => $this->application->id,
            'is_resolved'    => false,
            'fingerprint'    => 'duplicate-fp',
        ]);
        ExceptionRecord::factory()->create([
            'application_id' => $this->application->id,
            'is_resolved'    => false,
            'fingerprint'    => 'duplicate-fp',
        ]);
        ExceptionRecord::factory()->create([
            'application_id' => $this->application->id,
            'is_resolved'    => false,
            'fingerprint'    => 'unique-fp',
        ]);

        /* Act */
        $grouped = $this->service->getGroupedExceptions($this->application->id, false);

        /* Assert */
        $this->assertCount(3, $grouped);
        $this->assertInstanceOf(ExceptionRecord::class, $grouped->first());
        $this->assertTrue($grouped->every(fn ($record) => $record->application_id === $this->application->id));
    }

    #[Test]
    public function it_preserves_severity_as_enum_on_created_record(): void
    {
        /* Arrange */
        $data = $this->makeExceptionData(severity: Severity::CRITICAL);

        /* Act */
        $exception = $this->service->reportException($this->application->id, $data);

        /* Assert */
        $this->assertEquals(Severity::CRITICAL, $exception->severity);
    }
}