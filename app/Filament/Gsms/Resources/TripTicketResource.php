<?php

namespace App\Filament\Gsms\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Driver;
use App\Models\Profile;
use App\Models\Vehicle;
use Filament\Forms\Form;
use App\Models\TripTicket;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\ActionsPosition;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Gsms\Resources\TripTicketResource\Pages;
use App\Filament\Gsms\Resources\TripTicketResource\RelationManagers;

class TripTicketResource extends Resource
{
    protected static ?string $model = TripTicket::class;

    protected static ?string $navigationIcon = 'fas-ticket';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(2)
            ->schema([
                // Split::make([
                //     Section::make([
                //         TextInput::make('title'),
                //         Textarea::make('content'),
                //     ]),
                //     Section::make([
                //         Toggle::make('is_published'),
                //         Toggle::make('is_featured'),
                //     ])->grow(false),
                // ])->from('xl'),
                Section::make('Ticket Details')
                // ->description('Prevent abuse by limiting the number of requests per period')
                    ->columnSpan(1)
                    ->schema([
                        Forms\Components\TextInput::make('ticket_no')
                        ->maxLength(255)
                        ->readOnly()
                        ->placeholder(
                                fn () =>

                                Carbon::now()->format('Ym').str_pad(TripTicket::count() + 1, 6, '0', STR_PAD_LEFT)
                            ),
                        Forms\Components\DatePicker::make('date')
                            ->native(false)
                            ->displayFormat('F d, Y'),
                        // Forms\Components\TextInput::make('vehicle_id')
                        //     ->maxLength(255)
                        //     ->default(null),
                        Forms\Components\Select::make('vehicle_id')
                            ->label('Vehicle')
                            ->searchable()
                            ->options(
                                Vehicle::all()
                                    ->sortBy(fn ($v) => $v->vehicleName())
                                    ->mapWithKeys(fn ($v) => [$v->id => $v->vehicleName()])
                                    ->toArray()
                            )
                            ->reactive(), // 🚨 Make it reactive so changes trigger recomputation

                        Forms\Components\Select::make('profile_id')
                            ->label('Driver\'s Name')
                            ->options(function (callable $get) {
                                $vehicleId = $get('vehicle_id');

                                if (!$vehicleId) {
                                    return [];
                                }

                                // 🔍 Get drivers whose primary vehicle or assigned vehicle matches
                                $drivers = Driver::with('profile', 'vehicles')
                                    ->where('primary_vehicle', $vehicleId)
                                    ->orWhereHas('vehicles', fn ($q) => $q->where('vehicles.id', $vehicleId))
                                    ->get();

                                return $drivers->mapWithKeys(fn ($driver) => [
                                    $driver->profile_id => $driver->profile?->full_name,
                                ])->toArray();
                            })
                            ->searchable()
                            ->reactive()
                            ->afterStateHydrated(fn ($component) => $component->options(function (callable $get) {
                                $vehicleId = $get('vehicle_id');

                                if (!$vehicleId) {
                                    return [];
                                }

                                $drivers = Driver::with('profile', 'vehicles')
                                    ->where('primary_vehicle', $vehicleId)
                                    ->orWhereHas('vehicles', fn ($q) => $q->where('vehicles.id', $vehicleId))
                                    ->get();

                                return $drivers->mapWithKeys(fn ($driver) => [
                                    $driver->profile_id => $driver->profile?->full_name,
                                ])->toArray();
                            })),
                ]),
                Section::make('Travel Time')
                // ->description('Prevent abuse by limiting the number of requests per period')
                    ->columnSpan(1)
                    ->columns(2)
                    ->schema([
                        Forms\Components\DateTimePicker::make('office_departure')
                        ->seconds(false),
                        Forms\Components\DateTimePicker::make('destination_arrival')
                        ->seconds(false),
                        Forms\Components\DateTimePicker::make('destination_departure')
                        ->seconds(false),
                        Forms\Components\DateTimePicker::make('office_arrival')
                        ->seconds(false),
                        Forms\Components\TextInput::make('distance_travelled')
                            ->numeric()
                            ->default(null),
                ]),
                Section::make('Travel Details')
                // ->description('Prevent abuse by limiting the number of requests per period')
                    ->columnSpan(1)
                    ->schema([
                        Forms\Components\TextInput::make('destination')
                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\Select::make('profiles')
                            ->label('Passengers')
                            ->relationship('profiles', 'id') // Use actual column
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                            ->multiple()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('purpose')
                            ->maxLength(255)
                            ->default(null),

                ]),
                Section::make('Gasoline issued, purchased and consumed')
                // ->description('Prevent abuse by limiting the number of requests per period')
                    ->columnSpan(1)
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('beginning_balance')
                        ->numeric()
                        ->default(null),
                        Forms\Components\TextInput::make('purchase')
                            ->numeric()
                            ->default(null),
                        Forms\Components\TextInput::make('consumed')
                            ->numeric()
                            ->default(null),
                        Forms\Components\TextInput::make('ending_balance')
                            ->numeric()
                            ->default(null),
                        Forms\Components\TextInput::make('oil_grease_lub_issued')
                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\TextInput::make('speedometer_reading')
                            ->numeric()
                            ->default(null),
                        Forms\Components\TextInput::make('actual_distance_travelled')
                            ->numeric()
                            ->default(null),
                        Forms\Components\Textarea::make('remarks')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->default(null),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket_no')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle.vehicle_name')
                    ->searchable(['model', 'brand', 'plate_no']),
                Tables\Columns\TextColumn::make('profile.full_name')
                    ->label('Driver\'s Name')
                    ->searchable(['first_name', 'middle_name', 'surname'])
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('destination')
                    ->searchable(),
                Tables\Columns\TextColumn::make('profiles.full_name')
                    ->label('Passengers')
                    ->getStateUsing(fn ($record) =>
                        $record->profiles
                            ->unique('id')
                            ->map(fn ($v) => $v->fullName())
                            ->toArray()
                    )
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('purpose')
                    ->searchable(),
                Tables\Columns\TextColumn::make('office_departure')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('destination_arrival')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('destination_departure')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('office_arrival')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('distance_travelled')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('beginning_balance')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('consumed')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ending_balance')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('oil_grease_lub_issued')
                    ->searchable(),
                Tables\Columns\TextColumn::make('speedometer_reading')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('actual_distance_travelled')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('remarks')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListTripTickets::route('/'),
            // 'create' => Pages\CreateTripTicket::route('/create'),
            // 'edit' => Pages\EditTripTicket::route('/{record}/edit'),
        ];
    }
}
