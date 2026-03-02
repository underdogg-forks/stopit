<?php

namespace App\Providers\Resources\ApplicationResource\Pages;

use App\Providers\Resources\ApplicationResource;
use Filament\Resources\Pages\EditRecord;

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
