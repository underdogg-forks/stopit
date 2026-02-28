<?php

namespace Modules\Stopit\Filament\Resources;

use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Modules\Stopit\Filament\Resources\ApplicationResource\Forms\ApplicationForm;
use Modules\Stopit\Filament\Resources\ApplicationResource\Pages;
use Modules\Stopit\Filament\Resources\ApplicationResource\Tables\ApplicationTable;
use Modules\Stopit\Models\Application;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema(ApplicationForm::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(ApplicationTable::columns())
            ->actions(ApplicationTable::actions())
            ->modifyQueryUsing(fn ($query) => $query->forAccount(auth()->user()->account_id));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'create' => Pages\CreateApplication::route('/create'),
            'view' => Pages\ViewApplication::route('/{record}'),
            'edit' => Pages\EditApplication::route('/{record}/edit'),
        ];
    }
}
