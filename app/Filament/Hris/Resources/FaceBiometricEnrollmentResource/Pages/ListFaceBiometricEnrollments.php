<?php

namespace App\Filament\Hris\Resources\FaceBiometricEnrollmentResource\Pages;

use App\Filament\Hris\Resources\FaceBiometricEnrollmentResource;
use App\Filament\Hris\Resources\FaceBiometricEnrollmentResource\Widgets\FaceBiometricEnrollmentStats;
use App\Jobs\FaceBiometrics\BulkSeedFromProfilePicturesJob;
use App\Models\BiometricEnrollment;
use App\Models\FaceBiometrics\FaceAuditLog;
use App\Models\FaceBiometrics\FaceEmbedding;
use App\Models\FaceBiometrics\FaceProfile;
use App\Models\Profile;
use App\Services\FaceBiometricService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ListFaceBiometricEnrollments extends ListRecords
{
    protected static string $resource = FaceBiometricEnrollmentResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            FaceBiometricEnrollmentStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\Action::make('syncFromBiometricEnrollment')
            //     ->label('Sync from Biometric Enrollment')
            //     ->icon('heroicon-o-arrows-right-left')
            //     ->color('success')
            //     ->requiresConfirmation()
            //     ->modalHeading('Sync from Biometric Enrollment')
            //     ->modalDescription('This will extract face embeddings from existing Biometric Enrollment images and enroll them into Face Biometric Enrollment. Employees already enrolled (with existing embeddings) will be skipped.')
            //     ->modalSubmitActionLabel('Run Sync')
            //     ->action(function (): void {
            //         $enrollments = BiometricEnrollment::with('profile')
            //             ->where(function ($q): void {
            //                 $q->whereNotNull('image_1')
            //                     ->orWhereNotNull('image_2')
            //                     ->orWhereNotNull('image_3');
            //             })
            //             ->get();

            //         $synced = 0;
            //         $skipped = 0;
            //         $failed = 0;
            //         $failures = [];

            //         $svc = app(FaceBiometricService::class);

            //         foreach ($enrollments as $enrollment) {
            //             $profile = $enrollment->profile;

            //             if (! $profile) {
            //                 $skipped++;

            //                 continue;
            //             }

            //             $faceProfile = FaceProfile::firstOrCreate(['profile_id' => $profile->id]);

            //             if ($faceProfile->template_count > 0 || $faceProfile->is_enrolled) {
            //                 $skipped++;

            //                 continue;
            //             }

            //             $images = array_values(array_filter([
            //                 $enrollment->image_1,
            //                 $enrollment->image_2,
            //                 $enrollment->image_3,
            //             ]));

            //             if (empty($images)) {
            //                 $skipped++;

            //                 continue;
            //             }

            //             try {
            //                 DB::transaction(function () use ($faceProfile, $profile, $images, $svc, &$synced): void {
            //                     $processedCount = 0;
            //                     $bestQuality = 0.0;

            //                     foreach ($images as $imagePath) {
            //                         $absolutePath = Storage::disk('public')->path($imagePath);

            //                         if (! file_exists($absolutePath)) {
            //                             continue;
            //                         }

            //                         try {
            //                             $result = $svc->extractFromPath($absolutePath, true);
            //                         } catch (\Throwable) {
            //                             continue;
            //                         }

            //                         $embedding = $result['embedding'];
            //                         $quality = (float) ($result['quality'] ?? 0.0);

            //                         FaceEmbedding::insertVector(
            //                             $faceProfile->id,
            //                             $processedCount + 1,
            //                             $embedding,
            //                             $quality,
            //                             'biometric_sync',
            //                             $imagePath
            //                         );

            //                         if ($quality > $bestQuality) {
            //                             $bestQuality = $quality;
            //                         }

            //                         $processedCount++;
            //                     }

            //                     if ($processedCount === 0) {
            //                         throw new \RuntimeException('No valid face images could be processed.');
            //                     }

            //                     $faceProfile->update([
            //                         'is_enrolled' => true,
            //                         'template_count' => $processedCount,
            //                         'enrolled_at' => now(),
            //                         'enrollment_source' => 'biometric_sync',
            //                         'enrollment_quality_score' => $bestQuality,
            //                     ]);

            //                     FaceAuditLog::create([
            //                         'profile_id' => $profile->id,
            //                         'event' => 'enroll_sync',
            //                         'quality_score' => $bestQuality,
            //                         'source' => 'biometric_sync',
            //                         'reason' => 'sync_from_biometric_enrollment',
            //                         'created_at' => now(),
            //                     ]);

            //                     $synced++;
            //                 });
            //             } catch (\Throwable $e) {
            //                 $failed++;
            //                 $failures[] = ($profile->employee_number ?? "Profile #{$profile->id}").': '.$e->getMessage();

            //                 Log::warning("Face biometric sync failed for profile {$profile->id}: ".$e->getMessage());
            //             }
            //         }

            //         $body = "Synced: {$synced}   |   Skipped (already enrolled): {$skipped}   |   Failed: {$failed}";

            //         if (! empty($failures)) {
            //             $body .= "\n\nFailures:\n".implode("\n", array_slice($failures, 0, 5));

            //             if (count($failures) > 5) {
            //                 $body .= "\n...and ".(count($failures) - 5).' more. Check logs for details.';
            //             }
            //         }

            //         $notification = Notification::make()
            //             ->title($failed > 0 ? 'Sync Completed with Errors' : 'Sync Complete')
            //             ->body($body);

            //         $failed > 0 ? $notification->warning() : $notification->success();

            //         $notification->send();
            //     }),

            // Actions\Action::make('seedAll')
            //     ->label('Bulk Seed from Employee Pictures')
            //     ->icon('heroicon-o-photo')
            //     ->color('primary')
            //     ->modalHeading('Bulk Seed Enrollment from Employee Pictures')
            //     ->modalDescription('This will extract face embeddings from existing employee profile photos and enroll them in bulk.')
            //     ->form([
            //         Forms\Components\Select::make('profile_ids')
            //             ->label('Profiles to Seed')
            //             ->multiple()
            //             ->searchable()
            //             ->options(function () {
            //                 return Profile::whereNotNull('picture')
            //                     ->get()
            //                     ->filter(fn ($p) => Storage::disk('public')->exists($p->picture))
            //                     ->mapWithKeys(fn ($p) => [$p->id => $p->employee_number.' — '.$p->full_name])
            //                     ->toArray();
            //             })
            //             ->helperText('Leave blank to include ALL profiles with a photo and no active enrollment.')
            //             ->columnSpanFull(),

            //         Forms\Components\Toggle::make('skip_enrolled')
            //             ->label('Skip profiles already enrolled')
            //             ->default(true),

            //         Forms\Components\Toggle::make('overwrite_existing')
            //             ->label('Overwrite existing seed embeddings')
            //             ->default(false),
            //     ])
            //     ->action(function (array $data): void {
            //         $profileIds = ! empty($data['profile_ids'])
            //             ? $data['profile_ids']
            //             : Profile::whereNotNull('picture')
            //                 ->get()
            //                 ->filter(fn ($p) => Storage::disk('public')->exists($p->picture))
            //                 ->pluck('id')
            //                 ->toArray();

            //         $job = new BulkSeedFromProfilePicturesJob(
            //             $profileIds,
            //             (bool) ($data['overwrite_existing'] ?? false)
            //         );

            //         dispatch($job);

            //         Notification::make()
            //             ->title('Bulk Seed Job Queued')
            //             ->body(count($profileIds).' profiles queued. Run `php artisan queue:work` if the queue worker is not running.')
            //             ->success()
            //             ->send();
            //     }),

            Actions\CreateAction::make(),
        ];
    }
}
