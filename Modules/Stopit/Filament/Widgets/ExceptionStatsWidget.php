<?php

namespace Modules\Stopit\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Stopit\Filament\Traits\HasUserAccount;
use Modules\Stopit\Services\DashboardService;

class ExceptionStatsWidget extends BaseWidget
{
    use HasUserAccount;
    
    public ?int $applicationId = null;

    protected function getStats(): array
    {
        $service = app(DashboardService::class);
        
        $applicationId = $this->resolveApplicationId();
        $accountId = $this->getUserAccountId();
        $counts = $service->getCountBySeverity($applicationId, $accountId);
        
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
            // Verify user has access to this application
            $application = \Modules\Stopit\Models\Application::where('id', $selectedAppId)
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
