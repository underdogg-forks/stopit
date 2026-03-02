<?php

namespace Stopit\src\Providers\Database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Stopit\src\Providers\Models\Account;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        $name = $this->faker->company();
        $slug = Str::slug($name);

        return [
            'name'      => $name,
            'slug'      => $slug,
            'domain'    => $slug,
            'is_active' => true,
        ];
    }
}
