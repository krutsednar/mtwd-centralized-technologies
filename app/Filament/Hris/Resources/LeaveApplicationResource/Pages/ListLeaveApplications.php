<?php

namespace App\Filament\Hris\Resources\LeaveApplicationResource\Pages;

use App\Filament\Hris\Resources\LeaveApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeaveApplications extends ListRecords
{
    protected static string $resource = LeaveApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
