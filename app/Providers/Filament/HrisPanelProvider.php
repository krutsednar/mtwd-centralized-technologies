<?php

namespace App\Providers\Filament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;

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
            ->discoverWidgets(in: app_path('Filament/Hris/Widgets'), for: 'App\\Filament\\Hris\\Widgets')
            ->widgets([
                \App\Shared\Filament\Widgets\AccountStatusWidget::class,
                \App\Filament\Hris\Resources\FaceBiometricEnrollmentResource\Widgets\FaceBiometricEnrollmentStats::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Account Settings')
                    ->url(fn (): string => route('filament.HRIS.pages.edit-profile'))
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
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentEditProfilePlugin::make()
                    ->customProfileComponents([
                        \App\Livewire\CustomProfileComponent::class,
                    ])
                    ->setNavigationLabel('My Account')
                    ->setNavigationGroup('Account Settings')
                    ->setIcon('fas-user-edit')
                    ->setSort(10)
                    ->shouldShowAvatarForm(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->maxContentWidth(MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop();
    }
}
