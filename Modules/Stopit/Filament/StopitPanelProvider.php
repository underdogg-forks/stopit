<?php

namespace Modules\Stopit\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\Stopit\Filament\Pages\Dashboard;
use Modules\Stopit\Filament\Resources\ApplicationResource;
use Modules\Stopit\Filament\Resources\ExceptionResource;
use Modules\Stopit\Filament\Widgets\ExceptionStatsWidget;
use Modules\Stopit\Filament\Widgets\RecentExceptionsWidget;
use Modules\Stopit\Filament\Widgets\ExceptionsByClassWidget;

class StopitPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::hex('#5E81AC'),
                'gray' => Color::hex('#4C566A'),
                'info' => Color::hex('#81A1C1'),
                'success' => Color::hex('#A3BE8C'),
                'warning' => Color::hex('#EBCB8B'),
                'danger' => Color::hex('#BF616A'),
            ])
            ->discoverResources(in: __DIR__.'/Resources', for: 'Modules\\Stopit\\Filament\\Resources')
            ->discoverPages(in: __DIR__.'/Pages', for: 'Modules\\Stopit\\Filament\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: __DIR__.'/Widgets', for: 'Modules\\Stopit\\Filament\\Widgets')
            ->widgets([
                ExceptionStatsWidget::class,
                RecentExceptionsWidget::class,
                ExceptionsByClassWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
