<?php

namespace App\Filament\Hris\Resources\LeaveCardResource\Pages;

use App\Filament\Hris\Resources\LeaveCardResource;
use App\Filament\Hris\Widgets\LeaveCardStats;
use App\Models\Profile;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListLeaveCards extends ListRecords
{
    protected static string $resource = LeaveCardResource::class;

    public ?int $profileId = null;

    protected $queryString = ['profileId'];

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('selectEmployee')
                ->label(fn () => $this->profileId
                    ? 'Employee: ' . (Profile::find($this->profileId)?->full_name ?? '—')
                    : 'Select Employee'
                )
                ->icon('heroicon-o-user')
                ->color(fn () => $this->profileId ? 'success' : 'gray')
                ->form([
                    Forms\Components\Select::make('profile_id')
                        ->label('Employee')
                        ->options(
                            Profile::query()
                                ->get()
                                ->mapWithKeys(fn (Profile $p) => [$p->id => $p->employee_number . ' ' . $p->full_name])
                        )
                        ->default($this->profileId)
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->profileId = (int) $data['profile_id'];
                    $this->dispatch('leaveCardProfileSelected', profileId: $this->profileId);
                })
                ->modalSubmitActionLabel('View Leave Card'),

            Actions\CreateAction::make()
                ->label('Add Entry')
                ->slideOver()
                ->mutateFormDataUsing(function (array $data): array {
                    if ($this->profileId && empty($data['profile_id'])) {
                        $data['profile_id'] = $this->profileId;
                    }

                    return $data;
                }),
        ];
    }

    protected function getTableQuery(): Builder
    {
        if (! $this->profileId) {
            return parent::getTableQuery()->whereRaw('1 = 0');
        }

        return parent::getTableQuery()->where('profile_id', $this->profileId);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LeaveCardStats::class,
        ];
    }

    public function getWidgetsData(): array
    {
        return [
            'profileId' => $this->profileId,
        ];
    }
}
