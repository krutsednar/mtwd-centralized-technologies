<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Navigation\MenuItem;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class HrisPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('HRIS')
            ->path('hris')
            ->favicon(asset('images/mios-offline.png'))
            ->login(\App\Filament\Pages\Auth\RedirectLogin::class)
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Hris/Resources'), for: 'App\\Filament\\Hris\\Resources')
            ->discoverPages(in: app_path('Filament/Hris/Pages'), for: 'App\\Filament\\Hris\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                \App\Filament\Home\Resources\HomeResource\Widgets\CustomAccountWidget::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Account Settings')
                    ->url('/edit-profile')
                    ->icon('heroicon-o-cog-6-tooth'),
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
            ])
            ->maxContentWidth(MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop();
    }
}
