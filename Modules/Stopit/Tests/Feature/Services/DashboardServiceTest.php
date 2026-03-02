<?php

namespace Tests\Feature\Services;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Stopit\Providers\Models\Account;
use Modules\Stopit\Providers\Models\Application;
use Modules\Stopit\Providers\Models\ExceptionRecord;
use Modules\Stopit\Providers\Services\DashboardService;
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

    #[Test]
    public function it_returns_recent_exceptions_limited_to_10(): void
    {
        /* Arrange */
        $account     = Account::factory()->create();
        $application = Application::factory()->create();
        $application->accounts()->attach($account->id);

        ExceptionRecord::factory()->count(15)->create([
            'application_id' => $application->id,
        ]);

        /* Act */
        $recent = $this->service->getRecentExceptions($application->id);

        /* Assert */
        $this->assertCount(10, $recent);
    }

    #[Test]
    public function it_returns_all_severity_keys_with_counts(): void
    {
        /* Arrange */
        $account     = Account::factory()->create();
        $application = Application::factory()->create();
        $application->accounts()->attach($account->id);

        ExceptionRecord::factory()->create([
            'application_id' => $application->id,
            'severity'       => 'info',
        ]);

        ExceptionRecord::factory()->create([
            'application_id' => $application->id,
            'severity'       => 'error',
        ]);

        /* Act */
        $counts = $this->service->getCountBySeverity($application->id);

        /* Assert */
        $this->assertArrayHasKey('info', $counts);
        $this->assertArrayHasKey('warning', $counts);
        $this->assertArrayHasKey('error', $counts);
        $this->assertArrayHasKey('critical', $counts);

        $this->assertEquals(1, $counts['info']);
        $this->assertEquals(0, $counts['warning']);
        $this->assertEquals(1, $counts['error']);
        $this->assertEquals(0, $counts['critical']);
    }

    #[Test]
    public function it_returns_count_by_exception_class(): void
    {
        /* Arrange */
        $account     = Account::factory()->create();
        $application = Application::factory()->create();
        $application->accounts()->attach($account->id);

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
    public function it_returns_zeros_for_empty_application(): void
    {
        /* Arrange */
        $applicationId = 0;

        /* Act */
        $counts = $this->service->getCountBySeverity($applicationId);

        /* Assert */
        $this->assertEquals(0, $counts['info']);
        $this->assertEquals(0, $counts['warning']);
        $this->assertEquals(0, $counts['error']);
        $this->assertEquals(0, $counts['critical']);
    }

    #[Test]
    public function it_orders_recent_exceptions_by_last_occurred_at_desc(): void
    {
        /* Arrange */
        $account     = Account::factory()->create();
        $application = Application::factory()->create();
        $application->accounts()->attach($account->id);

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
}
