<?php

namespace App\Filament\Resources\ExceptionResource\Forms;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Modules\Core\Enums\Severity;

class ExceptionForm
{
    public static function schema(): array
    {
        return [
            Select::make('application_id')
                ->relationship('application', 'name')
                ->required()
                ->disabled(),

            TextInput::make('exception_class')
                ->required()
                ->maxLength(500)
                ->disabled(),

            Textarea::make('message')
                ->required()
                ->disabled(),

            TextInput::make('file')
                ->maxLength(1000)
                ->disabled(),

            TextInput::make('line')
                ->numeric()
                ->disabled(),

            Textarea::make('stack_trace')
                ->rows(10)
                ->disabled(),

            TextInput::make('request_method')
                ->maxLength(10)
                ->disabled(),

            TextInput::make('request_url')
                ->maxLength(2048)
                ->disabled(),

            Textarea::make('headers')
                ->disabled(),

            TextInput::make('user_agent')
                ->maxLength(1000)
                ->disabled(),

            TextInput::make('ip_address')
                ->maxLength(45)
                ->disabled(),

            TextInput::make('user_id')
                ->maxLength(255)
                ->disabled(),

            KeyValue::make('context')
                ->disabled(),

            Select::make('severity')
                ->options([
                    Severity::INFO->value     => 'Info',
                    Severity::WARNING->value  => 'Warning',
                    Severity::ERROR->value    => 'Error',
                    Severity::CRITICAL->value => 'Critical',
                ])
                ->default(Severity::ERROR->value)
                ->disabled(),

            TextInput::make('occurrence_count')
                ->numeric()
                ->default(1)
                ->disabled(),

            Checkbox::make('is_resolved')
                ->default(false)
                ->disabled(),
        ];
    }
}
