<?php

namespace App\Filament\Hris\Resources\IndividualPerformanceResource\Pages;

use App\Filament\Hris\Resources\IndividualPerformanceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIndividualPerformance extends EditRecord
{
    protected static string $resource = IndividualPerformanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
