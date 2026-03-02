<?php

namespace Stopit\src\Providers\src\Filament\Resources\ExceptionResource\Tables;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Enums\Severity;
use Stopit\src\Providers\Filament\Resources\ExceptionResource\Tables\ViewAction;

class ExceptionTable
{
    public static function columns(): array
    {
        return [
            TextColumn::make('exception_class')
                ->searchable()
                ->sortable()
                ->limit(50),

            TextColumn::make('message')
                ->searchable()
                ->limit(60),

            TextColumn::make('application.name')
                ->label('Application')
                ->sortable(),

            TextColumn::make('severity')
                ->badge()
                ->color(fn ($state): string => match ($state instanceof \Modules\Core\Enums\Severity ? $state->value : $state) {
                    'info'     => 'info',
                    'warning'  => 'warning',
                    'error'    => 'danger',
                    'critical' => 'danger',
                    default    => 'gray',
                })
                ->sortable(),

            TextColumn::make('occurrence_count')
                ->label('Count')
                ->sortable(),

            IconColumn::make('is_resolved')
                ->boolean()
                ->sortable(),

            TextColumn::make('last_occurred_at')
                ->dateTime()
                ->sortable(),
        ];
    }

    public static function filters(): array
    {
        return [
            SelectFilter::make('application_id')
                ->label('Application')
                ->relationship('application', 'name')
                ->query(function (Builder $query, array $data) {
                    if ( ! empty($data['value'])) {
                        return $query->where('application_id', $data['value']);
                    }

                    return $query->whereHas('application', function (Builder $q) {
                        $q->whereHas('accounts.users', function ($userQuery) {
                            $userQuery->where('users.id', auth()->id());
                        });
                    });
                }),

            SelectFilter::make('severity')
                ->options([
                    Severity::INFO->value     => 'Info',
                    Severity::WARNING->value  => 'Warning',
                    Severity::ERROR->value    => 'Error',
                    Severity::CRITICAL->value => 'Critical',
                ]),

            Filter::make('unresolved')
                ->label('Unresolved Only')
                ->query(fn (Builder $query): Builder => $query->where('is_resolved', false))
                ->toggle(),
        ];
    }

    public static function actions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
