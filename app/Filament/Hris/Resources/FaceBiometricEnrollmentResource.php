<?php

namespace App\Filament\Hris\Resources;

use App\Filament\Hris\Resources\FaceBiometricEnrollmentResource\Pages;
use App\Filament\Hris\Resources\FaceBiometricEnrollmentResource\Widgets\FaceBiometricEnrollmentStats;
use App\Models\FaceBiometrics\FaceAuditLog;
use App\Models\FaceBiometrics\FaceProfile;
use App\Services\FaceBiometricService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class FaceBiometricEnrollmentResource extends Resource
{
    protected static ?string $model = FaceProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Face Biometric Enrollment';

    protected static ?string $modelLabel = 'Face Biometric Enrollment';

    protected static ?string $pluralModelLabel = 'Face Biometric Enrollments';

    protected static ?string $navigationGroup = 'Attendance Management';

    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('profile_id')
                ->label('Employee')
                ->relationship('profile', 'employee_number')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->employee_number.' — '.$record->full_name)
                ->searchable()
                ->required()
                ->columnSpanFull(),

            Forms\Components\Toggle::make('is_enrolled')
                ->label('Enrolled')
                ->disabled(),

            Forms\Components\TextInput::make('template_count')
                ->label('Template Count')
                ->disabled(),

            Forms\Components\TextInput::make('enrollment_source')
                ->label('Enrollment Source')
                ->disabled(),

            Forms\Components\DateTimePicker::make('enrolled_at')
                ->label('Enrolled At')
                ->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('profile.employee_number')
                    ->label('Employee #')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('profile.full_name')
                    ->label('Employee Name')
                    ->searchable(['first_name', 'surname'])
                    ->sortable(),

                Tables\Columns\ImageColumn::make('profile.picture')
                    ->label('Source Photo')
                    ->disk('public')
                    ->height(56)
                    ->width(56)
                    ->extraImgAttributes(['class' => 'rounded-full object-cover']),

                Tables\Columns\IconColumn::make('is_enrolled')
                    ->label('Status')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('template_count')
                    ->label('Templates')
                    ->numeric()
                    ->alignCenter(),

                Tables\Columns\BadgeColumn::make('enrollment_source')
                    ->label('Source')
                    ->colors([
                        'primary' => 'profile_picture',
                        'success' => 'webcam',
                        'warning' => 'upload',
                        'gray' => 'mixed',
                    ]),

                Tables\Columns\TextColumn::make('enrollment_quality_score')
                    ->label('Quality')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) : '—')
                    ->color(fn ($state) => match (true) {
                        $state >= 0.75 => 'success',
                        $state >= 0.55 => 'warning',
                        default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('enrolled_at')
                    ->label('Enrolled At')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
            ])
            ->defaultSort('enrolled_at', 'desc')
            ->emptyStateHeading('No face profiles yet')
            ->emptyStateDescription('Use "Bulk Seed from Employee Pictures" or "New Enrollment" to get started.')
            ->emptyStateIcon('heroicon-o-sparkles')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_enrolled')
                    ->label('Enrollment Status')
                    ->trueLabel('Enrolled')
                    ->falseLabel('Not Enrolled'),

                Tables\Filters\SelectFilter::make('enrollment_source')
                    ->label('Source')
                    ->options([
                        'profile_picture' => 'Profile Picture',
                        'upload' => 'Upload',
                        'webcam' => 'Webcam',
                        'mixed' => 'Mixed',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('seedFromPicture')
                    ->label('Seed from Picture')
                    ->icon('heroicon-o-photo')
                    ->color('primary')
                    ->visible(fn (FaceProfile $record) => $record->profile?->picture
                        && Storage::disk('public')->exists($record->profile->picture))
                    ->requiresConfirmation()
                    ->modalHeading('Seed Enrollment from Profile Picture')
                    ->modalDescription('This will extract a face embedding from the employee\'s existing profile picture.')
                    ->action(function (FaceProfile $record) {
                        $profile = $record->profile;
                        $svc = app(FaceBiometricService::class);

                        try {
                            $absolutePath = Storage::disk('public')->path($profile->picture);
                            $result = $svc->extractFromPath($absolutePath, true);
                            $embedding = $result['embedding'];
                            $quality = (float) ($result['quality'] ?? 0.0);

                            \Illuminate\Support\Facades\DB::transaction(function () use ($record, $profile, $embedding, $quality) {
                                $record->update([
                                    'is_enrolled' => true,
                                    'enrolled_at' => now(),
                                    'enrollment_quality_score' => $quality,
                                    'template_count' => 1,
                                    'enrollment_source' => 'profile_picture',
                                ]);

                                \App\Models\FaceBiometrics\FaceEmbedding::insertVector(
                                    $record->id, 1, $embedding, $quality, 'profile_picture', $profile->picture
                                );

                                FaceAuditLog::create([
                                    'profile_id' => $profile->id,
                                    'event' => 'enroll_seed',
                                    'quality_score' => $quality,
                                    'source' => 'profile_picture',
                                    'reason' => 'single_seed_action',
                                    'created_at' => now(),
                                ]);
                            });

                            Notification::make()
                                ->title('Enrollment Seeded')
                                ->body("{$profile->full_name} enrolled from profile picture (quality: ".number_format($quality, 2).').')
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Seed Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('reEnroll')
                    ->label('Re-Enroll')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->modalHeading('Re-Enroll Employee')
                    ->form([
                        Forms\Components\FileUpload::make('new_image')
                            ->label('Upload New Photo')
                            ->image()
                            ->disk('public')
                            ->directory('face-biometric-images')
                            ->imagePreviewHeight('200')
                            ->maxSize(20480)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Or use the webcam kiosk link below'),

                        Forms\Components\Placeholder::make('webcam_link')
                            ->label('Live Webcam Enrollment')
                            ->content(fn (FaceProfile $record) => new \Illuminate\Support\HtmlString(
                                '<a href="'.route('face-biometrics.enroll', ['profile_id' => $record->profile_id])
                                .'" target="_blank" class="text-blue-600 underline">Open webcam enrollment kiosk →</a>'
                            )),
                    ])
                    ->action(function (FaceProfile $record, array $data) {
                        if (empty($data['new_image'])) {
                            Notification::make()->title('No image uploaded')->warning()->send();

                            return;
                        }

                        $svc = app(FaceBiometricService::class);

                        try {
                            $path = $data['new_image'];
                            $absPath = Storage::disk('public')->path($path);
                            $result = $svc->extractFromPath($absPath, true);
                            $embed = $result['embedding'];
                            $quality = (float) ($result['quality'] ?? 0.0);

                            $nextSlot = $record->embeddings()->max('slot') + 1;
                            if ($nextSlot > 5) {
                                Notification::make()->title('Max templates (5) reached')->warning()->send();

                                return;
                            }

                            $resized = static::resizeForExtractStatic($absPath);
                            $sendPath = $resized ?? $absPath;

                            \Illuminate\Support\Facades\DB::transaction(function () use ($record, $embed, $quality, $nextSlot, $path) {
                                \App\Models\FaceBiometrics\FaceEmbedding::insertVector(
                                    $record->id, $nextSlot, $embed, $quality, 'upload', $path
                                );

                                $record->update([
                                    'is_enrolled' => true,
                                    'template_count' => $nextSlot,
                                    'enrollment_source' => $record->template_count > 0 ? 'mixed' : 'upload',
                                ]);

                                FaceAuditLog::create([
                                    'profile_id' => $record->profile_id,
                                    'event' => 'enroll',
                                    'quality_score' => $quality,
                                    'source' => 'upload',
                                    'reason' => 're_enroll_action',
                                    'created_at' => now(),
                                ]);
                            });

                            if ($resized && file_exists($resized)) {
                                @unlink($resized);
                            }

                            Notification::make()
                                ->title('Re-enrolled')
                                ->body("Slot {$nextSlot} added. Quality: ".number_format($quality, 2))
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Re-enrollment Failed')->body($e->getMessage())->danger()->send();
                        }
                    }),

                Tables\Actions\Action::make('removeEnrollment')
                    ->label('Remove Enrollment')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove Face Enrollment')
                    ->modalDescription('This will delete all face embeddings and reset the enrollment status. This cannot be undone.')
                    ->action(function (FaceProfile $record) {
                        \Illuminate\Support\Facades\DB::transaction(function () use ($record) {
                            $record->embeddings()->delete();
                            $record->update([
                                'is_enrolled' => false,
                                'template_count' => 0,
                                'enrollment_source' => null,
                                'enrollment_quality_score' => null,
                                'enrolled_at' => null,
                            ]);

                            FaceAuditLog::create([
                                'profile_id' => $record->profile_id,
                                'event' => 'enroll',
                                'reason' => 'enrollment_deleted',
                                'source' => 'admin',
                                'created_at' => now(),
                            ]);
                        });

                        Notification::make()->title('Enrollment Removed')->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            FaceBiometricEnrollmentStats::class,
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaceBiometricEnrollments::route('/'),
            'create' => Pages\CreateFaceBiometricEnrollment::route('/create'),
            'edit' => Pages\EditFaceBiometricEnrollment::route('/{record}/edit'),
        ];
    }

    public static function resizeForExtractStatic(string $fullPath): ?string
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
