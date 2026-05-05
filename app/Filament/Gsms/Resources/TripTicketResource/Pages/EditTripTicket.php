<?php

namespace App\Filament\Gsms\Resources\TripTicketResource\Pages;

use App\Filament\Gsms\Resources\TripTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTripTicket extends EditRecord
{
    protected static string $resource = TripTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
