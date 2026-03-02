<?php

namespace Stopit\src\Providers\src\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Stopit\src\Providers\Services\DashboardService;
use Stopit\src\Providers\src\Filament\Traits\HasUserAccount;

class ExceptionStatsWidget extends BaseWidget
{
    use HasUserAccount;

    public ?int $applicationId = null;

    protected function getStats(): array
    {
        $service = app(DashboardService::class);

        $applicationId = $this->resolveApplicationId();
        $accountId     = $this->getUserAccountId();
        $counts        = $service->getCountBySeverity($applicationId, $accountId);

        return [
            Stat::make('Info', $counts['info'])
                ->color('info'),
            Stat::make('Warnings', $counts['warning'])
                ->color('warning'),
            Stat::make('Errors', $counts['error'])
                ->color('danger'),
            Stat::make('Critical', $counts['critical'])
                ->color('danger'),
        ];
    }

    protected function resolveApplicationId(): int
    {
        $filters       = $this->filters ?? [];
        $selectedAppId = $filters['applicationId'] ?? $this->applicationId;

        if ($selectedAppId) {
            // Verify user has access to this application
            $application = \Stopit\src\Providers\Models\Application::where('id', $selectedAppId)
                ->whereHas('accounts.users', function ($q) {
                    $q->where('users.id', auth()->id());
                })
                ->first();

            if ($application) {
                return $application->id;
            }
        }

        return 0;
    }
}
