<?php

namespace App\Providers\Resources;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Stopit\Providers\Filament\Resources\ExceptionResource\Pages;
use Modules\Stopit\Providers\Models\ExceptionRecord;
use App\Providers\Resources\ExceptionResource\Forms\ExceptionForm;
use App\Providers\Resources\ExceptionResource\Tables\ExceptionTable;

class ExceptionResource extends Resource
{
    protected static ?string $model = ExceptionRecord::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-exclamation-circle';

    protected static ?string $navigationLabel = 'Exceptions';

    protected static ?int $navigationSort = 2;

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->schema(ExceptionForm::schema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(ExceptionTable::columns())
            ->filters(ExceptionTable::filters())
            ->actions(ExceptionTable::actions())
            ->defaultSort('last_occurred_at', 'desc')
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
        return $record instanceof ExceptionRecord
            && $record->application
            && $record->application->accounts()->whereHas('users', fn ($q) => $q->where('users.id', auth()->id()))->exists();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Providers\Resources\ExceptionResource\Pages\ListExceptions::route('/'),
            'view'  => \App\Providers\Resources\ExceptionResource\Pages\ViewException::route('/{record}'),
        ];
    }

    protected static function scopeToUserAccounts(Builder $query): Builder
    {
        return $query->whereHas('application', function ($q) {
            $q->whereHas('accounts', function ($accountQuery) {
                $accountQuery->whereHas('users', function ($userQuery) {
                    $userQuery->where('users.id', auth()->id());
                });
            });
        });
    }
}
