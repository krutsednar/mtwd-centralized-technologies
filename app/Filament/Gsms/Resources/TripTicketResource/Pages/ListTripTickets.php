<?php

namespace App\Filament\Gsms\Resources\TripTicketResource\Pages;

use Carbon\Carbon;
use Filament\Actions;
use App\Models\TripTicket;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Gsms\Resources\TripTicketResource;

class ListTripTickets extends ListRecords
{
    protected static string $resource = TripTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('Create Trip Ticket Record')
            ->icon('heroicon-m-plus-circle')
            ->color('info')
            ->modalWidth('7xl')
            ->closeModalByClickingAway(false)
            ->model(TripTicket::class)
            ->mutateFormDataUsing(function (array $data): array {
                $ym = Carbon::now()->format('Ym');

                $latestNumber = TripTicket::count() ?? 0;

                $suffix = str_pad($latestNumber + 1, 6, '0', STR_PAD_LEFT);

                $triptix = $ym . $suffix;

                $data['ticket_no'] = $triptix;

                return $data;
            }),
        ];
    }
}
