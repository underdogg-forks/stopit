<?php

namespace App\Providers\Resources\ExceptionResource\Pages;

use App\Providers\Resources\ExceptionResource;
use Filament\Resources\Pages\ListRecords;

class ListExceptions extends ListRecords
{
    protected static string $resource = ExceptionResource::class;
}
