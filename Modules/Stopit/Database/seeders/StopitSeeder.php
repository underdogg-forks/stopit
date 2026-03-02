<?php

namespace Modules\Stopit\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Stopit\Models\Account;
use Modules\Stopit\Models\Application;
use Modules\Stopit\Models\User;

class StopitSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);

        User::create([
            'account_id' => $account->id,
            'name'       => 'Admin User',
            'email'      => 'admin@acme.test',
            'password'   => Hash::make('password'),
        ]);

        $applications = [
            ['name' => 'GitMan', 'slug' => 'gitman'],
            ['name' => 'Jobify', 'slug' => 'jobify'],
            ['name' => 'Spotivel', 'slug' => 'spotivel'],
            ['name' => 'TrollBeGone', 'slug' => 'trollbegone'],
        ];

        $this->command->info('Acme Corp Application Tokens:');
        $this->command->info('=============================');

        foreach ($applications as $appData) {
            $plainToken  = Str::random(64);
            $hashedToken = hash('sha256', $plainToken);

            Application::create([
                'account_id' => $account->id,
                'name'       => $appData['name'],
                'slug'       => $appData['slug'],
                'api_token'  => $hashedToken,
            ]);

            $this->command->info("{$appData['name']}: {$plainToken}");
        }

        $this->command->info('');
        $this->command->info('Admin credentials:');
        $this->command->info('Email: admin@acme.test');
        $this->command->info('Password: password');
    }
}
