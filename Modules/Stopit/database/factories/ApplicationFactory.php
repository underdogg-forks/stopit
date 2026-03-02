<?php

namespace Stopit\src\Providers\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Stopit\src\Providers\Models\Application;

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
