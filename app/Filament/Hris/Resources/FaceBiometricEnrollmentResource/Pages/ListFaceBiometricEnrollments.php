<?php

namespace App\Filament\Hris\Resources\FaceBiometricEnrollmentResource\Pages;

use App\Filament\Hris\Resources\FaceBiometricEnrollmentResource;
use App\Filament\Hris\Resources\FaceBiometricEnrollmentResource\Widgets\FaceBiometricEnrollmentStats;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFaceBiometricEnrollments extends ListRecords
{
    protected static string $resource = FaceBiometricEnrollmentResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            FaceBiometricEnrollmentStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('forEnrollment')
                ->label('For Enrollment')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->tooltip('Download employees not yet enrolled in face biometrics (employee number, name, division)')
                ->action(fn (): \Symfony\Component\HttpFoundation\StreamedResponse => FaceBiometricEnrollmentResource::exportForEnrollment()),

            Actions\CreateAction::make(),
        ];
    }
}
