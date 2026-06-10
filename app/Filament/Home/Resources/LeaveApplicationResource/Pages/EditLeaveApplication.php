<?php

namespace App\Filament\Home\Resources\LeaveApplicationResource\Pages;

use App\Filament\Home\Resources\LeaveApplicationResource;
use App\Filament\Hris\Resources\LeaveApplicationResource as HrisLeaveResource;
use App\Models\LeaveApplication;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLeaveApplication extends EditRecord
{
    protected static string $resource = LeaveApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Withdraw')
                ->visible(fn (): bool => $this->record->isPendingHrAction()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $errors = HrisLeaveResource::wellnessErrors($this->data, $this->record->id);
        if (! empty($errors)) {
            Notification::make()
                ->title('Wellness Leave not allowed')
                ->body(implode("\n", $errors))
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        return $data;
    }

    protected function afterSave(): void
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
