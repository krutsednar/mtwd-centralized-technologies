<?php

namespace App\Filament\Hris\Resources\BiometricEnrollmentResource\Pages;

use App\Filament\Hris\Resources\BiometricEnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBiometricEnrollments extends ListRecords
{
    protected static string $resource = BiometricEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Biometric Enrollment'),
        ];
    }
}
