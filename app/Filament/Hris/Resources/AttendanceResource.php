<?php

namespace App\Filament\Hris\Resources;

use App\Filament\Hris\Resources\AttendanceResource\Pages;
use App\Models\Division;
use App\Models\Profile;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;

class AttendanceResource extends Resource
{
    protected static ?string $model = Profile::class;

    protected static ?string $navigationGroup = 'Attendance Management';

    protected static ?string $navigationIcon = 'fas-clock';

    protected static ?string $navigationLabel = 'DTRs';

    protected static ?string $modelLabel = 'DTR';

    protected static ?string $pluralModelLabel = 'DTRs';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn (): \Illuminate\Database\Eloquent\Builder => Profile::query()->has('attendances'))
            ->columns([
                Tables\Columns\TextColumn::make('employee_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Full Name')
                    ->searchable(['surname', 'first_name', 'middle_name']),
                Tables\Columns\TextColumn::make('division.name')
                    ->label('Division')
                    ->sortable()
                    ->searchable(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('bulk_print_dtr')
                    ->label('DTR Bulk Print')
                    ->icon('heroicon-o-printer')
                    ->form([
                        \Filament\Forms\Components\Select::make('division_id')
                            ->label('Division')
                            ->options(Division::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        \Filament\Forms\Components\Select::make('month')
                            ->label('Month')
                            ->options(
                                collect(range(1, 12))->mapWithKeys(
                                    fn ($m) => [$m => \Carbon\Carbon::createFromDate(2000, $m, 1)->format('F')]
                                )
                            )
                            ->default(now()->month)
                            ->required(),
                        \Filament\Forms\Components\Select::make('year')
                            ->label('Year')
                            ->options(
                                collect(range(now()->year - 5, now()->year + 1))
                                    ->mapWithKeys(fn ($y) => [$y => $y])
                            )
                            ->default(now()->year)
                            ->required(),
                    ])
                    ->modalSubmitActionLabel('Open Print Preview')
                    ->action(function (array $data, \Livewire\Component $livewire): void {
                        $livewire->redirect(
                            route('dtr.print.division', $data['division_id']).'?month='.$data['month'].'&year='.$data['year'],
                            navigate: false
                        );
                    }),

                Tables\Actions\Action::make('jo_bulk_print_dtr')
                    ->label('Job Order Bulk Print')
                    ->icon('heroicon-o-printer')
                    ->color('warning')
                    ->form([
                        \Filament\Forms\Components\Select::make('division_id')
                            ->label('Division')
                            ->options(Division::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        \Filament\Forms\Components\Select::make('month')
                            ->label('Month')
                            ->options(
                                collect(range(1, 12))->mapWithKeys(
                                    fn ($m) => [$m => \Carbon\Carbon::createFromDate(2000, $m, 1)->format('F')]
                                )
                            )
                            ->default(now()->month)
                            ->required(),
                        \Filament\Forms\Components\Select::make('cutoff')
                            ->label('Cut-off Period')
                            ->options([
                                'first' => '1st Cut-off (26th prev. month – 10th)',
                                'second' => '2nd Cut-off (11th – 25th)',
                            ])
                            ->default('first')
                            ->required(),
                        \Filament\Forms\Components\Select::make('year')
                            ->label('Year')
                            ->options(
                                collect(range(now()->year - 5, now()->year + 1))
                                    ->mapWithKeys(fn ($y) => [$y => $y])
                            )
                            ->default(now()->year)
                            ->required(),
                    ])
                    ->modalSubmitActionLabel('Open Print Preview')
                    ->action(function (array $data, \Livewire\Component $livewire): void {
                        $livewire->redirect(
                            route('dtr.print.jo', $data['division_id'])
                                .'?month='.$data['month']
                                .'&cutoff='.$data['cutoff']
                                .'&year='.$data['year'],
                            navigate: false
                        );
                    }),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view_dtr')
                    ->label('View DTR')
                    ->icon('fas-clock')
                    ->modalHeading(fn (Profile $record) => 'DTR — '.$record->full_name)
                    ->modalContent(fn (Profile $record) => view('filament.hris.dtr-modal', ['profile' => $record]))
                    ->modalWidth(MaxWidth::FiveExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Tables\Actions\Action::make('print_dtr')
                    ->label('Print DTR')
                    ->icon('heroicon-o-printer')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required()
                            ->default(now()->startOfMonth())
                            ->live(),
                        \Filament\Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->required()
                            ->default(now()->endOfMonth())
                            ->afterOrEqual('start_date')
                            ->rules([
                                function (\Filament\Forms\Get $get): \Closure {
                                    return function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                        $start = $get('start_date');
                                        if ($start && $value) {
                                            $days = \Carbon\Carbon::parse($start)->diffInDays(\Carbon\Carbon::parse($value));
                                            if ($days > 30) {
                                                $fail('Date coverage must not exceed 31 days.');
                                            }
                                        }
                                    };
                                },
                            ]),
                    ])
                    ->modalSubmitActionLabel('Open Print Preview')
                    ->action(function (array $data, Profile $record, \Livewire\Component $livewire): void {
                        $livewire->redirect(
                            route('dtr.print', $record->id)
                                .'?start_date='.$data['start_date']
                                .'&end_date='.$data['end_date'],
                            navigate: false
                        );
                    }),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
        ];
    }
}
