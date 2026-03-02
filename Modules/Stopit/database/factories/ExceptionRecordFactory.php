<?php

namespace Stopit\src\Providers\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Enums\Severity;
use Stopit\src\Providers\Models\Application;
use Stopit\src\Providers\Models\ExceptionRecord;

class ExceptionRecordFactory extends Factory
{
    protected $model = ExceptionRecord::class;

    public function definition(): array
    {
        $now = now();

        return [
            'application_id'  => Application::factory(),
            'exception_class' => $this->faker->randomElement([
                'RuntimeException',
                'InvalidArgumentException',
                'LogicException',
                'UnexpectedValueException',
            ]),
            'message'           => $this->faker->sentence(),
            'file'              => '/app/' . $this->faker->word() . '.php',
            'line'              => $this->faker->numberBetween(1, 500),
            'stack_trace'       => $this->faker->text(500),
            'request_method'    => $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'request_url'       => $this->faker->url(),
            'headers'           => json_encode(['Accept' => 'application/json']),
            'user_agent'        => $this->faker->userAgent(),
            'ip_address'        => $this->faker->ipv4(),
            'user_id'           => (string) $this->faker->numberBetween(1, 1000),
            'context'           => ['key' => 'value'],
            'severity'          => $this->faker->randomElement(Severity::cases())->value,
            'occurrence_count'  => 1,
            'is_resolved'       => false,
            'first_occurred_at' => $now,
            'last_occurred_at'  => $now,
        ];
    }

    public function resolved(): self
    {
        return $this->state(fn (array $attributes) => [
            'is_resolved' => true,
        ]);
    }
}
