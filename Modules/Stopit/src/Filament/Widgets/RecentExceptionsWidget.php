<?php

namespace Stopit\src\Providers\src\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentExceptionsWidget extends BaseWidget
{
    public ?int $applicationId = null;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        $applicationId = $this->resolveApplicationId();

        if ($applicationId === 0) {
            $query = \Stopit\src\Providers\Models\ExceptionRecord::query()
                ->whereHas('application', function ($q) {
                    $q->whereHas('accounts', function ($accountQuery) {
                        $accountQuery->whereHas('users', function ($userQuery) {
                            $userQuery->where('users.id', auth()->id());
                        });
                    });
                })
                ->orderBy('last_occurred_at', 'desc')
                ->limit(10);
        } else {
            $query = \Stopit\src\Providers\Models\ExceptionRecord::query()
                ->where('application_id', $applicationId)
                ->orderBy('last_occurred_at', 'desc')
                ->limit(10);
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
