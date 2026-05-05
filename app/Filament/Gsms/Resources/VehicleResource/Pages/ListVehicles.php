<?php

namespace App\Filament\Gsms\Resources\VehicleResource\Pages;

use Filament\Forms;
use Filament\Actions;
use Filament\Forms\Get;
use App\Models\Division;
use App\Models\VehicleType;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Wizard\Step;
use App\Filament\Gsms\Resources\VehicleResource;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListVehicles extends ListRecords
{
    protected static string $resource = VehicleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('Create Vehicle Record')
            ->icon('heroicon-m-plus-circle')
            ->color('info')
            ->modalWidth('7xl')
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

        ];
    }
}
