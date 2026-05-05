<?php

namespace App\Filament\Gsms\Resources\LandStructureTypeResource\Pages;

use App\Filament\Gsms\Resources\LandStructureTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLandStructureTypes extends ListRecords
{
    protected static string $resource = LandStructureTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
