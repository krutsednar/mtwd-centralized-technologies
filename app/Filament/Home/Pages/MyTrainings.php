<?php

namespace App\Filament\Home\Pages;

use App\Filament\Home\Widgets\TrainingStats;
use App\Models\Profile;
use App\Models\Training;
use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class MyTrainings extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'fas-certificate';

    protected static ?string $navigationLabel = 'My Trainings';

    protected static ?string $navigationGroup = 'My Records';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.home.pages.my-trainings';

    public function getTitle(): string
    {
        return 'Trainings & Development';
    }

    protected function getHeaderWidgets(): array
    {
        return [TrainingStats::class];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Training::query()
                    ->where('profile_id', Profile::forCurrentUser()?->id ?? 0)
            )
            ->defaultSort('from', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->weight(FontWeight::Medium)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('from')
                    ->date('M d, Y')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('to')
                    ->date('M d, Y')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('number_of_hours')
                    ->label('Hours')
                    ->suffix(' hrs')
                    ->alignEnd()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('conducted_by')
                    ->wrap()
                    ->placeholder('—')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ld_type')
                    ->label('Type')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('attachment')
                    ->label('Certificate')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-m-document-text')
                    ->formatStateUsing(fn (?string $state): string => $state ? 'View' : '—')
                    ->url(fn (Training $record): ?string => $record->attachment ? Storage::url($record->attachment) : null, true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ld_type')
                    ->label('L&D Type')
                    ->options(Training::LD_TYPE_SELECT),
            ])
            ->paginated([10, 25, 50, 'all'])
            ->emptyStateHeading('No training records on file')
            ->emptyStateDescription('Your attended trainings will appear here once HR adds them.')
            ->emptyStateIcon('heroicon-o-book-open');
    }
}
