<?php

namespace App\Providers\Resources\ApplicationResource\Forms;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Str;

class ApplicationForm
{
    public static function schema(): array
    {
        return [
            Hidden::make('account_id')
                ->default(fn () => auth()->user()->accounts()->first()?->id),

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
