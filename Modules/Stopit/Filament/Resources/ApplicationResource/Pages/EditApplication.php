<?php

namespace Modules\Stopit\Filament\Resources\ApplicationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Modules\Stopit\Filament\Resources\ApplicationResource;

class EditApplication extends EditRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}
