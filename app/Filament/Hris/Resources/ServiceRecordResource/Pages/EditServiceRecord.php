<?php

namespace App\Filament\Hris\Resources\ServiceRecordResource\Pages;

use App\Filament\Hris\Resources\ServiceRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceRecord extends EditRecord
{
    protected static string $resource = ServiceRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
