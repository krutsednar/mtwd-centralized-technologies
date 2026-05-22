<?php

namespace App\Filament\Hris\Resources\FaceBiometricEnrollmentResource\Widgets;

use App\Models\FaceBiometrics\FaceProfile;
use App\Models\Profile;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FaceBiometricEnrollmentStats extends BaseWidget
{
    protected function getStats(): array
    {
        $totalEnrolled = FaceProfile::where('is_enrolled', true)->count();
        $totalProfiles = Profile::count();
        $totalUnenrolled = $totalProfiles - $totalEnrolled;
        $averageQuality = FaceProfile::where('is_enrolled', true)->avg('enrollment_quality_score');
        $lowQualityCount = FaceProfile::where('is_enrolled', true)
            ->where('enrollment_quality_score', '<', 0.45)
            ->count();

        return [
            Stat::make('Total Enrolled Employees', number_format($totalEnrolled))
                ->description("{$totalProfiles} total profiles")
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Total Unenrolled Employees', number_format($totalUnenrolled))
                ->description('Profiles without face enrollment')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($totalUnenrolled > 0 ? 'danger' : 'success'),

            Stat::make('Average Quality Score', $averageQuality ? number_format($averageQuality, 3) : '—')
                ->description('Across all captured embeddings')
                ->descriptionIcon('heroicon-m-star')
                ->color(match (true) {
                    $averageQuality === null => 'gray',
                    $averageQuality >= 0.70 => 'success',
                    $averageQuality >= 0.55 => 'warning',
                    default => 'danger',
                }),

            Stat::make('Low Quality Captures', number_format($lowQualityCount))
                ->description('Embeddings below quality 0.45')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowQualityCount > 0 ? 'warning' : 'success'),
        ];
    }
}
