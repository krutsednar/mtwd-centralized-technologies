<?php

namespace App\Filament\Hris\Resources\BiometricEnrollmentResource\Pages;

use App\Filament\Hris\Resources\BiometricEnrollmentResource;
use App\Models\Profile;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreateBiometricEnrollment extends CreateRecord
{
    use HasWizard;

    protected static string $resource = BiometricEnrollmentResource::class;

    // -------------------------------------------------------------------------
    // Wizard steps — Step 1: select employee, Step 2: upload 3 images
    // -------------------------------------------------------------------------
    public function getSteps(): array
    {
        return [
            Forms\Components\Wizard\Step::make('Select Employee')
                ->icon('heroicon-o-user')
                ->description('Search and select the employee to enroll')
                ->schema([
                    Forms\Components\Select::make('profile_id')
                        ->label('Employee')
                        ->options(
                            Profile::query()
                                ->get()
                                ->mapWithKeys(fn (Profile $p) => [
                                    $p->id => $p->employee_number.' — '.$p->full_name,
                                ])
                        )
                        ->searchable()
                        ->required()
                        ->columnSpanFull()
                        ->helperText('Type to search by name or employee number.'),
                ]),

            Forms\Components\Wizard\Step::make('Upload Face Images')
                ->icon('heroicon-o-camera')
                ->description('Upload 3 clear, front-facing photos of the employee')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\FileUpload::make('image_1')
                            ->label('Image 1')
                            ->image()
                            ->required()
                            ->disk('public')
                            ->directory('biometric-images')
                            ->imagePreviewHeight('220')
                            ->maxSize(20480)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Front-facing, good lighting'),

                        Forms\Components\FileUpload::make('image_2')
                            ->label('Image 2')
                            ->image()
                            ->required()
                            ->disk('public')
                            ->directory('biometric-images')
                            ->imagePreviewHeight('220')
                            ->maxSize(20480)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Slight left angle'),

                        Forms\Components\FileUpload::make('image_3')
                            ->label('Image 3')
                            ->image()
                            ->required()
                            ->disk('public')
                            ->directory('biometric-images')
                            ->imagePreviewHeight('220')
                            ->maxSize(20480)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Slight right angle'),
                    ]),
                ]),
        ];
    }

    // -------------------------------------------------------------------------
    // Stamp enrolled_at before the record is created
    // -------------------------------------------------------------------------
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['enrolled_at'] = now();

        return $data;
    }

    // -------------------------------------------------------------------------
    // After the record is saved to DB: push each image to CompreFace
    // -------------------------------------------------------------------------
    protected function afterCreate(): void
    {
        $record        = $this->record;
        $profile       = Profile::find($record->profile_id);
        $compreFaceUrl = config('services.compreface.url');
        $compreFaceKey = config('services.compreface.key');

        if (! $profile) {
            return;
        }

        // ── CompreFace not configured ────────────────────────────────────────
        if (! $compreFaceUrl || ! $compreFaceKey) {
            Notification::make()
                ->title('Saved Locally — CompreFace Skipped')
                ->body('Images saved to storage. Configure COMPREFACE_URL and COMPREFACE_KEY in .env to enable face recognition.')
                ->warning()
                ->persistent()
                ->send();

            $profile->update(['face_enrolled' => true]);

            return;
        }

        // ── Send each image to CompreFace ────────────────────────────────────
        $successCount = 0;
        $faceIds      = [];

        foreach (['image_1', 'image_2', 'image_3'] as $field) {
            $path = $record->$field;

            if (! $path || ! Storage::disk('public')->exists($path)) {
                continue;
            }

            $fullPath  = Storage::disk('public')->path($path);
            $resizedTmp = null;

            try {
                // Resize to max 1024 px on the longest side before POSTing.
                // Phone photos are typically 3–5 MB / 4 000 px wide — far larger
                // than CompreFace needs.  Resizing reduces the payload to ~100 KB
                // and cuts per-image processing time from 15 s+ to under 1 s.
                $resizedTmp = $this->resizeForCompreFace($fullPath);
                $sendPath   = $resizedTmp ?? $fullPath;

                $response = Http::timeout(60)
                    ->withHeaders(['x-api-key' => $compreFaceKey])
                    ->attach('file', fopen($sendPath, 'r'), basename($path))
                    ->post("{$compreFaceUrl}/api/v1/recognition/faces?subject={$profile->employee_number}");

                if ($response->successful()) {
                    $successCount++;
                    $imageId = data_get($response->json(), 'image_id');
                    if ($imageId) {
                        $faceIds[] = $imageId;
                    }
                } else {
                    Log::warning("CompreFace rejected {$field} (HTTP {$response->status()}): " . $response->body());
                }
            } catch (\Throwable $e) {
                Log::warning("CompreFace upload failed for {$field}: " . $e->getMessage());
            } finally {
                // Clean up the temporary resized file
                if ($resizedTmp && file_exists($resizedTmp)) {
                    @unlink($resizedTmp);
                }
            }
        }

        // ── Persist CompreFace face IDs and update profile flag ──────────────
        $record->update(['compreface_face_ids' => $faceIds]);
        $profile->update(['face_enrolled' => true]);

        // ── Feedback notification ────────────────────────────────────────────
        if ($successCount === 3) {
            Notification::make()
                ->title('Enrollment Complete')
                ->body("All 3 images enrolled for {$profile->full_name} ({$profile->employee_number}).")
                ->success()
                ->send();
        } elseif ($successCount > 0) {
            Notification::make()
                ->title('Partial Enrollment')
                ->body("{$successCount}/3 images enrolled for {$profile->full_name}. Check the CompreFace service.")
                ->warning()
                ->persistent()
                ->send();
        } else {
            Notification::make()
                ->title('CompreFace Unreachable')
                ->body('Images saved locally but could not reach CompreFace. Check COMPREFACE_URL and COMPREFACE_KEY.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    // -------------------------------------------------------------------------
    // Resize an image to max 1024 px on its longest side using GD.
    // Returns the path of the temporary resized file, or null if the image is
    // already small enough or GD cannot handle the format.
    // The caller is responsible for deleting the returned tmp file.
    // -------------------------------------------------------------------------
    private function resizeForCompreFace(string $fullPath): ?string
    {
        if (! function_exists('imagecreatefromjpeg')) {
            return null; // GD not available
        }

        $info = @getimagesize($fullPath);
        if (! $info) {
            return null;
        }

        [$origW, $origH, $type] = $info;

        $maxDim = 1024;

        // Already small enough — send as-is
        if ($origW <= $maxDim && $origH <= $maxDim) {
            return null;
        }

        // Load source image
        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($fullPath),
            IMAGETYPE_PNG  => @imagecreatefrompng($fullPath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($fullPath),
            default        => null,
        };

        if (! $src) {
            return null;
        }

        // Calculate new dimensions keeping aspect ratio
        $ratio  = min($maxDim / $origW, $maxDim / $origH);
        $newW   = (int) round($origW * $ratio);
        $newH   = (int) round($origH * $ratio);

        $dst = imagecreatetruecolor($newW, $newH);

        // Preserve transparency for PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);

        // Save as JPEG (CompreFace accepts JPEG; smaller than PNG)
        $tmpPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cf_' . uniqid() . '.jpg';
        imagejpeg($dst, $tmpPath, 92);
        imagedestroy($dst);

        return $tmpPath;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
