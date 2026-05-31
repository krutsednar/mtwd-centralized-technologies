<?php

namespace App\Providers\Filament;

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

class GsmsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('GSMS')
            ->path('gsms')
            ->font('Roboto')
            // ->brandLogo(asset('images/banner.png'))
            // ->brandLogoHeight('2rem')
            ->favicon(asset('images/mios-offline.png'))
            ->login(\App\Filament\Pages\Auth\RedirectLogin::class)
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Gsms/Resources'), for: 'App\\Filament\\Gsms\\Resources')
            ->discoverPages(in: app_path('Filament/Gsms/Pages'), for: 'App\\Filament\\Gsms\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Gsms/Widgets'), for: 'App\\Filament\\Gsms\\Widgets')
            ->widgets([
                \App\Shared\Filament\Widgets\AccountStatusWidget::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Account Settings')
                    ->url(fn (): string => route('filament.GSMS.pages.edit-profile'))
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
            ->plugins([
                FilamentEditProfilePlugin::make()
                    ->customProfileComponents([
                        \App\Livewire\CustomProfileComponent::class,
                    ])
                // ->slug('my-profile')
                // ->setTitle('My Profile')
                    ->setNavigationLabel('My Account')
                    ->setNavigationGroup('Account Settings')
                    ->setIcon('fas-user-edit')
                    ->setSort(10)
                // ->canAccess(fn () => auth()->user()->id === 1)
                // ->shouldRegisterNavigation(false)
                // ->shouldShowEmailForm()
                // ->shouldShowDeleteAccountForm(false)
                // ->shouldShowSanctumTokens()
                // ->shouldShowBrowserSessionsForm()
                    ->shouldShowAvatarForm(),

            ])
            ->navigationGroups([
                'Transport and Equipment',
                'Land and Structure Management',
                'Insurance Policies',
                'Transport Official Receipts',
                'Tax Documents',
                'Tables and Exports',
                'Configuration',
            ])
            ->maxContentWidth(MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop();
    }
}
