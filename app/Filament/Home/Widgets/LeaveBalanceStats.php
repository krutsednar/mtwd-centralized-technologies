<?php

namespace App\Filament\Home\Widgets;

use App\Models\LeaveCard;
use App\Models\Profile;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LeaveBalanceStats extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $profile = Profile::forCurrentUser();
        $entries = $profile
            ? LeaveCard::query()->where('profile_id', $profile->id)->get()
            : collect();

        $vl = $entries->sum(fn (LeaveCard $e): float => (float) $e->vl_earned - (float) $e->vl_with_pay - (float) $e->vl_without_pay);
        $sl = $entries->sum(fn (LeaveCard $e): float => (float) $e->sl_earned - (float) $e->sl_with_pay - (float) $e->sl_without_pay);

        return [
            Stat::make('Vacation Leave Balance', number_format($vl, 3))
                ->description('VL credits')
                ->descriptionIcon('heroicon-m-sun')
                ->color($vl >= 0 ? 'success' : 'danger'),

            Stat::make('Sick Leave Balance', number_format($sl, 3))
                ->description('SL credits')
                ->descriptionIcon('heroicon-m-heart')
                ->color($sl >= 0 ? 'info' : 'danger'),

            Stat::make('Total Leave Credits', number_format($vl + $sl, 3))
                ->description('VL + SL')
                ->descriptionIcon('heroicon-m-calculator')
                ->color($vl + $sl >= 0 ? 'warning' : 'danger'),
        ];
    }
}
