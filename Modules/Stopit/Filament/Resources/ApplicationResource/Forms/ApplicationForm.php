<?php

namespace Modules\Stopit\Filament\Resources\ApplicationResource\Forms;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

class ApplicationForm
{
    public static function schema(): array
    {
        return [
            Select::make('account_id')
                ->relationship('account', 'name')
                ->required()
                ->default(fn () => auth()->user()->account_id),
                
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->reactive()
                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                
            TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
        ];
    }
}
