<?php

namespace App\Providers;

use App\Filament\Hris\Widgets\LeaveCardStats;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use App\Livewire\Home\HomeDtrViewer;
use App\Livewire\Home\LeaveCardTable;
use App\Livewire\Home\PerformanceCards;
use App\Livewire\Home\ProfileViewer;
use App\Livewire\Home\ServiceRecordTable;
use App\Livewire\Home\TrainingsList;
use Illuminate\Support\Facades\URL;
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
        if (str_starts_with(config('app.url'), 'https')) {
            URL::forceScheme('https');
        }

        Livewire::component('app.filament.hris.widgets.leave-card-stats', LeaveCardStats::class);

        // Home panel components
        Livewire::component('home.profile-viewer',      ProfileViewer::class);
        Livewire::component('home.home-dtr-viewer',     HomeDtrViewer::class);
        Livewire::component('home.trainings-list',      TrainingsList::class);
        Livewire::component('home.service-record-table', ServiceRecordTable::class);
        Livewire::component('home.performance-cards',   PerformanceCards::class);
        Livewire::component('home.leave-card-table',    LeaveCardTable::class);

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
