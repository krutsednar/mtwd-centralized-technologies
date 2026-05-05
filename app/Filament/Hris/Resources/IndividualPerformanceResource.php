<?php

namespace App\Filament\Hris\Resources;

use Asmit\FilamentUpload\Forms\Components\AdvancedFileUpload;
use App\Filament\Hris\Resources\IndividualPerformanceResource\Pages;
use App\Models\IndividualPerformance;
use App\Models\Profile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class IndividualPerformanceResource extends Resource
{
    protected static ?string $model = Profile::class;

    protected static ?string $navigationGroup = 'Employee Management';

    protected static ?string $navigationIcon = 'fas-user-check';

    protected static ?string $navigationLabel = 'Individual Performance';

    protected static ?string $modelLabel = 'Individual Performance';

    protected static ?string $pluralModelLabel = 'Individual Performances';

    protected static ?int $navigationSort = 5;

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

    public static function ipcForm(): array
    {
        return [
            Forms\Components\Select::make('profile_id')
                ->label('Employee')
                ->options(static::employeeOptions())
                ->searchable()
                ->required(),
            Forms\Components\Select::make('ipc_year')
                ->label('Year')
                ->options(
                    collect(range(now()->year - 10, now()->year + 1))
                        ->mapWithKeys(fn ($y) => [$y => $y])
                )
                ->default(now()->year)
                ->searchable()
                ->required(),
            AdvancedFileUpload::make('ipc_attachment')
                ->label('IPC Attachment')
                ->directory('ipc_files')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(104800)
                ->nullable(),
            AdvancedFileUpload::make('ipcr_first')
                ->label('IPCR 1st Semester')
                ->directory('ipcr_files')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(104800)
                ->nullable(),
            AdvancedFileUpload::make('ipcr_second')
                ->label('IPCR 2nd Semester')
                ->directory('ipcr_files')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(104800)
                ->nullable(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn (): \Illuminate\Database\Eloquent\Builder => Profile::query()->has('individualPerformances'))
            ->columns([
                Tables\Columns\TextColumn::make('employee_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Full Name')
                    ->searchable(['surname', 'first_name', 'middle_name']),
                Tables\Columns\TextColumn::make('division.name')
                    ->label('Division')
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('add_record')
                    ->label('Create Record')
                    ->icon('heroicon-o-plus')
                    ->form(static::ipcForm())
                    ->action(fn (array $data) => IndividualPerformance::create($data))
                    ->successNotificationTitle('Record saved'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view_performance')
                    ->label('View')
                    ->icon('fas-user-check')
                    ->modalHeading(fn (Profile $record) => 'Individual Performance — ' . $record->full_name)
                    ->modalContent(fn (Profile $record) => view(
                        'filament.hris.individual-performance-cards',
                        [
                            'individualPerformances' => $record->individualPerformances()
                                ->orderByDesc('ipc_year')
                                ->get(),
                        ]
                    ))
                    ->modalWidth(MaxWidth::ThreeExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Tables\Actions\Action::make('edit_record')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        Forms\Components\Repeater::make('performances')
                            ->label('Records')
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\TextInput::make('ipc_year')
                                    ->label('Year')
                                    ->disabled(),
                                AdvancedFileUpload::make('ipc_attachment')
                                    ->label('IPC Attachment')
                                    ->directory('ipc_files')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(104800)
                                    ->nullable(),
                                AdvancedFileUpload::make('ipcr_first')
                                    ->label('IPCR 1st Semester')
                                    ->directory('ipcr_files')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(104800)
                                    ->nullable(),
                                AdvancedFileUpload::make('ipcr_second')
                                    ->label('IPCR 2nd Semester')
                                    ->directory('ipcr_files')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(104800)
                                    ->nullable(),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(2),
                    ])
                    ->fillForm(fn (Profile $record): array => [
                        'performances' => $record->individualPerformances()
                            ->orderByDesc('ipc_year')
                            ->get()
                            ->map(fn (IndividualPerformance $ipc) => [
                                'id'             => $ipc->id,
                                'ipc_year'       => $ipc->ipc_year,
                                'ipc_attachment' => $ipc->ipc_attachment,
                                'ipcr_first'     => $ipc->ipcr_first,
                                'ipcr_second'    => $ipc->ipcr_second,
                            ])
                            ->values()
                            ->toArray(),
                    ])
                    ->action(function (array $data): void {
                        foreach ($data['performances'] as $item) {
                            IndividualPerformance::find($item['id'])?->update([
                                'ipc_attachment' => $item['ipc_attachment'],
                                'ipcr_first'     => $item['ipcr_first'],
                                'ipcr_second'    => $item['ipcr_second'],
                            ]);
                        }
                    })
                    ->modalWidth(MaxWidth::ThreeExtraLarge)
                    ->successNotificationTitle('Records updated'),
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
            'index' => Pages\ListIndividualPerformances::route('/'),
        ];
    }
}
