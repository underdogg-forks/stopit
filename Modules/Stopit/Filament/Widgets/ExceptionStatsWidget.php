<?php

namespace Modules\Stopit\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Stopit\Services\DashboardService;

class ExceptionStatsWidget extends BaseWidget
{
    public ?int $applicationId = null;

    protected function getStats(): array
    {
        $service = app(DashboardService::class);
        
        $applicationId = $this->resolveApplicationId();
        $counts = $service->getCountBySeverity($applicationId);
        
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
        $filters = $this->filters ?? [];
        $selectedAppId = $filters['applicationId'] ?? $this->applicationId;
        
        if ($selectedAppId) {
            $userAccountId = auth()->user()->account_id;
            $application = \Modules\Stopit\Models\Application::where('id', $selectedAppId)
                ->forAccount($userAccountId)
                ->first();
            
            if ($application) {
                return $application->id;
            }
        }
        
        return 0;
    }
}
