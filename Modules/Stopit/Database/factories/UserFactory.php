<?php

namespace Modules\Stopit\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Stopit\Models\Account;
use Modules\Stopit\Models\User;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'account_id'     => Account::factory(),
            'name'           => $this->faker->name(),
            'email'          => $this->faker->unique()->safeEmail(),
            'password'       => Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }
}
