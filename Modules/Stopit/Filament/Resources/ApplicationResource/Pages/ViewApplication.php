<?php

namespace Modules\Stopit\Filament\Resources\ApplicationResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\Stopit\Filament\Resources\ApplicationResource;
use Modules\Stopit\Services\ApplicationService;

class ViewApplication extends ViewRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerate_token')
                ->label('Regenerate API Token')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading(fn () => "Regenerate API Token for {$this->record->name}?")
                ->modalDescription('This will invalidate the current token. Make sure to update your application with the new token.')
                ->action(function () {
                    $service = app(ApplicationService::class);
                    $plainToken = $service->regenerateToken($this->record->id);
                    
                    Notification::make()
                        ->title('Token Regenerated')
                        ->body("New API Token: {$plainToken}")
                        ->success()
                        ->persistent()
                        ->send();
                }),
            \Filament\Actions\EditAction::make(),
        ];
    }
}
