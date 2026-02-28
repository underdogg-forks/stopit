<?php

namespace Modules\Stopit\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Modules\Stopit\Services\DashboardService;

class RecentExceptionsWidget extends BaseWidget
{
    public ?int $applicationId = null;

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        $service = app(DashboardService::class);
        
        $applicationId = $this->resolveApplicationId();
        
        if ($applicationId === 0) {
            $query = \Modules\Stopit\Models\ExceptionRecord::query()
                ->whereHas('application', function ($q) {
                    $q->forAccount(auth()->user()->account_id);
                })
                ->orderBy('last_occurred_at', 'desc')
                ->limit(10);
        } else {
            $query = $service->getRecentExceptions($applicationId);
        }
        
        return $table
            ->query($query)
            ->columns([
                TextColumn::make('exception_class')
                    ->limit(50),
                TextColumn::make('message')
                    ->limit(60),
                TextColumn::make('application.name'),
                TextColumn::make('occurrence_count')
                    ->label('Count'),
                TextColumn::make('last_occurred_at')
                    ->dateTime(),
            ]);
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
