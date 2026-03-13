<?php

namespace Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Enums\Severity;
use Modules\Stopit\Models\Account;
use Modules\Stopit\Models\Application;
use Modules\Stopit\Models\ExceptionRecord;
use Modules\Stopit\Services\DashboardService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('services')]
#[Group('dashboard-service')]
class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DashboardService::class);
    }

    private function createApplicationWithAccount(?Account $account = null): array
    {
        $account     = $account ?? Account::factory()->create();
        $application = Application::factory()->create();
        $application->accounts()->attach($account->id);

        return ['application' => $application, 'account' => $account];
    }

    // -------------------------------------------------------------------------
    // Application-scoped tests
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_recent_exceptions_limited_to_10(): void
    {
        /* Arrange */
        ['application' => $application] = $this->createApplicationWithAccount();
        ExceptionRecord::factory()->count(15)->create(['application_id' => $application->id]);

        /* Act */
        $recent = $this->service->getRecentExceptions($application->id);

        /* Assert */
        $this->assertCount(10, $recent);
    }

    #[Test]
    public function it_returns_all_severity_keys_with_counts(): void
    {
        /* Arrange */
        ['application' => $application] = $this->createApplicationWithAccount();
        ExceptionRecord::factory()->create([
            'application_id' => $application->id,
            'severity'       => Severity::INFO->value,
        ]);
        ExceptionRecord::factory()->create([
            'application_id' => $application->id,
            'severity'       => Severity::ERROR->value,
        ]);

        /* Act */
        $counts = $this->service->getCountBySeverity($application->id);

        /* Assert */
        $this->assertArrayHasKey(Severity::INFO->value, $counts);
        $this->assertArrayHasKey(Severity::WARNING->value, $counts);
        $this->assertArrayHasKey(Severity::ERROR->value, $counts);
        $this->assertArrayHasKey(Severity::CRITICAL->value, $counts);
        $this->assertEquals(1, $counts[Severity::INFO->value]);
        $this->assertEquals(0, $counts[Severity::WARNING->value]);
        $this->assertEquals(1, $counts[Severity::ERROR->value]);
        $this->assertEquals(0, $counts[Severity::CRITICAL->value]);
    }

    #[Test]
    public function it_returns_count_by_exception_class(): void
    {
        /* Arrange */
        ['application' => $application] = $this->createApplicationWithAccount();
        ExceptionRecord::factory()->count(3)->create([
            'application_id'  => $application->id,
            'exception_class' => 'RuntimeException',
        ]);
        ExceptionRecord::factory()->count(2)->create([
            'application_id'  => $application->id,
            'exception_class' => 'InvalidArgumentException',
        ]);

        /* Act */
        $counts = $this->service->getCountByClass($application->id);

        /* Assert */
        $this->assertArrayHasKey('RuntimeException', $counts);
        $this->assertArrayHasKey('InvalidArgumentException', $counts);
        $this->assertEquals(3, $counts['RuntimeException']);
        $this->assertEquals(2, $counts['InvalidArgumentException']);
    }

    #[Test]
    public function it_returns_zeros_when_application_and_account_are_zero_or_null(): void
    {
        /* Arrange */
        $applicationId = 0;

        /* Act */
        $counts = $this->service->getCountBySeverity($applicationId);

        /* Assert */
        $this->assertEquals(0, $counts[Severity::INFO->value]);
        $this->assertEquals(0, $counts[Severity::WARNING->value]);
        $this->assertEquals(0, $counts[Severity::ERROR->value]);
        $this->assertEquals(0, $counts[Severity::CRITICAL->value]);
    }

    #[Test]
    public function it_orders_recent_exceptions_by_last_occurred_at_desc(): void
    {
        /* Arrange */
        ['application' => $application] = $this->createApplicationWithAccount();

        $oldest = ExceptionRecord::factory()->create([
            'application_id'   => $application->id,
            'last_occurred_at' => now()->subHours(3),
        ]);
        $newest = ExceptionRecord::factory()->create([
            'application_id'   => $application->id,
            'last_occurred_at' => now(),
        ]);
        $middle = ExceptionRecord::factory()->create([
            'application_id'   => $application->id,
            'last_occurred_at' => now()->subHours(1),
        ]);

        /* Act */
        $recent = $this->service->getRecentExceptions($application->id);

        /* Assert */
        $this->assertEquals($newest->id, $recent->first()->id);
        $this->assertEquals($middle->id, $recent->get(1)->id);
        $this->assertEquals($oldest->id, $recent->last()->id);
    }

    // -------------------------------------------------------------------------
    // Account-scoped tests (applicationId === 0, accountId provided)
    // -------------------------------------------------------------------------

    #[Test]
    public function it_returns_recent_exceptions_for_account_across_all_applications(): void
    {
        /* Arrange */
        $account = Account::factory()->create();
        ['application' => $app1] = $this->createApplicationWithAccount($account);
        ['application' => $app2] = $this->createApplicationWithAccount($account);

        ExceptionRecord::factory()->count(6)->create(['application_id' => $app1->id]);
        ExceptionRecord::factory()->count(6)->create(['application_id' => $app2->id]);

        /* Act */
        $recent = $this->service->getRecentExceptions(0, $account->id);

        /* Assert */
        // Limited to 10 even though 12 total exist
        $this->assertCount(10, $recent);
    }

    #[Test]
    public function it_returns_account_scoped_severity_counts_across_all_applications(): void
    {
        /* Arrange */
        $account = Account::factory()->create();
        ['application' => $app1] = $this->createApplicationWithAccount($account);
        ['application' => $app2] = $this->createApplicationWithAccount($account);

        ExceptionRecord::factory()->count(2)->create([
            'application_id' => $app1->id,
            'severity'       => Severity::ERROR->value,
        ]);
        ExceptionRecord::factory()->create([
            'application_id' => $app2->id,
            'severity'       => Severity::ERROR->value,
        ]);
        ExceptionRecord::factory()->create([
            'application_id' => $app2->id,
            'severity'       => Severity::WARNING->value,
        ]);

        /* Act */
        $counts = $this->service->getCountBySeverity(0, $account->id);

        /* Assert */
        $this->assertEquals(3, $counts[Severity::ERROR->value]);
        $this->assertEquals(1, $counts[Severity::WARNING->value]);
        $this->assertEquals(0, $counts[Severity::INFO->value]);
        $this->assertEquals(0, $counts[Severity::CRITICAL->value]);
    }

    #[Test]
    public function it_returns_account_scoped_class_counts_across_all_applications(): void
    {
        /* Arrange */
        $account = Account::factory()->create();
        ['application' => $app1] = $this->createApplicationWithAccount($account);
        ['application' => $app2] = $this->createApplicationWithAccount($account);

        ExceptionRecord::factory()->count(3)->create([
            'application_id'  => $app1->id,
            'exception_class' => 'RuntimeException',
        ]);
        ExceptionRecord::factory()->count(2)->create([
            'application_id'  => $app2->id,
            'exception_class' => 'RuntimeException',
        ]);
        ExceptionRecord::factory()->create([
            'application_id'  => $app2->id,
            'exception_class' => 'LogicException',
        ]);

        /* Act */
        $counts = $this->service->getCountByClass(0, $account->id);

        /* Assert */
        $this->assertEquals(5, $counts['RuntimeException']);
        $this->assertEquals(1, $counts['LogicException']);
    }

    #[Test]
    public function it_does_not_return_other_account_exceptions_in_account_scoped_query(): void
    {
        /* Arrange */
        $account1 = Account::factory()->create();
        $account2 = Account::factory()->create();
        ['application' => $app1] = $this->createApplicationWithAccount($account1);
        ['application' => $app2] = $this->createApplicationWithAccount($account2);

        ExceptionRecord::factory()->count(3)->create([
            'application_id' => $app1->id,
            'severity'       => Severity::ERROR->value,
        ]);
        ExceptionRecord::factory()->count(5)->create([
            'application_id' => $app2->id,
            'severity'       => Severity::ERROR->value,
        ]);

        /* Act */
        $counts1 = $this->service->getCountBySeverity(0, $account1->id);
        $counts2 = $this->service->getCountBySeverity(0, $account2->id);

        /* Assert */
        $this->assertEquals(3, $counts1[Severity::ERROR->value]);
        $this->assertEquals(5, $counts2[Severity::ERROR->value]);
    }

    #[Test]
    public function it_returns_empty_collection_when_no_application_or_account_provided(): void
    {
        /* Arrange */
        $account = Account::factory()->create();
        ['application' => $app] = $this->createApplicationWithAccount($account);
        ExceptionRecord::factory()->count(5)->create(['application_id' => $app->id]);

        /* Act */
        $recent = $this->service->getRecentExceptions(0, null);

        /* Assert */
        $this->assertCount(0, $recent);
    }

    #[Test]
    public function it_returns_zeros_when_no_application_or_account_provided_for_severity(): void
    {
        /* Arrange */
        $account = Account::factory()->create();
        ['application' => $app] = $this->createApplicationWithAccount($account);
        ExceptionRecord::factory()->create(['application_id' => $app->id]);

        /* Act */
        $counts = $this->service->getCountBySeverity(0, null);

        /* Assert */
        $this->assertEquals(0, $counts[Severity::ERROR->value]);
        $this->assertEquals(0, $counts[Severity::INFO->value]);
    }
}