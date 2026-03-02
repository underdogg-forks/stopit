<?php

namespace App\Providers\Resources\ExceptionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Providers\Resources\ExceptionResource;

class ListExceptions extends ListRecords
{
    protected static string $resource = ExceptionResource::class;
}
