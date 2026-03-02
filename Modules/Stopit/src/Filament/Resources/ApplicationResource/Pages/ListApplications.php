<?php

namespace Stopit\src\Providers\src\Filament\Resources\ApplicationResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Stopit\src\Providers\src\Filament\Resources\ApplicationResource;

class ListApplications extends ListRecords
{
    protected static string $resource = ApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
