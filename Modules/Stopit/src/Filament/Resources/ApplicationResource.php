<?php

namespace Stopit\src\Providers\src\Filament\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Stopit\src\Providers\Filament\Resources\ApplicationResource\Pages;
use Stopit\src\Providers\Filament\Resources\Form;
use Stopit\src\Providers\Models\Application;
use Stopit\src\Providers\src\Filament\Resources\ApplicationResource\Forms\ApplicationForm;
use Stopit\src\Providers\src\Filament\Resources\ApplicationResource\Tables\ApplicationTable;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 1;

    public static function form(Form|\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->schema(ApplicationForm::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(ApplicationTable::columns())
            ->actions(ApplicationTable::actions())
            ->modifyQueryUsing(fn ($query) => static::scopeToUserAccounts($query));
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->tap(fn ($query) => static::scopeToUserAccounts($query));
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->accounts()->exists();
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof Application
            && $record->accounts()->whereHas('users', fn ($q) => $q->where('users.id', auth()->id()))->exists();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canView($record);
    }

    public static function canDelete(Model $record): bool
    {
        return static::canView($record);
    }

    public static function getPages(): array
    {
        return [
            'index'  => \Stopit\src\Providers\src\Filament\Resources\ApplicationResource\Pages\ListApplications::route('/'),
            'create' => \Stopit\src\Providers\src\Filament\Resources\ApplicationResource\Pages\CreateApplication::route('/create'),
            'view'   => \Stopit\src\Providers\src\Filament\Resources\ApplicationResource\Pages\ViewApplication::route('/{record}'),
            'edit'   => \Stopit\src\Providers\src\Filament\Resources\ApplicationResource\Pages\EditApplication::route('/{record}/edit'),
        ];
    }

    protected static function scopeToUserAccounts(Builder $query): Builder
    {
        return $query->whereHas('accounts', function ($q) {
            $q->whereHas('users', function ($userQuery) {
                $userQuery->where('users.id', auth()->id());
            });
        });
    }
}
