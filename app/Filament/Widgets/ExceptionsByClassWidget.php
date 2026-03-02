<?php

namespace App\Providers\Widgets;

use App\Providers\Traits\HasUserAccount;
use Filament\Widgets\ChartWidget;
use Modules\Stopit\Providers\Services\DashboardService;

class ExceptionsByClassWidget extends ChartWidget
{
    use HasUserAccount;

    public ?int $applicationId = null;

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $service = app(DashboardService::class);

        $applicationId = $this->resolveApplicationId();
        $accountId     = $this->getUserAccountId();
        $counts        = $service->getCountByClass($applicationId, $accountId);

        $labels = array_keys($counts);
        $data   = array_values($counts);

        return [
            'datasets' => [
                [
                    'label' => 'Occurrences',
                    'data'  => $data,
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
        $filters       = $this->filters ?? [];
        $selectedAppId = $filters['applicationId'] ?? $this->applicationId;

        if ($selectedAppId) {
            // Verify user has access to this application
            $application = \Modules\Stopit\Providers\Models\Application::where('id', $selectedAppId)
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
