<?php

namespace App\Filament\Gsms\Resources\EquipmentResource\Pages;

use App\Filament\Gsms\Resources\EquipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEquipment extends ListRecords
{
    protected static string $resource = EquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('Create Equipment Record')
            ->icon('heroicon-m-plus-circle')
            ->color('info'),
        ];
    }
}
