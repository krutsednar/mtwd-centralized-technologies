<?php

namespace App\Filament\Gsms\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Driver;
use App\Models\Profile;
use App\Models\Vehicle;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\ActionsPosition;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Gsms\Resources\DriverResource\Pages;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Filament\Gsms\Resources\DriverResource\RelationManagers;

class DriverResource extends Resource
{
    protected static ?string $model = Driver::class;

    protected static ?string $navigationIcon = 'fas-user-cog';

    protected static ?string $navigationGroup = 'Transport and Equipment';

    protected static ?string $navigationLabel = 'Drivers';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([
                Forms\Components\Select::make('profile_id')
                    ->label('Driver\'s Name')
                    ->options(Profile::get()->pluck('full_name', 'id'))
                    ->searchable()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('license_no')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Select::make('type')
                    ->searchable()
                    ->options([
                        'Professional' => 'Professional',
                        'Non-Professional' => 'Non-Professional',
                        'Student Permit' => 'Student Permit',
                    ]),
                Forms\Components\TextInput::make('restrictions')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\DatePicker::make('expiration')
                    ->native(false)
                    ->displayFormat('F d, Y'),
                Forms\Components\DatePicker::make('date_approved')
                    ->label('Memo Date Approved')
                    ->native(false)
                    ->displayFormat('F d, Y'),
                Forms\Components\Select::make('primary_vehicle')
                    ->label('Primary Vehicle')
                    ->searchable()
                    ->options(
                        Vehicle::all()
                        ->sortBy(fn ($v) => $v->vehicleName())
                        ->mapWithKeys(fn ($v) => [$v->id => $v->vehicleName()])
                        ->toArray()
                    ),
                Forms\Components\Select::make('vehicles')
                    ->label('Alternate Vehicles')
                    ->relationship()
                    ->multiple()
                    ->options(
                        Vehicle::all()
                        ->sortBy(fn ($v) => $v->vehicleName())
                        ->mapWithKeys(fn ($v) => [$v->id => $v->vehicleName()])
                        ->toArray()
                    )
                    ->searchable()
                    ->columnSpan(2),
                Forms\Components\FileUpload::make('dl_file')
                            ->label('Upload Dirver\'s Lisence')
                            ->directory('dl_files')
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                                    ->prepend(''),
                            ),
                Forms\Components\FileUpload::make('som_file')
                            ->label('Upload Statement of Undertaking')
                            ->directory('som_files')
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => (string) str($file->getClientOriginalName())
                                    ->prepend(''),
                            ),
                Forms\Components\FileUpload::make('memo_file')
                            ->label('Upload Memorandum')
                            ->directory('memo_files')
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
                    ExcelExport::make('table')->fromTable()->withFilename('Drivers Export - '.date('F d, Y')),
                ])->color('success'),

            ])
            ->columns([
                Tables\Columns\TextColumn::make('profile.full_name')
                    ->label('Driver\'s Name')
                    ->searchable(['first_name', 'middle_name', 'surname'])
                    ->toggleable(),
                Tables\Columns\TextColumn::make('profile.division.abbreviation')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('license_no')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('restrictions')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('expiration')
                    ->date('F d, Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('date_approved')
                    ->label('Memo Date Approved')
                    ->date('F d, Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('primaryVehicle.model')
                    ->label('Primary Vehicle')
                    ->searchable(['brand', 'model', 'plate_no'])
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(function (?string $state, Driver $record): string {
                        return optional($record->primaryVehicle)?->vehicleName() ?? '-';
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('vehicles.model')
                    ->label('Assigned Vehicles')
                    ->getStateUsing(fn ($record) =>
                        $record->vehicles
                            ->unique('id')
                            ->map(fn ($v) => $v->vehicleName())
                            ->toArray()
                    )
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('dl_file')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('som_file')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('memo_file')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('dl_file')
                    ->label('Driver\'s Lisence')
                    ->trueIcon('fas-file-alt')
                    ->color('info')
                    ->action(
                        \Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction::make('dl_file')
                            ->iconButton()
                            ->media(fn($record) => Storage::url($record->dl_file)),
                    )
                    ->alignCenter()
                    ->tooltip('View Driver\'s Lisence')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('som_file')
                    ->label('SOU')
                    ->trueIcon('fas-file-alt')
                    ->color('info')
                    ->action(
                        \Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction::make('som_file')
                            ->iconButton()
                            ->media(fn($record) => Storage::url($record->som_file)),
                    )
                    ->alignCenter()
                    ->tooltip('View SOU')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('memo_file')
                    ->label('Memo')
                    ->trueIcon('fas-file-alt')
                    ->color('info')
                    ->action(
                        \Hugomyb\FilamentMediaAction\Tables\Actions\MediaAction::make('memo_file')
                            ->iconButton()
                            ->media(fn($record) => Storage::url($record->memo_file)),
                    )
                    ->alignCenter()
                    ->tooltip('View Memorandum')
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                ->label('')
                ->size('xl'),
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
            'index' => Pages\ListDrivers::route('/'),
            // 'create' => Pages\CreateDriver::route('/create'),
            // 'edit' => Pages\EditDriver::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
