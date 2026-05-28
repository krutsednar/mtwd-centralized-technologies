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
            Actions\CreateAction::make(),
        ];
    }
}
