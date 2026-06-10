<?php

namespace App\Filament\Home\Pages;

use App\Filament\Home\Widgets\ServiceRecordStats;
use App\Models\Profile;
use App\Models\ServiceRecord;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class MyServiceRecord extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'fas-list';

    protected static ?string $navigationLabel = 'Service Record';

    protected static ?string $navigationGroup = 'My Records';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.home.pages.my-service-record';

    public function getTitle(): string
    {
        return 'Service Record';
    }

    protected function getHeaderWidgets(): array
    {
        return [ServiceRecordStats::class];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ServiceRecord::query()
                    ->where('profile_id', Profile::forCurrentUser()?->id ?? 0)
            )
            ->defaultSort('from', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('from')
                    ->date('M d, Y')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('to')
                    ->date('M d, Y')
                    ->placeholder('Present')
                    ->sortable(),
                Tables\Columns\TextColumn::make('agency')
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('position')
                    ->weight(\Filament\Support\Enums\FontWeight::Medium)
                    ->wrap()
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('sg')
                    ->label('SG')
                    ->alignCenter()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('salary')
                    ->money('PHP')
                    ->alignEnd()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('allowance')
                    ->money('PHP')
                    ->alignEnd()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('remarks')
                    ->wrap()
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->paginated([10, 25, 50, 'all'])
            ->emptyStateHeading('No service records on file')
            ->emptyStateDescription('Your service record entries will appear here once HR adds them.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }
}
