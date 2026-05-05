<?php

namespace App\Filament\Hris\Resources\LeaveApplicationResource\Pages;

use App\Filament\Hris\Resources\LeaveApplicationResource;
use App\Models\LeaveApplication;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveApplication extends CreateRecord
{
    protected static string $resource = LeaveApplicationResource::class;

    protected function afterCreate(): void
    {
        $this->syncDaysApplied($this->record);
    }

    private function syncDaysApplied(LeaveApplication $record): void
    {
        if (in_array($record->leave_type, LeaveApplication::RANGE_BASED_LEAVE_TYPES)) {
            if ($record->from && $record->to) {
                $record->update([
                    'days_applied_number' => $record->from->diffInDays($record->to) + 1,
                ]);
            }
        } else {
            $record->update([
                'days_applied_number' => $record->inclusiveDates()->sum('duration'),
            ]);
        }
    }
}
