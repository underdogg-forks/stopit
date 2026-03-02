<?php

namespace Modules\Stopit\Filament\Resources\ApplicationResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\IconPosition;
use Modules\Stopit\Filament\Resources\ApplicationResource;
use Modules\Stopit\Services\ApplicationService;

class ViewApplication extends ViewRecord
{
    public ?string $revealedToken = null;

    protected static string $resource = ApplicationResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        // Check if there's a token in the session (from creation or regeneration)
        if (session()->has('revealed_token')) {
            $this->revealedToken = session()->get('revealed_token');
            session()->forget('revealed_token');
        }
    }

    public function getRevealedToken(): ?string
    {
        return $this->revealedToken;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerate_token')
                ->label('Regenerate API Token')
                ->icon('heroicon-o-arrow-path')
                ->iconPosition(IconPosition::Before)
                ->requiresConfirmation()
                ->modalHeading(fn () => "Regenerate API Token for {$this->record->name}?")
                ->modalDescription('This will invalidate the current token. Make sure to update your application with the new token.')
                ->modalSubmitActionLabel('Regenerate Token')
                ->action(function () {
                    $service    = app(ApplicationService::class);
                    $plainToken = $service->regenerateToken($this->record->id);

                    // Set the revealed token to display in the widget
                    $this->revealedToken = $plainToken;

                    Notification::make()
                        ->title('Token Regenerated Successfully')
                        ->body('Your new API token is displayed below. Copy it now - it won\'t be shown again!')
                        ->success()
                        ->duration(10000)
                        ->send();
                }),
            \Filament\Actions\EditAction::make(),
        ];
    }

    protected function getFooterWidgets(): array
    {
        if ($this->revealedToken) {
            return [
                \Modules\Stopit\Filament\Widgets\TokenDisplayWidget::class,
            ];
        }

        return [];
    }
}
