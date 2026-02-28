<?php

namespace Modules\Stopit\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Modules\Stopit\Services\DashboardService;

class ExceptionsByClassWidget extends ChartWidget
{
    public ?int $applicationId = null;

    protected static ?string $heading = 'Exceptions by Class';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $service = app(DashboardService::class);
        
        $applicationId = $this->resolveApplicationId();
        $counts = $service->getCountByClass($applicationId);
        
        $labels = array_keys($counts);
        $data = array_values($counts);
        
        return [
            'datasets' => [
                [
                    'label' => 'Occurrences',
                    'data' => $data,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
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
