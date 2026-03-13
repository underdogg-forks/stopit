<?php

namespace App\Filament\Resources\ApplicationResource\Tables;

use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Modules\Stopit\Services\ApplicationService;

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

                    session()->flash('revealed_token', $plainToken);

                    Notification::make()
                        ->title('Token Regenerated Successfully')
                        ->body('Your new API token has been generated. Copy it from the token field before navigating away.')
                        ->success()
                        ->persistent()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }
}
