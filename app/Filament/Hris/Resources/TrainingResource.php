<?php

namespace App\Filament\Hris\Resources;

use App\Filament\Hris\Resources\TrainingResource\Pages;
use App\Models\Profile;
use App\Models\Training;
use Asmit\FilamentUpload\Forms\Components\AdvancedFileUpload;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TrainingResource extends Resource
{
    protected static ?string $model = Profile::class;

    protected static ?string $navigationGroup = 'Employee Management';

    protected static ?string $navigationIcon = 'fas-certificate';

    protected static ?string $navigationLabel = 'Trainings';

    protected static ?string $pluralModelLabel = 'Trainings';

    protected static ?int $navigationSort = 3;

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
            ->mapWithKeys(fn (Profile $p) => [$p->id => $p->employee_number.' '.$p->full_name])
            ->toArray();
    }

    private static function createForm(): array
    {
        return [
            Forms\Components\Select::make('profile_id')
                ->label('Employee')
                ->options(static::employeeOptions())
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('title')
                ->label('Title')
                ->required()
                ->columnSpanFull(),
            Forms\Components\DatePicker::make('from')
                ->label('From'),
            Forms\Components\DatePicker::make('to')
                ->label('To'),
            Forms\Components\TextInput::make('number_of_hours')
                ->label('No. of Hours')
                ->numeric(),
            Forms\Components\TextInput::make('conducted_by')
                ->label('Conducted By'),
            Forms\Components\Select::make('ld_type')
                ->label('L&D Type')
                ->options(Training::LD_TYPE_SELECT),
            AdvancedFileUpload::make('attachment')
                ->label('Certificate')
                ->directory('training_files')
                ->acceptedFileTypes(['application/pdf'])
                ->maxSize(104800)
                ->nullable()
                ->columnSpanFull(),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn (): \Illuminate\Database\Eloquent\Builder => Profile::query()->has('trainings'))
            ->columns([
                Tables\Columns\TextColumn::make('employee_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable(['surname', 'first_name', 'middle_name']),
                Tables\Columns\TextColumn::make('trainings.title')
                    ->label('Training Title')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_hours')
                    ->label('Total Hours')
                    ->getStateUsing(fn (Profile $record): int|float => $record->trainings->sum('number_of_hours'))
                    ->suffix(' hrs')
                    ->sortable(query: fn (\Illuminate\Database\Eloquent\Builder $query, string $direction) => $query->withSum('trainings', 'number_of_hours')->orderBy('trainings_sum_number_of_hours', $direction)),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create_training')
                    ->label('Create Record')
                    ->icon('heroicon-o-plus')
                    ->form(static::createForm())
                    ->action(fn (array $data) => Training::create($data))
                    ->modalWidth(MaxWidth::ThreeExtraLarge)
                    ->successNotificationTitle('Training record saved'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('view_trainings')
                    ->label('View')
                    ->icon('fas-certificate')
                    ->slideOver()
                    ->infolist([
                        Infolists\Components\Actions::make([
                            Infolists\Components\Actions\Action::make('downloadAllCertificates')
                                ->label('Download All Certificates (ZIP)')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('success')
                                ->visible(fn (Profile $record): bool => $record->trainings()
                                    ->whereNotNull('attachment')
                                    ->where('attachment', '!=', '')
                                    ->exists())
                                ->action(fn (Profile $record): ?BinaryFileResponse => static::downloadCertificatesZip($record)),
                        ]),
                        Infolists\Components\RepeatableEntry::make('trainings')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('title')
                                    ->columnSpanFull(),
                                Infolists\Components\TextEntry::make('from')
                                    ->date('F d, Y'),
                                Infolists\Components\TextEntry::make('to')
                                    ->date('F d, Y'),
                                Infolists\Components\TextEntry::make('number_of_hours')
                                    ->label('No. of Hours')
                                    ->suffix(' hours'),
                                Infolists\Components\TextEntry::make('conducted_by'),
                                Infolists\Components\TextEntry::make('ld_type'),
                                Infolists\Components\TextEntry::make('attachment')
                                    ->label('Certificate')
                                    ->formatStateUsing(fn ($state): string => $state ? 'View Certificate' : '—')
                                    ->url(fn ($record): ?string => $record->attachment ? Storage::url($record->attachment) : null)
                                    ->openUrlInNewTab()
                                    ->color('info'),
                            ])
                            ->columns(3),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Tables\Actions\Action::make('edit_trainings')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->form([
                        Forms\Components\Repeater::make('trainings')
                            ->label('Training Records')
                            ->schema([
                                Forms\Components\Hidden::make('id'),
                                Forms\Components\TextInput::make('title')
                                    ->label('Title')
                                    ->columnSpanFull(),
                                Forms\Components\DatePicker::make('from')
                                    ->label('From'),
                                Forms\Components\DatePicker::make('to')
                                    ->label('To'),
                                Forms\Components\TextInput::make('number_of_hours')
                                    ->label('No. of Hours')
                                    ->numeric(),
                                Forms\Components\TextInput::make('conducted_by')
                                    ->label('Conducted By'),
                                Forms\Components\Select::make('ld_type')
                                    ->label('L&D Type')
                                    ->options(Training::LD_TYPE_SELECT),
                                AdvancedFileUpload::make('attachment')
                                    ->label('Certificate')
                                    ->directory('training_files')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(104800)
                                    ->nullable()
                                    ->columnSpanFull(),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columns(2),
                    ])
                    ->fillForm(fn (Profile $record): array => [
                        'trainings' => $record->trainings()
                            ->orderByDesc('from')
                            ->get()
                            ->map(fn (Training $t) => [
                                'id' => $t->id,
                                'title' => $t->title,
                                'from' => $t->from ? \Carbon\Carbon::parse($t->from)->toDateString() : null,
                                'to' => $t->to ? \Carbon\Carbon::parse($t->to)->toDateString() : null,
                                'number_of_hours' => $t->number_of_hours,
                                'conducted_by' => $t->conducted_by,
                                'ld_type' => $t->ld_type,
                                'attachment' => $t->attachment,
                            ])
                            ->values()
                            ->toArray(),
                    ])
                    ->action(function (array $data): void {
                        foreach ($data['trainings'] as $item) {
                            Training::find($item['id'])?->update([
                                'title' => $item['title'],
                                'from' => $item['from'],
                                'to' => $item['to'],
                                'number_of_hours' => $item['number_of_hours'],
                                'conducted_by' => $item['conducted_by'],
                                'ld_type' => $item['ld_type'],
                                'attachment' => $item['attachment'],
                            ]);
                        }
                    })
                    ->modalWidth(MaxWidth::FourExtraLarge)
                    ->successNotificationTitle('Trainings updated'),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([]);
    }

    /**
     * Bundle every uploaded training certificate for an employee into a single ZIP
     * download. Certificates are named after their training title (de-duplicated),
     * and trainings without a stored file are skipped. Returns null (with a
     * warning notification) when the employee has no downloadable certificate.
     */
    public static function downloadCertificatesZip(Profile $record): ?BinaryFileResponse
    {
        $disk = Storage::disk(config('filament.default_filesystem_disk'));

        $trainings = $record->trainings()
            ->whereNotNull('attachment')
            ->where('attachment', '!=', '')
            ->orderByDesc('from')
            ->get();

        $zipPath = tempnam(sys_get_temp_dir(), 'certs_');
        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $added = 0;
        $usedNames = [];

        foreach ($trainings as $training) {
            if (! $disk->exists($training->attachment)) {
                continue;
            }

            $extension = pathinfo($training->attachment, PATHINFO_EXTENSION) ?: 'pdf';
            $base = Str::slug($training->title ?: 'certificate') ?: 'certificate';
            $name = "{$base}.{$extension}";

            $counter = 1;
            while (isset($usedNames[$name])) {
                $name = $base.'-'.(++$counter).'.'.$extension;
            }
            $usedNames[$name] = true;

            $zip->addFromString($name, $disk->get($training->attachment));
            $added++;
        }

        $zip->close();

        if ($added === 0) {
            @unlink($zipPath);

            Notification::make()
                ->title('No certificates found')
                ->body('This employee has no uploaded training certificates to download.')
                ->warning()
                ->send();

            return null;
        }

        $filename = Str::slug($record->employee_number.' '.$record->full_name).'-certificates.zip';

        return response()->download($zipPath, $filename)->deleteFileAfterSend(true);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrainings::route('/'),
        ];
    }
}
