<?php

namespace Stopit\src\Providers\src\Filament\Resources\ApplicationResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Stopit\src\Providers\src\Filament\Resources\ApplicationResource;

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
