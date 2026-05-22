<?php

namespace App\Filament\Hris\Resources;

use App\Filament\Hris\Resources\SupervisorManagementResource\Pages;
use App\Models\Division;
use App\Models\Profile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupervisorManagementResource extends Resource
{
    protected static ?string $model = Division::class;

    protected static ?string $slug = 'supervisor-management';

    protected static ?string $navigationGroup = 'Organization Management';

    protected static ?string $navigationLabel = 'Supervisor Management';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $modelLabel = 'Supervisor Assignment';

    protected static ?string $pluralModelLabel = 'Supervisor Management';

    protected static ?int $navigationSort = 31;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    private static function profileOptions(): array
    {
        return Profile::query()
            ->orderBy('surname')
            ->get()
            ->mapWithKeys(fn (Profile $p) => [
                $p->id => $p->employee_number.' — '.$p->full_name,
            ])
            ->toArray();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => Division::query()->orderBy('sort_order')->orderBy('name'))
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Level')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Division::TYPE_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        Division::TYPE_OGM => 'primary',
                        Division::TYPE_OAGM => 'warning',
                        Division::TYPE_ODM => 'info',
                        Division::TYPE_DIVISION => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('name')
                    ->label('Org Unit')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Division $record): string => $record->abbreviation ?? ''),

                Tables\Columns\TextColumn::make('head.full_name')
                    ->label('Head')
                    ->placeholder('— not assigned —')
                    ->searchable(['profiles.surname', 'profiles.first_name']),

                Tables\Columns\IconColumn::make('oic_active')
                    ->label('OIC Active')
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('oic.full_name')
                    ->label('OIC')
                    ->placeholder('—')
                    ->color('warning'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Level')
                    ->options(Division::TYPE_LABELS),

                Tables\Filters\TernaryFilter::make('oic_active')
                    ->label('OIC Active'),

                Tables\Filters\Filter::make('no_head')
                    ->label('No Head Assigned')
                    ->query(fn ($query) => $query->whereNull('head_profile_id')),
            ])
            ->actions([
                Tables\Actions\Action::make('assign')
                    ->label('Assign')
                    ->icon('heroicon-o-pencil')
                    ->form(function (Division $record): array {
                        $options = static::profileOptions();

                        return [
                            Forms\Components\Select::make('head_profile_id')
                                ->label('Head of Group')
                                ->options($options)
                                ->searchable()
                                ->nullable()
                                ->default($record->head_profile_id)
                                ->columnSpanFull()
                                ->helperText('The permanently assigned head of this org unit.'),

                            Forms\Components\Toggle::make('oic_active')
                                ->label('Activate OIC')
                                ->default($record->oic_active)
                                ->live()
                                ->helperText('When ON, the OIC signs instead of the head. Turn OFF when the head returns.'),

                            Forms\Components\Select::make('oic_profile_id')
                                ->label('Officer-In-Charge (OIC)')
                                ->options($options)
                                ->searchable()
                                ->nullable()
                                ->default($record->oic_profile_id)
                                ->columnSpanFull()
                                ->visible(fn (Forms\Get $get): bool => (bool) $get('oic_active'))
                                ->requiredIf('oic_active', true)
                                ->helperText('Required when OIC is activated.'),
                        ];
                    })
                    ->fillForm(fn (Division $record): array => [
                        'head_profile_id' => $record->head_profile_id,
                        'oic_active' => $record->oic_active,
                        'oic_profile_id' => $record->oic_profile_id,
                    ])
                    ->action(function (Division $record, array $data): void {
                        $record->update([
                            'head_profile_id' => $data['head_profile_id'],
                            'oic_active' => $data['oic_active'],
                            'oic_profile_id' => $data['oic_active'] ? $data['oic_profile_id'] : null,
                        ]);
                    })
                    ->modalHeading(fn (Division $record): string => 'Assign — '.$record->name)
                    ->modalWidth('lg')
                    ->successNotificationTitle('Supervisor assignment saved'),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupervisorManagement::route('/'),
        ];
    }
}
