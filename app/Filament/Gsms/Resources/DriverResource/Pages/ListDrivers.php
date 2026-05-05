<?php

namespace App\Filament\Gsms\Resources\DriverResource\Pages;

use Filament\Actions;
use App\Models\Driver;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Gsms\Resources\DriverResource;

class ListDrivers extends ListRecords
{
    protected static string $resource = DriverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('Create Driver Record')
            ->icon('heroicon-m-plus-circle')
            ->color('info')
            ->modalWidth('7xl')
            ->closeModalByClickingAway(false)
            ->model(Driver::class),
        ];
    }
}
