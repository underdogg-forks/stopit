<?php

namespace Modules\Stopit\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Stopit\Models\Account;
use Modules\Stopit\Models\Application;
use Modules\Stopit\Models\User;

class StopitSeeder extends Seeder
{
    public function run(): void
    {
        $account = Account::firstOrCreate(
            ['slug' => 'acme-corp'],
            ['name' => 'Acme Corp']
        );

        $user = User::firstOrCreate(
            ['email' => 'admin@acme.test'],
            [
                'name'     => 'Admin User',
                'password' => 'password',
            ]
        );

        // Attach user to account if not already attached
        if ( ! $user->accounts()->where('accounts.id', $account->id)->exists()) {
            $user->accounts()->attach($account->id, ['role' => 'owner']);
        }

        $applications = [
            ['name' => 'GitMan', 'slug' => 'gitman'],
            ['name' => 'Jobify', 'slug' => 'jobify'],
            ['name' => 'Spotivel', 'slug' => 'spotivel'],
            ['name' => 'TrollBeGone', 'slug' => 'trollbegone'],
        ];

        $verbose = $this->command->getOutput()->isVerbose();

        if ($verbose) {
            $this->command->info('Acme Corp Application Tokens:');
            $this->command->info('=============================');
        }

        foreach ($applications as $appData) {
            $plainToken  = Str::random(64);
            $hashedToken = hash('sha256', $plainToken);

            $application = Application::firstOrCreate(
                ['slug' => $appData['slug']],
                [
                    'name'      => $appData['name'],
                    'api_token' => $hashedToken,
                ]
            );

            // Attach application to account if not already attached
            if ( ! $application->accounts()->where('accounts.id', $account->id)->exists()) {
                $application->accounts()->attach($account->id);
            }

            if ($verbose) {
                $this->command->info("{$appData['name']}: {$plainToken}");
            }
        }

        if ($verbose) {
            $this->command->info('');
            $this->command->info('Admin credentials:');
            $this->command->info('Email: admin@acme.test');
            $this->command->info('Password: password');
        } else {
            $this->command->info('Seeder completed. Run with -v flag to see credentials.');
        }
    }
}
