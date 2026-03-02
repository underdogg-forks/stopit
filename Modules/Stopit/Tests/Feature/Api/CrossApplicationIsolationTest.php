<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Stopit\Providers\Models\Account;
use Modules\Stopit\Providers\Models\Application;
use Modules\Stopit\Providers\Models\ExceptionRecord;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[Group('api')]
#[Group('isolation')]
class CrossApplicationIsolationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_resolves_token_to_correct_application(): void
    {
        /* Arrange */
        $account = Account::factory()->create();

        $token1 = 'token1-123456789012345678901234567890123456789012345678901234';
        $app1   = Application::factory()->create([
            'name'      => 'App1',
            'api_token' => hash('sha256', $token1),
        ]);
        $app1->accounts()->attach($account->id);

        $token2 = 'token2-123456789012345678901234567890123456789012345678901234';
        $app2   = Application::factory()->create([
            'name'      => 'App2',
            'api_token' => hash('sha256', $token2),
        ]);
        $app2->accounts()->attach($account->id);

        /* Act */
        $response1 = $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'From App1',
        ], ['Authorization' => "Bearer {$token1}"]);

        $response2 = $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'From App2',
        ], ['Authorization' => "Bearer {$token2}"]);

        /* Assert */
        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $this->assertDatabaseHas('exceptions', [
            'application_id' => $app1->id,
            'message'        => 'From App1',
        ]);

        $this->assertDatabaseHas('exceptions', [
            'application_id' => $app2->id,
            'message'        => 'From App2',
        ]);
    }

    #[Test]
    public function it_writes_exception_to_correct_application_only(): void
    {
        /* Arrange */
        $account = Account::factory()->create();

        $token1 = 'token1-123456789012345678901234567890123456789012345678901234';
        $app1   = Application::factory()->create([
            'api_token' => hash('sha256', $token1),
        ]);
        $app1->accounts()->attach($account->id);

        $token2 = 'token2-123456789012345678901234567890123456789012345678901234';
        $app2   = Application::factory()->create([
            'api_token' => hash('sha256', $token2),
        ]);
        $app2->accounts()->attach($account->id);

        /* Act */
        $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Error message',
        ], ['Authorization' => "Bearer {$token1}"]);

        /* Assert */
        $this->assertEquals(1, ExceptionRecord::where('application_id', $app1->id)->count());
        $this->assertEquals(0, ExceptionRecord::where('application_id', $app2->id)->count());
    }

    #[Test]
    public function it_cannot_pollute_other_application(): void
    {
        /* Arrange */
        $account = Account::factory()->create();

        $token1 = 'token1-123456789012345678901234567890123456789012345678901234';
        $app1   = Application::factory()->create([
            'api_token' => hash('sha256', $token1),
        ]);
        $app1->accounts()->attach($account->id);

        $token2 = 'token2-123456789012345678901234567890123456789012345678901234';
        $app2   = Application::factory()->create([
            'api_token' => hash('sha256', $token2),
        ]);
        $app2->accounts()->attach($account->id);

        /* Act */
        $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Error',
        ], ['Authorization' => "Bearer {$token1}"]);

        $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Error',
        ], ['Authorization' => "Bearer {$token1}"]);

        $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Error',
        ], ['Authorization' => "Bearer {$token2}"]);

        /* Assert */
        $app1Exception = ExceptionRecord::where('application_id', $app1->id)->first();
        $app2Exception = ExceptionRecord::where('application_id', $app2->id)->first();

        $this->assertEquals(2, $app1Exception->occurrence_count);
        $this->assertEquals(1, $app2Exception->occurrence_count);
    }

    #[Test]
    public function it_keeps_occurrence_counts_separate_per_application(): void
    {
        /* Arrange */
        $account = Account::factory()->create();

        $token1 = 'token1-123456789012345678901234567890123456789012345678901234';
        $app1   = Application::factory()->create([
            'api_token' => hash('sha256', $token1),
        ]);
        $app1->accounts()->attach($account->id);

        $token2 = 'token2-123456789012345678901234567890123456789012345678901234';
        $app2   = Application::factory()->create([
            'api_token' => hash('sha256', $token2),
        ]);
        $app2->accounts()->attach($account->id);

        $payload = [
            'exception_class' => 'RuntimeException',
            'message'         => 'Identical error',
        ];

        /* Act */
        $this->postJson('/api/v1/exceptions', $payload, ['Authorization' => "Bearer {$token1}"]);
        $this->postJson('/api/v1/exceptions', $payload, ['Authorization' => "Bearer {$token1}"]);
        $this->postJson('/api/v1/exceptions', $payload, ['Authorization' => "Bearer {$token1}"]);

        $this->postJson('/api/v1/exceptions', $payload, ['Authorization' => "Bearer {$token2}"]);
        $this->postJson('/api/v1/exceptions', $payload, ['Authorization' => "Bearer {$token2}"]);

        /* Assert */
        $app1Exception = ExceptionRecord::where('application_id', $app1->id)->first();
        $app2Exception = ExceptionRecord::where('application_id', $app2->id)->first();

        $this->assertEquals(3, $app1Exception->occurrence_count);
        $this->assertEquals(2, $app2Exception->occurrence_count);
    }

    #[Test]
    public function it_isolates_exceptions_across_different_accounts(): void
    {
        /* Arrange */
        $account1 = Account::factory()->create(['name' => 'Account 1']);
        $account2 = Account::factory()->create(['name' => 'Account 2']);

        $token1 = 'token1-123456789012345678901234567890123456789012345678901234';
        $app1   = Application::factory()->create([
            'api_token' => hash('sha256', $token1),
        ]);
        $app1->accounts()->attach($account1->id);

        $token2 = 'token2-123456789012345678901234567890123456789012345678901234';
        $app2   = Application::factory()->create([
            'api_token' => hash('sha256', $token2),
        ]);
        $app2->accounts()->attach($account2->id);

        /* Act */
        $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Same message',
        ], ['Authorization' => "Bearer {$token1}"]);

        $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Same message',
        ], ['Authorization' => "Bearer {$token2}"]);

        /* Assert */
        $this->assertDatabaseCount('exceptions', 2);

        $app1Exception = ExceptionRecord::where('application_id', $app1->id)->first();
        $app2Exception = ExceptionRecord::where('application_id', $app2->id)->first();

        // Verify applications belong to different accounts via pivot
        $this->assertTrue($app1Exception->application->accounts()->where('accounts.id', $account1->id)->exists());
        $this->assertTrue($app2Exception->application->accounts()->where('accounts.id', $account2->id)->exists());
        $this->assertEquals(1, $app1Exception->occurrence_count);
        $this->assertEquals(1, $app2Exception->occurrence_count);
    }

    #[Test]
    public function it_prevents_token_from_accessing_other_account_data(): void
    {
        /* Arrange */
        $account1 = Account::factory()->create(['name' => 'Account 1']);
        $account2 = Account::factory()->create(['name' => 'Account 2']);

        $token1 = 'token1-123456789012345678901234567890123456789012345678901234';
        $app1   = Application::factory()->create([
            'api_token' => hash('sha256', $token1),
        ]);
        $app1->accounts()->attach($account1->id);

        $app2 = Application::factory()->create();
        $app2->accounts()->attach($account2->id);

        /* Act */
        $this->postJson('/api/v1/exceptions', [
            'exception_class' => 'TestException',
            'message'         => 'Error',
        ], ['Authorization' => "Bearer {$token1}"]);

        /* Assert */
        $this->assertEquals(1, ExceptionRecord::whereHas('application', function ($q) use ($account1) {
            $q->whereHas('accounts', fn ($aq) => $aq->where('accounts.id', $account1->id));
        })->count());

        $this->assertEquals(0, ExceptionRecord::whereHas('application', function ($q) use ($account2) {
            $q->whereHas('accounts', fn ($aq) => $aq->where('accounts.id', $account2->id));
        })->count());
    }
}
