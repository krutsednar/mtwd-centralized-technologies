<?php

namespace App\Filament\Gsms\Resources\LandStructureTypeResource\Pages;

use App\Filament\Gsms\Resources\LandStructureTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLandStructureType extends EditRecord
{
    protected static string $resource = LandStructureTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
