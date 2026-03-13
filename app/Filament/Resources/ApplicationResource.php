<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApplicationResource\Forms\ApplicationForm;
use App\Filament\Resources\ApplicationResource\Tables\ApplicationTable;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Stopit\Models\Application;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?int $navigationSort = 1;

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form->schema(ApplicationForm::schema());
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
            'index'  => \App\Filament\Resources\ApplicationResource\Pages\ListApplications::route('/'),
            'create' => \App\Filament\Resources\ApplicationResource\Pages\CreateApplication::route('/create'),
            'view'   => \App\Filament\Resources\ApplicationResource\Pages\ViewApplication::route('/{record}'),
            'edit'   => \App\Filament\Resources\ApplicationResource\Pages\EditApplication::route('/{record}/edit'),
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