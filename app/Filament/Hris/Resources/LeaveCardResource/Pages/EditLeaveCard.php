<?php

namespace App\Filament\Hris\Resources\LeaveCardResource\Pages;

use App\Filament\Hris\Resources\LeaveCardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLeaveCard extends EditRecord
{
    protected static string $resource = LeaveCardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
