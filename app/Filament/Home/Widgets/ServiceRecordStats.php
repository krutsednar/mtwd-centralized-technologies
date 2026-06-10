<?php

namespace App\Filament\Home\Widgets;

use App\Models\Profile;
use App\Models\ServiceRecord;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ServiceRecordStats extends BaseWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $profile = Profile::forCurrentUser();
        $records = $profile
            ? ServiceRecord::query()->where('profile_id', $profile->id)->get()
            : collect();

        $totalDays = $records->sum(fn (ServiceRecord $sr): int => $sr->from && $sr->to ? (int) $sr->from->diffInDays($sr->to) : 0);
        $years = intdiv($totalDays, 365);
        $months = intdiv($totalDays % 365, 30);

        return [
            Stat::make('Service Record Entries', $records->count())
                ->description('Recorded appointments')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('primary'),

            Stat::make('Total Length of Service', "{$years} yr {$months} mo")
                ->description('Across all agencies')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),
        ];
    }
}
