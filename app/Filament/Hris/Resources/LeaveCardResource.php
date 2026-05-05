<?php

namespace App\Filament\Hris\Resources;

use App\Filament\Hris\Resources\LeaveCardResource\Pages;
use App\Models\LeaveCard;
use App\Models\Profile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeaveCardResource extends Resource
{
    protected static ?string $model = LeaveCard::class;

    protected static ?string $navigationGroup = 'Leave/CTO Management';

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'Leave Cards';

    protected static ?string $modelLabel = 'Leave Card Entry';

    protected static ?string $pluralModelLabel = 'Leave Cards';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('profile_id')
                ->label('Employee')
                ->options(
                    Profile::query()
                        ->get()
                        ->mapWithKeys(fn (Profile $p) => [$p->id => $p->employee_number . ' ' . $p->full_name])
                )
                ->searchable()
                ->required()
                ->columnSpanFull(),

            Forms\Components\Grid::make(3)->schema([
                Forms\Components\DatePicker::make('date_applied')
                    ->label('Date Applied')
                    ->default(now())
                    ->required(),

                Forms\Components\TextInput::make('ref_code')
                    ->label('Reference Code')
                    ->nullable(),

                Forms\Components\Select::make('category')
                    ->label('Category')
                    ->options(LeaveCard::CATEGORY_SELECT)
                    ->searchable()
                    ->required(),
            ]),

            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('period_covered')
                    ->label('Period Covered')
                    ->placeholder('e.g. January 1–15, 2026')
                    ->nullable(),

                Forms\Components\TextInput::make('duration')
                    ->label('Duration (dd-hh-mm)')
                    ->placeholder('e.g. 01-08-00')
                    ->nullable(),
            ]),

            Forms\Components\Fieldset::make('Vacation Leave')
                ->schema([
                    Forms\Components\TextInput::make('vl_earned')
                        ->label('VL Earned (+)')
                        ->numeric()
                        ->default(0)
                        ->step(0.000001),
                    Forms\Components\TextInput::make('vl_with_pay')
                        ->label('VL With Pay (−)')
                        ->numeric()
                        ->default(0)
                        ->step(0.000001),
                    Forms\Components\TextInput::make('vl_without_pay')
                        ->label('VL Without Pay')
                        ->numeric()
                        ->default(0)
                        ->step(0.000001),
                ])
                ->columns(3),

            Forms\Components\Fieldset::make('Sick Leave')
                ->schema([
                    Forms\Components\TextInput::make('sl_earned')
                        ->label('SL Earned (+)')
                        ->numeric()
                        ->default(0)
                        ->step(0.000001),
                    Forms\Components\TextInput::make('sl_with_pay')
                        ->label('SL With Pay (−)')
                        ->numeric()
                        ->default(0)
                        ->step(0.000001),
                    Forms\Components\TextInput::make('sl_without_pay')
                        ->label('SL Without Pay')
                        ->numeric()
                        ->default(0)
                        ->step(0.000001),
                ])
                ->columns(3),


            Forms\Components\Textarea::make('remarks')
                ->label('Remarks')
                ->rows(2)
                ->nullable()
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordAction(Tables\Actions\EditAction::class)
            ->columns([
                Tables\Columns\TextColumn::make('date_applied')
                    ->label('Date Applied')
                    ->date('M d, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ref_code')
                    ->label('Ref. Code')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->formatStateUsing(fn (string $state): string => LeaveCard::CATEGORY_SELECT[$state] ?? $state)
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('period_covered')
                    ->label('Period Covered')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state): string => $state && $state !== '0' ? $state : '—')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('vl_earned')
                    ->label('VL Earned')
                    ->formatStateUsing(fn ($state): string => (float) $state > 0 ? number_format((float) $state, 6) : '—')
                    ->alignEnd()
                    ->color(fn ($state) => (float) $state > 0 ? 'success' : null),

                Tables\Columns\TextColumn::make('vl_with_pay')
                    ->label('VL w/ Pay')
                    ->formatStateUsing(fn ($state): string => (float) $state > 0 ? number_format((float) $state, 6) : '—')
                    ->alignEnd()
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : null),

                Tables\Columns\TextColumn::make('sl_earned')
                    ->label('SL Earned')
                    ->formatStateUsing(fn ($state): string => (float) $state > 0 ? number_format((float) $state, 6) : '—')
                    ->alignEnd()
                    ->color(fn ($state) => (float) $state > 0 ? 'success' : null),

                Tables\Columns\TextColumn::make('sl_with_pay')
                    ->label('SL w/ Pay')
                    ->formatStateUsing(fn ($state): string => (float) $state > 0 ? number_format((float) $state, 6) : '—')
                    ->alignEnd()
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : null),


                Tables\Columns\TextColumn::make('remarks')
                    ->label('Remarks')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date_applied', 'asc')
            ->emptyStateHeading('No leave card entries')
            ->emptyStateDescription('Select an employee to view their leave card, or add an entry.')
            ->emptyStateIcon('heroicon-o-table-cells')
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->options(LeaveCard::CATEGORY_SELECT),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Leave Card Entry'),
                Tables\Actions\DeleteAction::make(),
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
            'index'  => Pages\ListLeaveCards::route('/'),
            'create' => Pages\CreateLeaveCard::route('/create'),
            'edit'   => Pages\EditLeaveCard::route('/{record}/edit'),
        ];
    }
}
