<?php

namespace App\Filament\Home\Pages;

use App\Models\IndividualPerformance;
use App\Models\Profile;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class MyPerformance extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'fas-user-check';

    protected static ?string $navigationLabel = 'Individual Performance';

    protected static ?string $navigationGroup = 'My Records';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.home.pages.my-performance';

    public function getTitle(): string
    {
        return 'Individual Performance';
    }

    public function table(Table $table): Table
    {
        $document = fn (string $column, string $label): Tables\Columns\TextColumn => Tables\Columns\TextColumn::make($column)
            ->label($label)
            ->badge()
            ->icon(fn (?string $state): ?string => $state ? 'heroicon-m-document-text' : null)
            ->color(fn (?string $state): string => $state ? 'primary' : 'gray')
            ->formatStateUsing(fn (?string $state): string => $state ? 'View PDF' : 'Not uploaded')
            ->url(fn (IndividualPerformance $record): ?string => $record->{$column} ? Storage::url($record->{$column}) : null, true);

        return $table
            ->query(
                IndividualPerformance::query()
                    ->where('profile_id', Profile::forCurrentUser()?->id ?? 0)
            )
            ->defaultSort('ipc_year', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('ipc_year')
                    ->label('Year')
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->sortable(),
                $document('ipc_attachment', 'IPC'),
                $document('ipcr_first', 'IPCR — 1st Sem'),
                $document('ipcr_second', 'IPCR — 2nd Sem'),
            ])
            ->paginated([12, 24, 'all'])
            ->emptyStateHeading('No performance records on file')
            ->emptyStateDescription('Your IPC / IPCR documents will appear here once HR adds them.')
            ->emptyStateIcon('heroicon-o-chart-bar');
    }
}
