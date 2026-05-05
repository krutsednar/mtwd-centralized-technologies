<?php

namespace App\Filament\Gsms\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\LandStructure;
use PhpParser\Node\Stmt\Label;
use App\Livewire\Gsms\RptTable;
use Filament\Resources\Resource;
use App\Models\LandStructureType;
use App\Livewire\Gsms\TaxDecTable;
use Filament\Tables\Actions\Action;
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
use App\Livewire\Gsms\StructureInsuranceTable;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction;
use App\Filament\Gsms\Resources\LandStructureResource\Pages;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Filament\Gsms\Resources\LandStructureResource\RelationManagers;

class LandStructureResource extends Resource
{
    protected static ?string $model = LandStructure::class;

    protected static ?string $navigationIcon = 'fas-building-flag';

    // protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Land and Structure Management';

    protected static ?string $navigationLabel = 'Lots and Facilities';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('land_structure_type_id')
                    ->options(LandStructureType::pluck('name', 'id'))
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

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make('table')->fromTable()->withFilename('Lot and Structure Export - '.date('F d, Y')),
                ])->color('success'),

            ])
            ->query(LandStructure::orderBy('id', 'desc'))
            ->columns([
                Tables\Columns\TextColumn::make('landStructureType.name')
                    ->searchable()
                    ->label('Type')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('property_name')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('lot_area')
                    ->label('Lot Area (sqm)')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('date_acquired')
                    ->date('F d, Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('aging_display')
                    ->label('Acquisition Age')
                    ->getStateUsing(fn ($record) => $record->date_acquired)
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';

                        $dateAcquired = Carbon::parse($state);
                        $now = Carbon::now();
                        $diff = $dateAcquired->diff($now);

                        return "{$diff->y} yrs, {$diff->m} mos, {$diff->d} days";
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('date_established')
                    ->label('Date Constructed')
                    ->date('F d, Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('infra_aging')
                    ->label('Construction Age')
                    ->getStateUsing(fn ($record) => $record->date_established)
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';

                        $dateAcquired = Carbon::parse($state);
                        $now = Carbon::now();
                        $diff = $dateAcquired->diff($now);

                        return "{$diff->y} yrs, {$diff->m} mos, {$diff->d} days";
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('title_no')
                    ->searchable()
                    ->toggleable(),
                // Tables\Columns\TextColumn::make('title_file')
                //     ->searchable(),
                Tables\Columns\IconColumn::make('title_file')
                    ->label('Title Attachment')
                    ->trueIcon('fas-file-alt')
                    ->color('info')
                    ->getStateUsing(fn($record) =>
                        !empty($record->title_file)
                    )
                    ->action(
                        \Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction::make('title_file')
                            ->iconButton()
                            ->media(fn($record) => Storage::url($record->title_file)),
                    )
                    ->alignCenter()
                    ->tooltip('View Title'),
                Tables\Columns\IconColumn::make('photo')
                    ->label('Photo')
                    ->trueIcon('tabler-photo-f')
                    ->color('info')
                    ->getStateUsing(fn($record) =>
                        !empty($record->photo)
                    )
                    ->action(
                        \Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction::make('photo')
                            ->iconButton()
                            ->media(fn($record) => Storage::url($record->photo)),
                    )
                    ->alignCenter()
                    ->tooltip('View Photo'),
                Tables\Columns\TextColumn::make('or_no')
                    ->label('RPT No.')
                    ->getStateUsing(function ($record) {
                        return optional($record->realPropertyTaxes()->latest()->first())->or_no ?? '-';
                    })
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('rpt_date_issued')
                    ->label('RPT Date Issued')
                    ->date('F d, Y')
                    ->getStateUsing(function ($record) {
                        return $record->realPropertyTaxes()->latest()->first()->date_issued ?? null;
                    })
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('rtp_attachment')
                    ->label('RPT File')
                    ->trueIcon('fas-file-alt')
                    ->color('info')
                    ->getStateUsing(fn($record) =>
                        !empty($record->realPropertyTaxes()->latest()->first()?->attachment)
                    )
                    ->action(
                        \Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction::make('attachment')
                            ->iconButton()
                            ->media(fn($record) => Storage::url($record->realPropertyTaxes()->latest()->first()->attachment)
                            ),
                    )
                    ->alignCenter()
                    ->tooltip('View RPT File')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tax_dec')
                    ->label('Tax Dec. No.')
                    ->getStateUsing(function ($record) {
                        return optional($record->taxDeclarations()->latest()->first())->tax_dec_no ?? '-';
                    })
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tax_dec_date')
                    ->label('Tax Dec. Date Issued')
                    ->date('F d, Y')
                    ->getStateUsing(function ($record) {
                        return $record->taxDeclarations()->latest()->first()->date_issued ?? null;
                    })
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('tax_dec_file')
                    ->label('Tax Dec. File')
                    ->trueIcon('fas-file-alt')
                    ->color('info')
                    ->getStateUsing(fn($record) =>
                        !empty($record->taxDeclarations()->latest()->first()?->attachment)
                    )
                    ->action(
                        \Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction::make('attachment')
                            ->iconButton()
                            ->media(fn($record) => Storage::url($record->taxDeclarations()->latest()->first()->attachment)
                            ),
                    )
                    ->alignCenter()
                    ->tooltip('View Tax Dec. File')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->sortable()
                    ->toggleable()
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Inactive' => 'danger',
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
                    ->icon('fas-eye')
                    ->size('xl')
                    ->slideOver()
                    ->modalWidth('8xl')
                    ->modalSubmitAction(false)
                    ->infolist([
                        Section::make('Heavy Equipment Details')
                        ->schema([
                            TextEntry::make('land_structure_type.name'),
                            TextEntry::make('property_name'),
                            TextEntry::make('lot_area')
                                ->suffix('sqm'),
                            TextEntry::make('date_acquired')
                                ->date('F d, Y'),
                            TextEntry::make('date_established')
                                ->label('Date Constructed')
                                ->date('F d, Y'),

                            TextEntry::make('title_no'),
                            TextEntry::make('address'),
                            IconEntry::make('title_file')
                                ->label('Title File')
                                ->tooltip('View Title')
                                ->icon('fas-file-alt') // or any FontAwesome icon
                                ->url(fn($record) => $record->title_file ? Storage::url($record->title_file) : null)
                                ->openUrlInNewTab()
                                ->color('info') // optional: blue color
                                ->visible(fn($record) => !empty($record->title_file)),
                            IconEntry::make('photo')
                                ->label('Site Photo')
                                ->tooltip('View Site Photo')
                                ->icon('fas-file-alt') // or any FontAwesome icon
                                ->url(fn($record) => $record->photo ? Storage::url($record->photo) : null)
                                ->openUrlInNewTab()
                                ->color('info') // optional: blue color
                                ->visible(fn($record) => !empty($record->photo)),
                            TextEntry::make('lot_aging')
                                ->label('Lot Aging')
                                ->getStateUsing(fn ($record) => $record->date_acquired)
                                ->formatStateUsing(function ($state) {
                                    if (!$state) return '-';

                                    $dateAcquired = Carbon::parse($state);
                                    $now = Carbon::now();
                                    $diff = $dateAcquired->diff($now);

                                    return "{$diff->y} yrs, {$diff->m} mos, {$diff->d} days";
                                }),
                            TextEntry::make('structure_aging')
                                ->label('Structure Aging')
                                ->getStateUsing(fn ($record) => $record->date_established)
                                ->formatStateUsing(function ($state) {
                                    if (!$state) return '-';

                                    $dateEstablished = Carbon::parse($state);
                                    $now = Carbon::now();
                                    $diff = $dateEstablished->diff($now);

                                    return "{$diff->y} yrs, {$diff->m} mos, {$diff->d} days";
                                }),
                            TextEntry::make('status')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'Active' => 'success',
                                    'Inactive' => 'danger',
                                    default => 'gray', // 👈 fallback color
                                }),
                        ])->columns(3),
                            Split::make([
                                Section::make('Tax Declarations')
                                ->schema([
                                        Livewire::make(TaxDecTable::class)
                                ]),
                                Section::make('Real Property Taxes')
                                ->schema([
                                        Livewire::make(RptTable::class)
                                ]),
                                Section::make('Insurance Policies')
                                ->schema([
                                        Livewire::make(StructureInsuranceTable::class)
                                ]),
                            ])
                        ]),
                Tables\Actions\EditAction::make()
                ->modalWidth('7xl')
                ->size('xl')
                ->label('')
                ->closeModalByClickingAway(false)
                ->steps([
                        Step::make('Basic Information')
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
            'index' => Pages\ListLandStructures::route('/'),
            // 'create' => Pages\CreateLandStructure::route('/create'),
            // 'edit' => Pages\EditLandStructure::route('/{record}/edit'),
        ];
    }
}
