<?php

namespace App\Filament\Home\Resources;

use App\Filament\Home\Resources\LeaveApplicationResource\Pages;
use App\Filament\Hris\Resources\LeaveApplicationResource as HrisLeaveResource;
use App\Models\LeaveApplication;
use App\Models\Profile;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Employee self-service Leave Application (Home panel). Scoped to the signed-in
 * employee, fillable only up to section 6.D — HR completes 7.A–7.D in the HRIS
 * panel. Employees may edit/withdraw only while the application is still pending.
 */
class LeaveApplicationResource extends Resource
{
    protected static ?string $model = LeaveApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'My Records';

    protected static ?string $navigationLabel = 'Apply for Leave';

    protected static ?string $modelLabel = 'Leave Application';

    protected static ?string $pluralModelLabel = 'Leave Applications';

    protected static ?int $navigationSort = 7;

    public static function currentProfile(): ?Profile
    {
        $user = auth()->user();

        if (! $user) {
            return null;
        }

        return Profile::with('division')
            ->where('employee_number', $user->employee_number)
            ->first();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('profile_id', static::currentProfile()?->id ?? 0);
    }

    // ── Self-authorization (employees manage only their own, pending records) ──

    public static function canViewAny(): bool
    {
        return static::currentProfile() !== null;
    }

    public static function canCreate(): bool
    {
        return static::currentProfile() !== null;
    }

    public static function canView(Model $record): bool
    {
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        return $record->isPendingHrAction();
    }

    public static function canDelete(Model $record): bool
    {
        return $record->isPendingHrAction();
    }

    public static function form(Form $form): Form
    {
        return $form->schema(
            HrisLeaveResource::applicationDetailsSchema(
                withSignatoryResolution: false,
                lockedProfileId: static::currentProfile()?->id,
            )
        );
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Application')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('leave_application_no')->label('Application No.'),
                    Infolists\Components\TextEntry::make('leave_type')
                        ->label('Type of Leave')
                        ->formatStateUsing(fn (string $state): string => LeaveApplication::LEAVE_TYPE_SELECT[$state] ?? $state),
                    Infolists\Components\TextEntry::make('date_of_filing')->date('F d, Y'),
                    Infolists\Components\TextEntry::make('days_applied_number')->label('Working Days')->suffix(' day(s)'),
                    Infolists\Components\TextEntry::make('commutation')
                        ->formatStateUsing(fn (?string $state): string => LeaveApplication::COMMUTATION_SELECT[$state] ?? '—'),
                ]),

            Infolists\Components\Section::make('Action on Application (HR)')
                ->description('Completed by HR / management.')
                ->columns(2)
                ->schema([
                    Infolists\Components\TextEntry::make('recommendation')
                        ->label('7.B Recommendation')
                        ->formatStateUsing(fn (?string $state): string => $state ? (LeaveApplication::RECOMMENDATION_SELECT[$state] ?? $state) : 'Pending')
                        ->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            'for_approval' => 'success',
                            'for_disapproval' => 'danger',
                            default => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('approval_status')
                        ->label('7.C Approval')
                        ->formatStateUsing(fn (?string $state): string => $state ? (LeaveApplication::APPROVAL_STATUS_SELECT[$state] ?? $state) : 'Pending')
                        ->badge()
                        ->color(fn (?string $state): string => match ($state) {
                            'with_pay' => 'success',
                            'without_pay' => 'warning',
                            'disapproved' => 'danger',
                            default => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('recommendationSignatory.full_name')
                        ->label('Recommending Officer')
                        ->placeholder('—'),
                    Infolists\Components\TextEntry::make('approvalSignatory.full_name')
                        ->label('Approving Official')
                        ->placeholder('—'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('leave_application_no')
                    ->label('Application No.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('leave_type')
                    ->label('Leave Type')
                    ->formatStateUsing(fn (string $state): string => LeaveApplication::LEAVE_TYPE_SELECT[$state] ?? $state)
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('days_applied_number')
                    ->label('Days')
                    ->suffix(' day(s)')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('date_of_filing')
                    ->label('Date Filed')
                    ->date('M d, Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('approval_status')
                    ->label('Status')
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? (LeaveApplication::APPROVAL_STATUS_SELECT[$state] ?? $state)
                        : 'Pending')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'with_pay' => 'success',
                        'without_pay' => 'warning',
                        'disapproved' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('date_of_filing', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (LeaveApplication $record): bool => $record->isPendingHrAction()),
                Tables\Actions\DeleteAction::make()
                    ->label('Withdraw')
                    ->visible(fn (LeaveApplication $record): bool => $record->isPendingHrAction()),
            ])
            ->emptyStateHeading('No leave applications yet')
            ->emptyStateDescription('Use "Apply for Leave" to file your first application.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveApplications::route('/'),
            'create' => Pages\CreateLeaveApplication::route('/create'),
            'view' => Pages\ViewLeaveApplication::route('/{record}'),
            'edit' => Pages\EditLeaveApplication::route('/{record}/edit'),
        ];
    }
}
