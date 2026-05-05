<?php

namespace App\Filament\Gsms\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Profile;
use Filament\Forms\Get;
use App\Models\Division;
use Filament\Forms\Form;
use App\Models\Equipment;
use Filament\Tables\Table;
use App\Models\EquipmentType;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\ActionsPosition;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use App\Filament\Gsms\Resources\EquipmentResource\Pages;
use App\Filament\Gsms\Resources\EquipmentResource\RelationManagers;

class EquipmentResource extends Resource
{
    protected static ?string $model = Equipment::class;

    protected static ?string $navigationIcon = 'mdi-generator-portable';

    protected static ?string $navigationGroup = 'Transport and Equipment';

    protected static ?string $navigationLabel = 'Equipment';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('equipment_type_id')
                    ->label('Type')
                    ->searchable()
                    ->options(EquipmentType::pluck('name', 'id'))
                    ->required(),
                Forms\Components\TextInput::make('brand')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('model')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('serial_number')
                    ->required()
                    ->maxLength(255),
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
                Forms\Components\TextInput::make('location')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('value')
                    ->label('Acquisition Cost')
                    ->numeric()
                    ->prefix('₱')
                    ->default(null),
                Forms\Components\Textarea::make('desc')
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->options([
                        'Good Running Condition' => 'Good Running Condition',
                        'Running Condition' => 'Running Condition',
                        'Unserviceable' => 'Unserviceable',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                ExportAction::make()->exports([
                    ExcelExport::make('table')->fromTable()->withFilename('Equipment Export - '.date('F d, Y')),
                ])->color('success'),

            ])
            ->columns([
                Tables\Columns\TextColumn::make('equipmentType.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('brand')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('model')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('serial_number')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('date_acquired')
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
                    ->toggleable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('value')
                    ->label('Acquisition Cost')
                    ->money('PHP')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('par_no')
                    ->label('PAR No.')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('division.abbreviation')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('profile.full_name')
                    ->label('Custodian')
                    ->searchable(['surname', 'first_name', 'middle_name'])
                    ->toggleable(),
                Tables\Columns\TextColumn::make('location')
                    ->searchable()
                    ->sortable()
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
                    })
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
            'index' => Pages\ListEquipment::route('/'),
            // 'create' => Pages\CreateEquipment::route('/create'),
            // 'edit' => Pages\EditEquipment::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
