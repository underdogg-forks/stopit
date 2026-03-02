<?php

namespace App\Providers\Resources\ApplicationResource\Tables;

use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Modules\Stopit\Providers\Filament\Resources\ApplicationResource\Tables\Action;
use Modules\Stopit\Providers\Filament\Resources\ApplicationResource\Tables\DeleteAction;
use Modules\Stopit\Providers\Filament\Resources\ApplicationResource\Tables\EditAction;
use Modules\Stopit\Providers\Filament\Resources\ApplicationResource\Tables\ViewAction;
use Modules\Stopit\Providers\Services\ApplicationService;

class ApplicationTable
{
    public static function columns(): array
    {
        return [
            TextColumn::make('name')
                ->searchable()
                ->sortable(),

            TextColumn::make('slug')
                ->searchable()
                ->sortable(),

            TextColumn::make('created_at')
                ->dateTime()
                ->sortable(),
        ];
    }

    public static function actions(): array
    {
        return [
            ViewAction::make(),
            EditAction::make(),
            Action::make('regenerate_token')
                ->label('Regenerate Token')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading(fn ($record) => "Regenerate API Token for {$record->name}?")
                ->modalDescription('This will invalidate the current token. Make sure to update your application with the new token.')
                ->action(function ($record) {
                    $service    = app(ApplicationService::class);
                    $plainToken = $service->regenerateToken($record->id);

                    Notification::make()
                        ->title('Token Regenerated')
                        ->body("New API Token: {$plainToken}")
                        ->success()
                        ->persistent()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
