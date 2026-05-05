<?php

namespace App\Providers;

use App\Filament\Hris\Widgets\LeaveCardStats;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use Illuminate\Support\ServiceProvider;
use Akira\FilamentSwitchPanel\FilamentSwitchPanel;
use Livewire\Livewire;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LogoutResponseContract::class, LogoutResponse::class);
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::component('app.filament.hris.widgets.leave-card-stats', LeaveCardStats::class);

        FilamentSwitchPanel::configureUsing(function (FilamentSwitchPanel $switchPanel) {

            $switchPanel->modalHeading('Modal Heading')
            ->modalWidth('md')
            ->slideOver()
            ->simple()
            ->labels([
                'admin' => 'Admin',
                'home' => 'Home',
                'gsms' => 'GSMS',
                'hris' => 'HRIS',
            ])
            ->icons([
                'admin' => 'heroicon-o-user',
                'home' => 'heroicon-o-home',
                'gsms' => 'heroicon-o-home',
                'hris' => 'heroicon-o-home',
            ], $asImage = false)
            ->iconSize(48)
            // ->visible(fn (): bool => auth()->user()?->hasAnyRole(['super_admin', 'User']))
            // ->canSwitchPanels(fn (): bool => auth()->user()?->can('switch-panels'))
            ->excludes(['user'])
            ->renderHook('panels::global-search.before');

        });

    }
}
