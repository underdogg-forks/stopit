<?php

namespace Modules\Stopit\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('applicationId')
                    ->label('Application')
                    ->options(function () {
                        $user = auth()->user();
                        $firstAccount = $user->accounts()->first();
                        
                        if (!$firstAccount) {
                            return [];
                        }
                        
                        return $firstAccount->applications()
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->placeholder('All Applications')
                    ->native(false),
            ]);
    }
}
