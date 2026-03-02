<?php

namespace Modules\Stopit\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Stopit\Models\Application;

class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        $name = $this->faker->words(2, true);

        return [
            'name'      => $name,
            'slug'      => Str::slug($name),
            'api_token' => hash('sha256', Str::random(64)),
        ];
    }
}
