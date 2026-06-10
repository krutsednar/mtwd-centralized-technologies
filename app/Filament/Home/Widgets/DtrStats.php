<?php

namespace App\Filament\Home\Widgets;

use App\Models\Attendance;
use App\Models\Profile;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DtrStats extends BaseWidget
{
    protected static ?int $sort = 5;

    protected function getStats(): array
    {
        $profile = Profile::forCurrentUser();
        $query = Attendance::query()
            ->where('employee_number', $profile?->employee_number)
            ->whereBetween('attendance_date', [
                now()->startOfMonth()->toDateString(),
                now()->endOfMonth()->toDateString(),
            ]);

        return [
            Stat::make('Days Present', (clone $query)->whereNotNull('morning_in')->count())
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('primary'),

            Stat::make('Days with Overtime', (clone $query)->whereNotNull('ot_in')->count())
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
