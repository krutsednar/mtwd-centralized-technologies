<?php

namespace App\Filament\Home\Pages;

use App\Filament\Home\Widgets\DtrStats;
use App\Models\Attendance;
use App\Models\Profile;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MyDtr extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'fas-clock';

    protected static ?string $navigationLabel = 'My DTR';

    protected static ?string $navigationGroup = 'My Records';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.home.pages.my-dtr';

    public function getTitle(): string
    {
        return 'Daily Time Record';
    }

    protected function getHeaderWidgets(): array
    {
        return [DtrStats::class];
    }

    public function table(Table $table): Table
    {
        $employeeNumber = Profile::forCurrentUser()?->employee_number ?? '__none__';

        return $table
            ->query(
                // One representative attendance row per month (the latest day),
                // so each table row is a month the employee may drill into.
                Attendance::query()
                    ->where('employee_number', $employeeNumber)
                    ->whereIn('id', function ($sub) use ($employeeNumber): void {
                        $sub->selectRaw('MAX(id)')
                            ->from('attendances')
                            ->where('employee_number', $employeeNumber)
                            ->whereNull('deleted_at')
                            ->groupByRaw('EXTRACT(YEAR FROM attendance_date), EXTRACT(MONTH FROM attendance_date)');
                    })
            )
            ->defaultSort('attendance_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('attendance_date')
                    ->label('Month')
                    ->formatStateUsing(fn ($state): string => Carbon::parse($state)->format('F Y'))
                    ->weight(\Filament\Support\Enums\FontWeight::Medium)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('year')
                    ->options(collect(range(now()->year, now()->year - 5))->mapWithKeys(fn ($y) => [$y => $y])->all())
                    ->default(now()->year)
                    ->query(fn (Builder $query, array $data): Builder => $data['value']
                        ? $query->whereYear('attendance_date', $data['value'])
                        : $query),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->color('primary')
                    ->modalHeading(fn (Attendance $record): string => 'Daily Time Record — '.Carbon::parse($record->attendance_date)->format('F Y'))
                    ->modalContent(fn (Attendance $record) => view('filament.home.dtr-day-grid', [
                        'profile' => Profile::forCurrentUser(),
                        'month' => (int) Carbon::parse($record->attendance_date)->month,
                        'year' => (int) Carbon::parse($record->attendance_date)->year,
                    ]))
                    ->modalWidth(MaxWidth::FiveExtraLarge)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->paginated([12, 24, 'all'])
            ->emptyStateHeading('No attendance records')
            ->emptyStateDescription('Your monthly DTR will appear here once you have recorded attendance.')
            ->emptyStateIcon('heroicon-o-clock');
    }
}
