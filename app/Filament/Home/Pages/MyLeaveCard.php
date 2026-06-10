<?php

namespace App\Filament\Home\Pages;

use App\Filament\Home\Widgets\LeaveBalanceStats;
use App\Models\LeaveCard;
use App\Models\Profile;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MyLeaveCard extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'Leave Card';

    protected static ?string $navigationGroup = 'My Records';

    protected static ?int $navigationSort = 6;

    protected static string $view = 'filament.home.pages.my-leave-card';

    public function getTitle(): string
    {
        return 'Leave Card';
    }

    protected function getHeaderWidgets(): array
    {
        return [LeaveBalanceStats::class];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                LeaveCard::query()
                    ->where('profile_id', Profile::forCurrentUser()?->id ?? 0)
            )
            ->defaultSort('date_applied', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('date_applied')
                    ->date('M d, Y')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn (string $state): string => LeaveCard::CATEGORY_SELECT[$state] ?? $state),
                Tables\Columns\TextColumn::make('period_covered')
                    ->label('Period')
                    ->wrap()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('duration')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state): string => $state && (float) $state !== 0.0 ? rtrim(rtrim(number_format((float) $state, 3), '0'), '.') : '—'),
                Tables\Columns\TextColumn::make('vl_earned')
                    ->label('VL Earned')
                    ->alignEnd()
                    ->color('success')
                    ->formatStateUsing(fn ($state): string => (float) $state > 0 ? number_format((float) $state, 3) : '—'),
                Tables\Columns\TextColumn::make('vl_with_pay')
                    ->label('VL Used')
                    ->alignEnd()
                    ->color('danger')
                    ->formatStateUsing(fn ($state): string => (float) $state > 0 ? number_format((float) $state, 3) : '—'),
                Tables\Columns\TextColumn::make('sl_earned')
                    ->label('SL Earned')
                    ->alignEnd()
                    ->color('success')
                    ->formatStateUsing(fn ($state): string => (float) $state > 0 ? number_format((float) $state, 3) : '—'),
                Tables\Columns\TextColumn::make('sl_with_pay')
                    ->label('SL Used')
                    ->alignEnd()
                    ->color('danger')
                    ->formatStateUsing(fn ($state): string => (float) $state > 0 ? number_format((float) $state, 3) : '—'),
                Tables\Columns\TextColumn::make('remarks')
                    ->wrap()
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(LeaveCard::CATEGORY_SELECT),
                Tables\Filters\SelectFilter::make('year')
                    ->options(collect(range(now()->year, now()->year - 10))->mapWithKeys(fn ($y) => [$y => $y])->all())
                    ->query(fn (Builder $query, array $data): Builder => $data['value']
                        ? $query->whereYear('date_applied', $data['value'])
                        : $query),
            ])
            ->paginated([10, 25, 50, 'all'])
            ->emptyStateHeading('No leave card entries')
            ->emptyStateDescription('Your leave ledger entries will appear here.')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }
}
