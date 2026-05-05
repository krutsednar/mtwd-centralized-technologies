<?php

namespace App\Filament\Gsms\Resources\HeavyEquipmentTypeResource\Pages;

use App\Filament\Gsms\Resources\HeavyEquipmentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHeavyEquipmentTypes extends ListRecords
{
    protected static string $resource = HeavyEquipmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
