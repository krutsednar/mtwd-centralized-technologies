<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Navigation\MenuItem;
use App\Filament\Pages\Auth\Login;
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
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;

class HomePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('home')
            ->path('home')
            ->login(Login::class)
            ->favicon(asset('images/mios-offline.png'))
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Home/Resources'), for: 'App\\Filament\\Home\\Resources')
            ->discoverPages(in: app_path('Filament/Home/Pages'), for: 'App\\Filament\\Home\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Home/Widgets'), for: 'App\\Filament\\Home\\Widgets')
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
                ->shouldShowAvatarForm()

            ])
            ->maxContentWidth(MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop();
    }

    // public function widgets(): array
    // {
    //     return [
    //         CustomAccountWidget::class,
    //     ];
    // }
}
