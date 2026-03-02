<?php

namespace Modules\Stopit\Filament\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Modules\Stopit\Filament\Resources\ExceptionResource\Forms\ExceptionForm;
use Modules\Stopit\Filament\Resources\ExceptionResource\Pages;
use Modules\Stopit\Filament\Resources\ExceptionResource\Tables\ExceptionTable;
use Modules\Stopit\Models\ExceptionRecord;

class ExceptionResource extends Resource
{
    protected static ?string $model = ExceptionRecord::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-circle';

    protected static ?string $navigationLabel = 'Exceptions';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema(ExceptionForm::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(ExceptionTable::columns())
            ->filters(ExceptionTable::filters())
            ->actions(ExceptionTable::actions())
            ->defaultSort('last_occurred_at', 'desc')
            ->modifyQueryUsing(function ($query) {
                return $query->whereHas('application', function ($q) {
                    $q->whereHas('accounts.users', function ($userQuery) {
                        $userQuery->where('users.id', auth()->id());
                    });
                });
            });
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExceptions::route('/'),
            'view' => Pages\ViewException::route('/{record}'),
        ];
    }
}
