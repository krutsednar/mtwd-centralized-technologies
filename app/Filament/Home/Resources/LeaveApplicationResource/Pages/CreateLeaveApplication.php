<?php

namespace App\Filament\Home\Resources\LeaveApplicationResource\Pages;

use App\Filament\Home\Resources\LeaveApplicationResource;
use App\Filament\Hris\Resources\LeaveApplicationResource as HrisLeaveResource;
use App\Models\LeaveApplication;
use App\Models\ServiceRecord;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveApplication extends CreateRecord
{
    protected static string $resource = LeaveApplicationResource::class;

    protected function fillForm(): void
    {
        $profile = LeaveApplicationResource::currentProfile();
        $latest = $profile
            ? ServiceRecord::where('profile_id', $profile->id)->orderByDesc('from')->first()
            : null;

        $this->form->fill([
            'profile_id' => $profile?->id,
            '_name' => $profile?->full_name,
            '_department' => $profile?->division?->name,
            'position' => $latest?->position,
            'salary' => $latest?->salary,
            'date_of_filing' => now(),
            'commutation' => 'requested',
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Never trust a posted profile_id — force ownership to the signed-in employee.
        $data['profile_id'] = LeaveApplicationResource::currentProfile()?->id;

        $errors = HrisLeaveResource::wellnessErrors($this->data, null);
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
