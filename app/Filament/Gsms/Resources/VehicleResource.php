<?php

namespace App\Filament\Gsms\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Vehicle;
use Filament\Forms\Get;
use App\Models\Division;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\VehicleType;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use App\Livewire\Gsms\VehicleInsurace;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Facades\Storage;
use Filament\Infolists\Components\Split;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Wizard\Step;
use Filament\Infolists\Components\Section;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Livewire\Gsms\VehicleOfficialReceipts;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use App\Filament\Gsms\Resources\VehicleResource\Pages;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Filament\Gsms\Resources\VehicleResource\RelationManagers;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static ?string $navigationIcon = 'fas-car-side';

    protected static ?string $navigationGroup = 'Transport and Equipment';

    protected static ?string $navigationLabel = 'Vehicles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('vehicle_type_id')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('brand')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('model')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('serial_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('certificate_of_registration')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('cr_file')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('chasis_no')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('chasis_file')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('engine_no')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('plate_no')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\DatePicker::make('date_acquired'),
                Forms\Components\TextInput::make('par_no')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('custodian')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('division_id')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('value')
                    ->label('Acquisition Cost')
                    ->numeric()
                    ->default(null),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('remarks')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('status')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make('table')->fromTable()->withFilename('Vehicle Export - '.date('F d, Y')),
                ])->color('success'),

            ])
            ->columns([
                Tables\Columns\TextColumn::make('vehicleType.name')
                    ->label('Vehicle Type')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('brand')
                    ->searchable(),
                Tables\Columns\TextColumn::make('model')
                    ->searchable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->label('Serial No.')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_acquired')
                    ->label('Date Acquired')
                    ->date('F d, Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('aging_display')
                    ->label('Aging')
                    ->getStateUsing(fn ($record) => $record->date_acquired)
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';

                        $dateAcquired = Carbon::parse($state);
                        $now = Carbon::now();
                        $diff = $dateAcquired->diff($now);

                        return "{$diff->y} yrs, {$diff->m} mos, {$diff->d} days";
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Acquisition Cost')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('engine_no')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plate_no')
                    ->searchable(),

                Tables\Columns\TextColumn::make('par_no')
                    ->label('PAR No.')
                    ->searchable(),
                Tables\Columns\TextColumn::make('certificate_of_registration')
                    ->label('CR No.')
                    ->searchable(),
                Tables\Columns\IconColumn::make('cr_file')
                    ->label('CR File')
                    ->trueIcon('fas-file-alt')
                    ->color('info')
                    ->action(
                        \Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction::make('cr_file')
                            ->iconButton()
                            ->media(fn($record) => Storage::url($record->cr_file)),
                    )
                    ->alignCenter()
                    ->tooltip('View Certificate of Registration')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('chasis_no')
                    ->searchable(),
                Tables\Columns\IconColumn::make('chasis_file')
                    ->label('Stencil')
                    ->trueIcon('fas-file-alt')
                    ->color('info')
                    ->action(
                        \Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction::make('chasis_file')
                            ->iconButton()
                            ->media(fn($record) => Storage::url($record->chasis_file)),
                    )
                    ->alignCenter()
                    ->tooltip('View Stencil')
                    ->toggleable(),


                Tables\Columns\TextColumn::make('policy_no')
                    ->label('Policy No.')
                    ->getStateUsing(function ($record) {
                        return optional($record->insurancePolicies()->latest()->first())->policy_no ?? '-';
                    })
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('policy_file')
                    ->label('Policy File')
                    ->trueIcon('fas-file-alt')
                    ->color('info')
                     ->getStateUsing(fn($record) =>
                        !empty($record->insurancePolicies()->latest()->first()?->policy_file)
                    )
                    ->action(
                        \Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction::make('policy_file')
                            ->iconButton()
                            ->media(fn($record) =>
                                optional($record->insurancePolicies()->latest()->first())
                                    ? Storage::url($record->insurancePolicies()->latest()->first()->policy_file)
                                    : null
                            ),
                    )
                    ->alignCenter()
                    ->tooltip('View Insurance Policy')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('or_no')
                    ->label('OR No.')
                    ->getStateUsing(function ($record) {
                        return optional($record->officialReceipts()->latest()->first())->or_no ?? '-';
                    })
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('or_file')
                    ->label('OR File')
                    ->trueIcon('fas-file-alt')
                    ->color('info')
                     ->getStateUsing(fn($record) =>
                        !empty($record->officialReceipts()->latest()->first()?->or_file)
                    )
                    ->action(
                        \Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction::make('or_file')
                            ->iconButton()
                            ->media(fn($record) =>
                                optional($record->officialReceipts()->latest()->first())
                                    ? Storage::url($record->officialReceipts()->latest()->first()->or_file)
                                    : null
                            ),
                    )
                    ->alignCenter()
                    ->tooltip('View OR File')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('division.abbreviation')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('profile.full_name')
                    ->label('Custodian')
                    ->searchable(['surname', 'first_name', 'middle_name'])
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Good Running Condition' => 'success',
                        'Running Condition' => 'warning',
                        'Unserviceable' => 'danger',
                        default => 'gray', // 👈 fallback color
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('details')
                    ->label('')
                    ->tooltip('View Record')
                    // ->color('info')
                    ->icon('fas-eye')
                    ->size('xl')
                    ->slideOver()
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->infolist([
                        Section::make('Heavy Equipment Details')
                        ->schema([
                            TextEntry::make('vehicleType.name'),
                            TextEntry::make('brand'),
                            TextEntry::make('model'),
                            TextEntry::make('serial_number'),
                            TextEntry::make('date_acquired')
                                ->date('F d, Y'),
                            TextEntry::make('value')
                                ->label('Acquisition Cost')
                                ->money('PHP'),
                            TextEntry::make('certificate_of_registration'),
                            TextEntry::make('chasis_no'),
                            TextEntry::make('engine_no'),
                            TextEntry::make('plate_no'),
                            TextEntry::make('par_no'),
                            TextEntry::make('division.name')
                                ->label('Division'),
                            TextEntry::make('profile.full_name')
                                ->label('Custodian'),
                            IconEntry::make('cr_file')
                                ->label('CR File')
                                ->tooltip('View file')
                                ->icon('fas-file-alt') // or any FontAwesome icon
                                ->url(fn($record) => $record->cr_file ? Storage::url($record->cr_file) : null)
                                ->openUrlInNewTab()
                                ->color('info') // optional: blue color
                                ->visible(fn($record) => !empty($record->cr_file)),
                            IconEntry::make('chasis_file')
                                ->label('Stencil File')
                                ->tooltip('View file')
                                ->icon('fas-file-alt') // or any FontAwesome icon
                                ->url(fn($record) => $record->chasis_file ? Storage::url($record->chasis_file) : null)
                                ->openUrlInNewTab()
                                ->color('info') // optional: blue color
                                ->visible(fn($record) => !empty($record->chasis_file)),
                            TextEntry::make('aging')
                                ->label('Aging')
                                ->getStateUsing(fn ($record) => $record->date_acquired)
                                ->formatStateUsing(function ($state) {
                                    if (!$state) return '-';

                                    $dateAcquired = Carbon::parse($state);
                                    $now = Carbon::now();
                                    $diff = $dateAcquired->diff($now);

                                    return "{$diff->y} yrs, {$diff->m} mos, {$diff->d} days";
                                }),
                            TextEntry::make('status')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'Good Running Condition' => 'success',
                                    'Running Condition' => 'warning',
                                    'Unserviceable' => 'danger',
                                    default => 'gray', // 👈 fallback color
                                }),
                        ])->columns(6),
                            Split::make([
                                Section::make('Official Receipts')
                                ->schema([
                                        Livewire::make(VehicleOfficialReceipts::class)
                                ]),
                                Section::make('Insurance Policies')
                                ->schema([
                                        Livewire::make(VehicleInsurace::class)
                                ]),
                            ])
                        ]),
                Tables\Actions\EditAction::make()
                ->modalWidth('7xl')
                ->size('xl')
                ->label('')
                ->tooltip('Edit Record')
                ->closeModalByClickingAway(false)
                ->steps([
                    Step::make('Vehicle Information')
                        ->columns([
                            'sm' => 3,
                            'xl' => 3,
                            '2xl' => 3,
                        ])
                        ->schema([
                            Forms\Components\Select::make('vehicle_type_id')
                                ->label('Vehicle Type')
                                ->options(VehicleType::pluck('name', 'id'))
                                ->default(null),
                            Forms\Components\TextInput::make('brand')
                                ->maxLength(255)
                                ->default(null),
                            Forms\Components\TextInput::make('model')
                                ->maxLength(255)
                                ->default(null),
                            Forms\Components\TextInput::make('value')
                                ->label('Acquisition Cost')
                                ->numeric()
                                ->prefix('₱ ')
                                ->default(null),
                            Forms\Components\TextInput::make('serial_number')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('engine_no')
                                ->maxLength(255)
                                ->default(null),
                            Forms\Components\TextInput::make('certificate_of_registration')
                                ->maxLength(255)
                                ->default(null),
                            Forms\Components\TextInput::make('chasis_no')
                                ->maxLength(255)
                                ->default(null),
                            Forms\Components\TextInput::make('plate_no')
                                ->maxLength(255)
                                ->default(null),
                            // Forms\Components\TextInput::make('cr_file')
                            //     ->maxLength(255)
                            //     ->default(null),
                            Forms\Components\FileUpload::make('cr_file')
                            ->label('Upload Certificate of Registration')
                            ->directory('cr_files')
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                                    ->prepend(''),
                            ),

                            // Forms\Components\TextInput::make('chasis_file')
                            //     ->maxLength(255)
                            //     ->default(null),
                            Forms\Components\FileUpload::make('chasis_file')
                            ->label('Upload Stencil')
                            ->directory('chasis_files')
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                                    ->prepend(''),
                            ),

                            Forms\Components\DatePicker::make('date_acquired')
                                ->native(false)
                                ->displayFormat('F d, Y'),
                            Forms\Components\TextInput::make('par_no')
                                ->maxLength(255)
                                ->default(null),
                            Forms\Components\Select::make('division_id')
                                ->label('Division')
                                ->options(Division::pluck('name', 'id'))
                                ->searchable()
                                ->reactive()
                                ->live()
                                ->required(),
                            Forms\Components\Select::make('custodian')
                                ->options(fn (Get $get) => \App\Models\Profile::where('division_id', $get('division_id'))
                                    ->get()
                                    ->pluck('full_name', 'id'))
                                ->label('Custodian')
                                ->searchable(),
                            Forms\Components\Select::make('status')
                                ->options([
                                    'Good Running Condition' => 'Good Running Condition',
                                    'Running Condition' => 'Running Condition',
                                    'Unserviceable' => 'Unserviceable',
                                ]),
                            Forms\Components\Textarea::make('description')
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('remarks')
                                ->columnSpanFull(),

                        ]),
                    Step::make('Official Receipts')
                        ->schema([
                            Repeater::make('officialReceipts')
                            ->relationship()
                            // Changed this from `->columns(2)` to `->columns(3)`
                            ->columns([
                                'sm' => 4,
                                'xl' => 4,
                                '2xl' => 4,
                            ])
                            ->schema([
                                Forms\Components\TextInput::make('or_no')
                                    ->maxLength(255)
                                    ->default(null),
                                Forms\Components\DatePicker::make('or_expiration')
                                    ->native(false)
                                    ->displayFormat('F d, Y'),
                                Forms\Components\FileUpload::make('or_file')
                                    ->label('Upload Official Receipt')
                                    ->directory('or_files')
                                    ->getUploadedFileNameForStorageUsing(
                                        fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                                            ->prepend(''),
                                    )
                                    ->columnSpan([
                                        'sm' => 2,
                                        'xl' => 2,
                                        '2xl' => 2,
                                    ]),

                            ])
                            ->columns(1)
                        ]),
                    Step::make('Insurance Policies')
                        ->schema([
                            Repeater::make('insurancePolicies')
                            ->relationship()
                            ->columns([
                                'sm' => 4,
                                'xl' => 4,
                                '2xl' => 4,
                            ])
                            ->schema([
                                Forms\Components\TextInput::make('policy_no')
                                    ->maxLength(255)
                                    ->default(null),
                                Forms\Components\DatePicker::make('policy_expiration')
                                    ->displayFormat('F d, Y')
                                    ->native(false),
                                Forms\Components\FileUpload::make('policy_file')
                                    ->label('Upload Insurance Policy')
                                    ->directory('policy_files')
                                    ->getUploadedFileNameForStorageUsing(
                                        fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                                            ->prepend(''),
                                    )
                                    ->columnSpan([
                                        'sm' => 2,
                                        'xl' => 2,
                                        '2xl' => 2,
                                    ]),
                            ]),

                        ]),
                ])
                ->skippableSteps(),
            ], position: ActionsPosition::BeforeColumns)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            // 'create' => Pages\CreateVehicle::route('/create'),
            // 'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
