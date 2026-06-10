<?php

namespace App\Filament\Home\Resources\LeaveApplicationResource\Pages;

use App\Filament\Home\Resources\LeaveApplicationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLeaveApplication extends ViewRecord
{
    protected static string $resource = LeaveApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (): bool => $this->record->isPendingHrAction()),
        ];
    }
}
