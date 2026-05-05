<?php

namespace App\Filament\Hris\Resources;

use App\Filament\Hris\Resources\ServiceRecordResource\Pages;
use App\Models\Profile;
use App\Models\ServiceRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceRecordResource extends Resource
{
    protected static ?string $model = Profile::class;

    protected static ?string $navigationGroup = 'Employee Management';

    protected static ?string $navigationIcon = 'fas-List';

    protected static ?string $navigationLabel = 'Service Records';

    protected static ?string $modelLabel = 'Service Record';

    protected static ?string $pluralModelLabel = 'Service Records';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    private static function employeeOptions(): array
    {
        return Profile::query()
            ->get()
            ->mapWithKeys(fn (Profile $p) => [$p->id => $p->employee_number . ' ' . $p->full_name])
            ->toArray();
    }

    private static function createForm(): array
    {
        return [
            Forms\Components\Select::make('profile_id')
                ->label('Employee')
                ->options(static::employeeOptions())
                ->searchable()
                ->required()
                ->columnSpanFull(),
            Forms\Components\DatePicker::make('from')
                ->label('From'),
            Forms\Components\DatePicker::make('to')
                ->label('To'),
            Forms\Components\TextInput::make('agency')
                ->label('Agency')
                ->columnSpanFull(),
            Forms\Components\TextInput::make('position')
                ->label('Position')
                ->columnSpan(2),
            Forms\Components\TextInput::make('status')
                ->label('Status'),
            Forms\Components\TextInput::make('sg')
                ->label('Salary Grade'),
            Forms\Components\TextInput::make('salary')
                ->label('Salary')
                ->numeric(),
            Forms\Components\TextInput::make('allowance')
                ->label('Allowance')
                ->numeric(),
            Forms\Components\Textarea::make('remarks')
                ->label('Remarks')
                ->columnSpanFull(),
            Forms\Components\Textarea::make('other_remarks')
                ->label('Other Remarks')
                ->columnSpanFull(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn (): \Illuminate\Database\Eloquent\Builder => Profile::query()->has('serviceRecords'))
            ->columns([
                Tables\Columns\TextColumn::make('employee_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable(['surname', 'first_name', 'middle_name']),
                Tables\Columns\TextColumn::make('total_years')
                    ->label('Total Years')
                    ->getStateUsing(function (Profile $record): string {
                        $totalDays = $record->serviceRecords->sum(function ($sr) {
                            if (! $sr->from || ! $sr->to) {
                                return 0;
                            }

                            return $sr->from->diffInDays($sr->to);
                        });

                        $years = intdiv($totalDays, 365);
                        $months = intdiv($totalDays % 365, 30);

                        return "{$years}yrs {$months}mos";
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create_service_record')
                    ->label('Create Record')
                    ->icon('heroicon-o-plus')
                    ->form(static::createForm())
                    ->action(fn (array $data) => ServiceRecord::create($data))
                    ->modalWidth(MaxWidth::FourExtraLarge)
                    ->successNotificationTitle('Service record saved'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view_service_records')
                    ->label('View')
                    ->icon('fas-id-card')
                    ->modalContent(fn (Profile $record) => view(
                        'filament.hris.service-records-table',
                        ['serviceRecords' => $record->serviceRecords()->orderBy('from')->get()]
                    ))
                    ->modalWidth(MaxWidth::SevenExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Tables\Actions\Action::make('edit_service_records')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        Forms\Components\Repeater::make('service_records')
                            ->label('Service Records')
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\DatePicker::make('from')
                                    ->label('From'),
                                Forms\Components\DatePicker::make('to')
                                    ->label('To'),
                                Forms\Components\TextInput::make('agency')
                                    ->label('Agency')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('position')
                                    ->label('Position')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('status')
                                    ->label('Status'),
                                Forms\Components\TextInput::make('sg')
                                    ->label('Salary Grade'),
                                Forms\Components\TextInput::make('salary')
                                    ->label('Salary')
                                    ->numeric(),
                                Forms\Components\TextInput::make('allowance')
                                    ->label('Allowance')
                                    ->numeric(),
                                Forms\Components\Textarea::make('remarks')
                                    ->label('Remarks')
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('other_remarks')
                                    ->label('Other Remarks')
                                    ->columnSpanFull(),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(3),
                    ])
                    ->fillForm(fn (Profile $record): array => [
                        'service_records' => $record->serviceRecords()
                            ->orderBy('from')
                            ->get()
                            ->map(fn (ServiceRecord $sr) => [
                                'id'            => $sr->id,
                                'from'          => $sr->from?->toDateString(),
                                'to'            => $sr->to?->toDateString(),
                                'agency'        => $sr->agency,
                                'position'      => $sr->position,
                                'status'        => $sr->status,
                                'sg'            => $sr->sg,
                                'salary'        => $sr->salary,
                                'allowance'     => $sr->allowance,
                                'remarks'       => $sr->remarks,
                                'other_remarks' => $sr->other_remarks,
                            ])
                            ->values()
                            ->toArray(),
                    ])
                    ->action(function (array $data): void {
                        foreach ($data['service_records'] as $item) {
                            ServiceRecord::find($item['id'])?->update([
                                'from'          => $item['from'],
                                'to'            => $item['to'],
                                'agency'        => $item['agency'],
                                'position'      => $item['position'],
                                'status'        => $item['status'],
                                'sg'            => $item['sg'],
                                'salary'        => $item['salary'],
                                'allowance'     => $item['allowance'],
                                'remarks'       => $item['remarks'],
                                'other_remarks' => $item['other_remarks'],
                            ]);
                        }
                    })
                    ->modalWidth(MaxWidth::FourExtraLarge)
                    ->successNotificationTitle('Service records updated'),
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
            'index' => Pages\ListServiceRecords::route('/'),
        ];
    }
}
