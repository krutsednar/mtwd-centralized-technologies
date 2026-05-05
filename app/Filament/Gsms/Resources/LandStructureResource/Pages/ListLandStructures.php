<?php

namespace App\Filament\Gsms\Resources\LandStructureResource\Pages;

use Filament\Forms;
use Filament\Actions;
use Filament\Forms\Form;
use App\Models\LandStructure;
use App\Models\LandStructureType;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\Wizard\Step;
use App\Filament\Gsms\Resources\LandStructureResource;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ListLandStructures extends ListRecords
{
    protected static string $resource = LandStructureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
            ->label('Create Land/Structure Record')
            ->icon('heroicon-m-plus-circle')
            ->color('info')
            ->modalWidth('7xl')
            ->closeModalByClickingAway(false)
            ->model(LandStructure::class)
            ->steps([
                    Step::make('Lot/Structure Information')
                        ->columns([
                            'sm' => 2,
                            'xl' => 2,
                            '2xl' => 2,
                        ])
                        ->schema([
                        Forms\Components\Select::make('land_structure_type_id')
                            ->options(LandStructureType::pluck('name', 'id'))
                            ->label('Type')
                            ->required(),
                        Forms\Components\TextInput::make('property_name')
                            ->maxLength(255)
                            ->required(),
                        Forms\Components\TextInput::make('lot_area')
                            ->suffix('sqm')
                            ->numeric()
                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\DatePicker::make('date_acquired')
                            ->displayFormat('m/d/Y')
                            ->native(false),
                        Forms\Components\DatePicker::make('date_established')
                            ->label('Date Constructed')
                            ->displayFormat('m/d/Y')
                            ->native(false),
                        Forms\Components\TextInput::make('address')
                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\TextInput::make('title_no')
                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\Select::make('status')
                            ->options([
                                'Active' => 'Active',
                                'Inactive' => 'Inactive',
                            ])
                            ->default(null),
                        Forms\Components\FileUpload::make('title_file')
                            ->label('Upload Title')
                            ->directory('land_titles')
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                                    ->prepend(''),
                            ),
                        Forms\Components\FileUpload::make('photo')
                            ->label('Upload Photo')
                            ->directory('structure_photos')
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                                    ->prepend(''),
                            ),

                    ]),
                    Step::make('Real Property Tax')
                        ->schema([
                            Repeater::make('realPropertyTaxes')
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
                                    ->default(null)
                                    ->columnSpan([
                                        'sm' => 2,
                                        'xl' => 2,
                                        '2xl' => 2,
                                    ]),
                                Forms\Components\DatePicker::make('date_issued')
                                    ->displayFormat('m/d/Y')
                                    ->native(false)
                                    ->columnSpan([
                                        'sm' => 2,
                                        'xl' => 2,
                                        '2xl' => 2,
                                    ]),
                                Forms\Components\FileUpload::make('attachment')
                                    ->label('Upload Photo')
                                    ->directory('rpt_files')
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
                    Step::make('Tax Declaration')
                        ->schema([
                            Repeater::make('taxDeclarations')
                            ->relationship()
                            ->columns([
                                'sm' => 4,
                                'xl' => 4,
                                '2xl' => 4,
                            ])
                            ->schema([
                            Forms\Components\TextInput::make('tax_dec_no')
                                ->maxLength(255)
                                ->default(null)
                                ->columnSpan([
                                        'sm' => 2,
                                        'xl' => 2,
                                        '2xl' => 2,
                                    ]),
                            Forms\Components\DatePicker::make('date_issued')
                                ->displayFormat('m/d/Y')
                                ->native(false)
                                ->columnSpan([
                                        'sm' => 2,
                                        'xl' => 2,
                                        '2xl' => 2,
                                    ]),
                            Forms\Components\FileUpload::make('attachment')
                                ->label('Upload Photo')
                                ->directory('tax_dec_files')
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

                    Step::make('Insurance Policy')
                        ->schema([
                            Repeater::make('structureInsurances')
                            ->relationship()
                            ->columns([
                                'sm' => 4,
                                'xl' => 6,
                                '2xl' => 6,
                            ])
                            ->schema([
                                Forms\Components\TextInput::make('policy_no')
                                    ->maxLength(255)
                                    ->default(null)
                                    ->columnSpan([
                                        'sm' => 2,
                                        'xl' => 2,
                                        '2xl' => 2,
                                    ]),
                                Forms\Components\DatePicker::make('date_issued')
                                ->columnSpan([
                                        'sm' => 2,
                                        'xl' => 2,
                                        '2xl' => 2,
                                    ])
                                ->displayFormat('m/d/Y')
                                ->native(false),
                                Forms\Components\DatePicker::make('expiration')
                                ->columnSpan([
                                        'sm' => 2,
                                        'xl' => 2,
                                        '2xl' => 2,
                                    ])
                                ->displayFormat('m/d/Y')
                                ->native(false),
                                Forms\Components\FileUpload::make('attachment')
                                ->label('Upload Photo')
                                ->directory('structure_insurance_files')
                                ->getUploadedFileNameForStorageUsing(
                                    fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                                        ->prepend(''),
                                )
                                ->columnSpan([
                                        'sm' => 2,
                                        'xl' => 3,
                                        '2xl' => 3,
                                ]),
                            ]),

                        ]),
                ])
                ->skippableSteps(),
        ];
    }
}
