<?php

namespace App\Filament\Hris\Resources;

use App\Filament\Hris\Resources\BiometricEnrollmentResource\Pages;
use App\Models\BiometricEnrollment;
use App\Models\Profile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;

class BiometricEnrollmentResource extends Resource
{
    protected static ?string $model = BiometricEnrollment::class;

    protected static ?string $navigationIcon = 'heroicon-o-finger-print';

    protected static ?string $navigationLabel = 'Biometric Enrollment';

    protected static ?string $modelLabel = 'Biometric Enrollment';

    protected static ?string $pluralModelLabel = 'Biometric Enrollments';

    protected static ?string $navigationGroup = 'Attendance Management';

    protected static ?int $navigationSort = 20;

    // -------------------------------------------------------------------------
    // form() is used by the Edit page only.
    // The Create page overrides this with a two-step Wizard via HasWizard.
    // -------------------------------------------------------------------------
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('profile_id')
                ->label('Employee')
                ->options(
                    Profile::query()
                        ->get()
                        ->mapWithKeys(fn (Profile $p) => [$p->id => $p->employee_number.' — '.$p->full_name])
                )
                ->searchable()
                ->required()
                ->columnSpanFull(),

            Forms\Components\Grid::make(3)->schema([
                Forms\Components\FileUpload::make('image_1')
                    ->label('Image 1')
                    ->image()
                    ->required()
                    ->disk('public')
                    ->directory('biometric-images')
                    ->imagePreviewHeight('200')
                    ->maxSize(20480),

                Forms\Components\FileUpload::make('image_2')
                    ->label('Image 2')
                    ->image()
                    ->required()
                    ->disk('public')
                    ->directory('biometric-images')
                    ->imagePreviewHeight('200')
                    ->maxSize(20480),

                Forms\Components\FileUpload::make('image_3')
                    ->label('Image 3')
                    ->image()
                    ->required()
                    ->disk('public')
                    ->directory('biometric-images')
                    ->imagePreviewHeight('200')
                    ->maxSize(20480),
            ]),
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

                Tables\Columns\ImageColumn::make('image_1')
                    ->label('Image 1')
                    ->disk('public')
                    ->height(56)
                    ->width(56)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover']),

                Tables\Columns\ImageColumn::make('image_2')
                    ->label('Image 2')
                    ->disk('public')
                    ->height(56)
                    ->width(56)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover']),

                Tables\Columns\ImageColumn::make('image_3')
                    ->label('Image 3')
                    ->disk('public')
                    ->height(56)
                    ->width(56)
                    ->extraImgAttributes(['class' => 'rounded-lg object-cover']),

                Tables\Columns\IconColumn::make('profile.face_enrolled')
                    ->label('CompreFace')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('enrolled_at')
                    ->label('Enrolled At')
                    ->dateTime('M d, Y h:i A')
                    ->sortable(),
            ])
            ->defaultSort('enrolled_at', 'desc')
            ->emptyStateHeading('No enrollments yet')
            ->emptyStateDescription('Click "New Biometric Enrollment" to enroll an employee\'s face.')
            ->emptyStateIcon('heroicon-o-finger-print')
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label('Remove')
                    ->modalHeading('Remove Biometric Enrollment')
                    ->modalDescription('This will delete the enrollment record and remove all associated faces from CompreFace. This cannot be undone.')
                    ->before(function (BiometricEnrollment $record): void {
                        $profile = $record->profile;
                        $compreFaceUrl = config('services.compreface.url');
                        $compreFaceKey = config('services.compreface.key');

                        if (! $compreFaceUrl || ! $compreFaceKey || ! $profile) {
                            return;
                        }

                        // Delete specific faces by stored face IDs, if available
                        $faceIds = $record->compreface_face_ids ?? [];

                        if (! empty($faceIds)) {
                            foreach ($faceIds as $faceId) {
                                Http::timeout(10)
                                    ->withHeaders(['x-api-key' => $compreFaceKey])
                                    ->delete("{$compreFaceUrl}/api/v1/recognition/faces/{$faceId}");
                            }
                        } else {
                            // Fallback: delete all faces of the subject
                            Http::timeout(10)
                                ->withHeaders(['x-api-key' => $compreFaceKey])
                                ->delete("{$compreFaceUrl}/api/v1/recognition/faces?subject={$profile->employee_number}");
                        }

                        // Un-flag profile only if this is their only enrollment record
                        $remaining = BiometricEnrollment::where('profile_id', $profile->id)
                            ->where('id', '!=', $record->id)
                            ->count();

                        if ($remaining === 0) {
                            $profile->update(['face_enrolled' => false]);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBiometricEnrollments::route('/'),
            'create' => Pages\CreateBiometricEnrollment::route('/create'),
        ];
    }
}
