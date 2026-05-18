<?php

namespace App\Filament\Hris\Resources\FaceBiometricEnrollmentResource\Pages;

use App\Exceptions\FaceBiometricException;
use App\Filament\Hris\Resources\FaceBiometricEnrollmentResource;
use App\Models\FaceBiometrics\FaceAuditLog;
use App\Models\FaceBiometrics\FaceEmbedding;
use App\Models\FaceBiometrics\FaceProfile;
use App\Models\Profile;
use App\Services\FaceBiometricService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CreateFaceBiometricEnrollment extends CreateRecord
{
    use HasWizard;

    protected static string $resource = FaceBiometricEnrollmentResource::class;

    public function getSteps(): array
    {
        return [
            // ── Step 1: Select Employee ───────────────────────────────────────
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
                        ->live()
                        ->columnSpanFull()
                        ->helperText('Type to search by name or employee number.'),

                    Forms\Components\Placeholder::make('existing_photo_preview')
                        ->label('Existing Profile Photo')
                        ->content(function (Forms\Get $get) {
                            $profileId = $get('profile_id');
                            if (! $profileId) {
                                return 'Select an employee to preview their photo.';
                            }
                            $profile = Profile::find($profileId);
                            if (! $profile || ! $profile->picture || ! Storage::disk('public')->exists($profile->picture)) {
                                return new \Illuminate\Support\HtmlString(
                                    '<p class="text-sm text-yellow-600">This employee has no profile photo on file. You will need to upload photos or use webcam.</p>'
                                );
                            }
                            $url = Storage::disk('public')->url($profile->picture);

                            return new \Illuminate\Support\HtmlString(
                                "<img src=\"{$url}\" class=\"w-32 h-32 object-cover rounded-xl shadow\" alt=\"Profile photo\">"
                                .'<p class="text-sm text-green-600 mt-2">✓ This employee already has a photo on file. You can enroll using that photo, upload new ones, or use the live webcam — or any combination.</p>'
                            );
                        })
                        ->columnSpanFull(),
                ]),

            // ── Step 2: Enrollment Source ─────────────────────────────────────
            Forms\Components\Wizard\Step::make('Enrollment Source')
                ->icon('heroicon-o-camera')
                ->description('Choose how to capture biometric data')
                ->schema([
                    Forms\Components\Radio::make('enrollment_source_choice')
                        ->label('Enrollment Method')
                        ->options([
                            'profile_picture' => 'Use existing employee picture (fastest)',
                            'upload' => 'Upload new photos (3–5 images)',
                            'webcam' => 'Open live webcam kiosk',
                        ])
                        ->default('profile_picture')
                        ->live()
                        ->required()
                        ->columnSpanFull(),

                    // Profile picture confirm
                    Forms\Components\Placeholder::make('profile_pic_confirm')
                        ->label('Profile Picture Enrollment')
                        ->content(function (Forms\Get $get) {
                            $profileId = $get('profile_id');
                            if (! $profileId) {
                                return 'Please go back and select an employee.';
                            }
                            $profile = Profile::find($profileId);
                            if (! $profile?->picture || ! Storage::disk('public')->exists($profile->picture)) {
                                return new \Illuminate\Support\HtmlString(
                                    '<p class="text-red-600 text-sm">No profile photo found. Please choose "Upload" instead.</p>'
                                );
                            }
                            $url = Storage::disk('public')->url($profile->picture);

                            return new \Illuminate\Support\HtmlString(
                                "<img src=\"{$url}\" class=\"w-24 h-24 object-cover rounded-xl shadow\" alt=\"Profile photo\">"
                                .'<p class="text-sm text-gray-600 mt-2">The existing profile photo will be sent to the face recognition service.</p>'
                            );
                        })
                        ->visible(fn (Forms\Get $get) => $get('enrollment_source_choice') === 'profile_picture')
                        ->columnSpanFull(),

                    // Upload photos
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\FileUpload::make('image_1')
                                ->label('Image 1 — Frontal')
                                ->image()
                                ->required()
                                ->disk('public')
                                ->directory('face-biometric-images')
                                ->imagePreviewHeight('220')
                                ->maxSize(20480)
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->helperText('Front-facing'),

                            Forms\Components\FileUpload::make('image_2')
                                ->label('Image 2 — Slight Left ~15°')
                                ->image()
                                ->required()
                                ->disk('public')
                                ->directory('face-biometric-images')
                                ->imagePreviewHeight('220')
                                ->maxSize(20480)
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->helperText('Slight left angle'),

                            Forms\Components\FileUpload::make('image_3')
                                ->label('Image 3 — Slight Right ~15°')
                                ->image()
                                ->required()
                                ->disk('public')
                                ->directory('face-biometric-images')
                                ->imagePreviewHeight('220')
                                ->maxSize(20480)
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->helperText('Slight right angle'),

                            Forms\Components\FileUpload::make('image_4')
                                ->label('Image 4 — Slight Up (optional)')
                                ->image()
                                ->disk('public')
                                ->directory('face-biometric-images')
                                ->imagePreviewHeight('220')
                                ->maxSize(20480)
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->helperText('Slight upward angle'),

                            Forms\Components\FileUpload::make('image_5')
                                ->label('Image 5 — Glasses (optional)')
                                ->image()
                                ->disk('public')
                                ->directory('face-biometric-images')
                                ->imagePreviewHeight('220')
                                ->maxSize(20480)
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                ->helperText('Glasses on, if applicable'),
                        ])
                        ->visible(fn (Forms\Get $get) => $get('enrollment_source_choice') === 'upload')
                        ->columnSpanFull(),

                    // Webcam link
                    Forms\Components\Placeholder::make('webcam_instructions')
                        ->label('Live Webcam Enrollment')
                        ->content(function (Forms\Get $get) {
                            $profileId = $get('profile_id');
                            $url = $profileId
                                ? route('face-biometrics.enroll', ['profile_id' => $profileId])
                                : route('face-biometrics.enroll');

                            return new \Illuminate\Support\HtmlString(
                                '<a href="'.$url.'" target="_blank" '
                                .'class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-500 text-sm font-medium">'
                                .'↗ Open Webcam Enrollment Kiosk</a>'
                                .'<p class="text-sm text-gray-500 mt-2">Complete the webcam enrollment in the new tab, then return here and click Next to confirm.</p>'
                            );
                        })
                        ->visible(fn (Forms\Get $get) => $get('enrollment_source_choice') === 'webcam')
                        ->columnSpanFull(),
                ]),

            // ── Step 3: Review & Confirm ──────────────────────────────────────
            Forms\Components\Wizard\Step::make('Review & Confirm')
                ->icon('heroicon-o-check-circle')
                ->description('Review your selections and finalize enrollment')
                ->schema([
                    Forms\Components\Placeholder::make('summary')
                        ->label('Enrollment Summary')
                        ->content(function (Forms\Get $get) {
                            $profileId = $get('profile_id');
                            $method = $get('enrollment_source_choice');
                            $profile = $profileId ? Profile::find($profileId) : null;

                            $imageCount = match ($method) {
                                'profile_picture' => 1,
                                'upload' => collect(['image_1', 'image_2', 'image_3', 'image_4', 'image_5'])
                                    ->filter(fn ($k) => ! empty($get($k)))->count(),
                                'webcam' => 'Up to 5 (captured via kiosk)',
                                default => 0,
                            };

                            return new \Illuminate\Support\HtmlString(
                                '<ul class="text-sm space-y-1">'
                                .'<li><strong>Employee:</strong> '.($profile ? $profile->employee_number.' — '.$profile->full_name : 'Not selected').'</li>'
                                .'<li><strong>Method:</strong> '.$method.'</li>'
                                .'<li><strong>Images:</strong> '.$imageCount.'</li>'
                                .'</ul>'
                                .'<p class="text-xs text-gray-500 mt-3">Click "Create" to submit. Each image will be processed by the face recognition service.</p>'
                            );
                        })
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected function handleRecordCreation(array $data): FaceProfile
    {
        $profileId = $data['profile_id'];
        $method = $data['enrollment_source_choice'] ?? 'profile_picture';

        $svc = app(FaceBiometricService::class);

        return DB::transaction(function () use ($profileId, $method, $data, $svc) {
            $faceProfile = FaceProfile::updateOrCreate(
                ['profile_id' => $profileId],
                []
            );

            $embeddings = [];
            $sources = [];
            $slotOffset = $faceProfile->template_count;

            if ($method === 'profile_picture') {
                $profile = Profile::findOrFail($profileId);

                if (! $profile->picture || ! Storage::disk('public')->exists($profile->picture)) {
                    throw new \RuntimeException('Profile picture not found.');
                }

                $absPath = Storage::disk('public')->path($profile->picture);
                $result = $svc->extractFromPath($absPath, true);

                $embeddings[] = ['embedding' => $result['embedding'], 'quality' => $result['quality'] ?? 0.0, 'source' => 'profile_picture', 'path' => $profile->picture];
                $sources[] = 'profile_picture';
            } elseif ($method === 'upload') {
                foreach (['image_1', 'image_2', 'image_3', 'image_4', 'image_5'] as $field) {
                    $path = $data[$field] ?? null;
                    if (! $path) {
                        continue;
                    }

                    $absPath = Storage::disk('public')->path($path);
                    $resized = $this->resizeForExtract($absPath);
                    $sendPath = $resized ?? $absPath;

                    try {
                        $result = $svc->extractFromPath($sendPath, true);
                        $embeddings[] = ['embedding' => $result['embedding'], 'quality' => $result['quality'] ?? 0.0, 'source' => 'upload', 'path' => $path];
                        $sources[] = 'upload';
                    } catch (FaceBiometricException $e) {
                        if ($resized && file_exists($resized)) {
                            @unlink($resized);
                        }
                        throw new \RuntimeException("Failed on {$field}: {$e->getMessage()}");
                    } finally {
                        if ($resized && file_exists($resized)) {
                            @unlink($resized);
                        }
                    }
                }

                if (empty($embeddings)) {
                    throw new \RuntimeException('No images could be processed. Ensure you uploaded at least 1 image.');
                }
            } elseif ($method === 'webcam') {
                // Webcam enrollment is done via the kiosk separately — just confirm the faceProfile exists
                $sources[] = 'webcam';
            }

            // Insert embeddings
            $slot = $slotOffset + 1;
            foreach ($embeddings as $emb) {
                FaceEmbedding::insertVector($faceProfile->id, $slot, $emb['embedding'], $emb['quality'], $emb['source'], $emb['path'] ?? null);

                FaceAuditLog::create([
                    'profile_id' => $profileId,
                    'event' => 'enroll',
                    'quality_score' => $emb['quality'],
                    'source' => $emb['source'],
                    'reason' => 'wizard_enrollment_slot_'.$slot,
                    'created_at' => now(),
                ]);

                $slot++;
            }

            $totalSlots = count($embeddings) + $slotOffset;
            $avgQuality = ! empty($embeddings)
                ? array_sum(array_column($embeddings, 'quality')) / count($embeddings)
                : $faceProfile->enrollment_quality_score;

            $uniqueSources = array_unique($sources);
            $existingSources = $faceProfile->is_enrolled ? [$faceProfile->enrollment_source] : [];
            $allSources = array_unique(array_merge($existingSources, $uniqueSources));
            $sourceLabel = count($allSources) > 1 ? 'mixed' : ($allSources[0] ?? $method);

            $faceProfile->update([
                'is_enrolled' => $method !== 'webcam' ? ! empty($embeddings) : $faceProfile->is_enrolled,
                'enrolled_at' => now(),
                'enrollment_quality_score' => $avgQuality,
                'template_count' => $totalSlots,
                'enrollment_source' => $sourceLabel,
            ]);

            return $faceProfile;
        });
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Enrollment Complete')
            ->success();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    private function resizeForExtract(string $fullPath): ?string
    {
        if (! function_exists('imagecreatefromjpeg')) {
            return null;
        }

        $info = @getimagesize($fullPath);
        if (! $info) {
            return null;
        }

        [$origW, $origH, $type] = $info;
        $maxDim = 1024;

        if ($origW <= $maxDim && $origH <= $maxDim) {
            return null;
        }

        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($fullPath),
            IMAGETYPE_PNG => @imagecreatefrompng($fullPath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($fullPath),
            default => null,
        };

        if (! $src) {
            return null;
        }

        $ratio = min($maxDim / $origW, $maxDim / $origH);
        $newW = (int) round($origW * $ratio);
        $newH = (int) round($origH * $ratio);

        $dst = imagecreatetruecolor($newW, $newH);

        if ($type === IMAGETYPE_PNG) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($src);

        $tmpPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'fe_'.uniqid().'.jpg';
        imagejpeg($dst, $tmpPath, 92);
        imagedestroy($dst);

        return $tmpPath;
    }
}
