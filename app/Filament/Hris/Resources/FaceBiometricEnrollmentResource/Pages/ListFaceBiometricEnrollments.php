<?php

namespace App\Filament\Hris\Resources\FaceBiometricEnrollmentResource\Pages;

use App\Filament\Hris\Resources\FaceBiometricEnrollmentResource;
use App\Jobs\FaceBiometrics\BulkSeedFromProfilePicturesJob;
use App\Models\Profile;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;

class ListFaceBiometricEnrollments extends ListRecords
{
    protected static string $resource = FaceBiometricEnrollmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('seedAll')
                ->label('Bulk Seed from Employee Pictures')
                ->icon('heroicon-o-photo')
                ->color('primary')
                ->modalHeading('Bulk Seed Enrollment from Employee Pictures')
                ->modalDescription('This will extract face embeddings from existing employee profile photos and enroll them in bulk.')
                ->form([
                    Forms\Components\Select::make('profile_ids')
                        ->label('Profiles to Seed')
                        ->multiple()
                        ->searchable()
                        ->options(function () {
                            return Profile::whereNotNull('picture')
                                ->get()
                                ->filter(fn ($p) => Storage::disk('public')->exists($p->picture))
                                ->mapWithKeys(fn ($p) => [$p->id => $p->employee_number.' — '.$p->full_name])
                                ->toArray();
                        })
                        ->helperText('Leave blank to include ALL profiles with a photo and no active enrollment.')
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('skip_enrolled')
                        ->label('Skip profiles already enrolled')
                        ->default(true),

                    Forms\Components\Toggle::make('overwrite_existing')
                        ->label('Overwrite existing seed embeddings')
                        ->default(false),
                ])
                ->action(function (array $data) {
                    $profileIds = ! empty($data['profile_ids'])
                        ? $data['profile_ids']
                        : Profile::whereNotNull('picture')
                            ->get()
                            ->filter(fn ($p) => Storage::disk('public')->exists($p->picture))
                            ->pluck('id')
                            ->toArray();

                    $job = new BulkSeedFromProfilePicturesJob(
                        $profileIds,
                        (bool) ($data['overwrite_existing'] ?? false)
                    );

                    dispatch($job);

                    Notification::make()
                        ->title('Bulk Seed Job Queued')
                        ->body(count($profileIds).' profiles queued. Run `php artisan queue:work` if the queue worker is not running.')
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make(),
        ];
    }
}
