<?php

namespace App\Filament\Home\Widgets;

use App\Models\Profile;
use App\Models\Training;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TrainingStats extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $profile = Profile::forCurrentUser();
        $query = Training::query()->where('profile_id', $profile?->id ?? 0);

        return [
            Stat::make('Trainings Attended', (clone $query)->count())
                ->description('Learning & development')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('primary'),

            Stat::make('Total Training Hours', number_format((float) (clone $query)->sum('number_of_hours')).' hrs')
                ->description('Cumulative')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }
}
