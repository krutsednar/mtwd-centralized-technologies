<?php

namespace App\Filament\Hris\Resources\FaceBiometricEnrollmentResource\Pages;

use App\Filament\Hris\Resources\FaceBiometricEnrollmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFaceBiometricEnrollment extends EditRecord
{
    protected static string $resource = FaceBiometricEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
