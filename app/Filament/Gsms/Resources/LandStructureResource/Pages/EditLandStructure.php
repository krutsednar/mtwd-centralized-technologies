<?php

namespace App\Filament\Gsms\Resources\LandStructureResource\Pages;

use App\Filament\Gsms\Resources\LandStructureResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLandStructure extends EditRecord
{
    protected static string $resource = LandStructureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
