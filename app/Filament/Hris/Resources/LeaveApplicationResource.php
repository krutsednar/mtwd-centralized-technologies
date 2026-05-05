<?php

namespace App\Filament\Hris\Resources;

use App\Filament\Hris\Resources\LeaveApplicationResource\Pages;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\Profile;
use App\Models\ServiceRecord;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeaveApplicationResource extends Resource
{
    protected static ?string $model = LeaveApplication::class;

    protected static ?string $navigationGroup = 'Leave/CTO Management';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Leave Applications';

    protected static ?string $modelLabel = 'Leave Application';

    protected static ?string $pluralModelLabel = 'Leave Applications';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Wizard::make(static::getFormSteps())
                ->columnSpanFull()
                ->skippable(),
        ]);
    }

    public static function getFormSteps(): array
    {
        return [
            Forms\Components\Wizard\Step::make('Application Details')
                ->icon('heroicon-o-document-text')
                ->schema([
                    // Application No — always visible, never editable
                    // Forms\Components\TextInput::make('leave_application_no')
                    //     ->label('Application No.')
                    //     ->disabled()
                    //     ->dehydrated(false)
                    //     ->placeholder('(auto-generated on save)'),

                    // Header info
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Select::make('profile_id')
                            ->label('Employee')
                            ->options(
                                Profile::query()
                                    ->get()
                                    ->mapWithKeys(fn (Profile $p) => [$p->id => $p->employee_number . ' ' . $p->full_name])
                            )
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateHydrated(function (Set $set, ?int $state): void {
                                if (! $state) {
                                    return;
                                }
                                $profile = Profile::with('division')->find($state);
                                if ($profile) {
                                    $set('_name', $profile->full_name);
                                    $set('_department', $profile->division?->name ?? '');
                                }
                            })
                            ->afterStateUpdated(function (Get $get, Set $set, ?int $state): void {
                                if (! $state) {
                                    return;
                                }
                                $profile = Profile::with('division')->find($state);
                                if ($profile) {
                                    $set('_name', $profile->full_name);
                                    $set('_department', $profile->division?->name ?? '');
                                }
                                $latestServiceRecord = ServiceRecord::where('profile_id', $state)
                                    ->orderByDesc('from')
                                    ->first();
                                if ($latestServiceRecord) {
                                    $set('position', $latestServiceRecord->position);
                                    $set('salary', $latestServiceRecord->salary);
                                }
                            }),

                        Forms\Components\TextInput::make('_name')
                            ->label('Name')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('(auto-filled from employee)'),

                        Forms\Components\DatePicker::make('date_of_filing')
                            ->label('Date of Filing')
                            ->default(now())
                            ->required(),
                    ]),

                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('position')
                            ->label('Position')
                            ->nullable(),

                        Forms\Components\TextInput::make('_department')
                            ->label('Department / Office')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('(auto-filled from employee)'),

                        Forms\Components\TextInput::make('salary')
                            ->label('Salary')
                            ->numeric()
                            ->prefix('₱')
                            ->nullable(),
                    ]),

                    Forms\Components\Fieldset::make('6. Details of Application')
                        ->schema([
                            // Leave type
                            Forms\Components\Select::make('leave_type')
                                ->label('Type of Leave')
                                ->options(LeaveApplication::LEAVE_TYPE_SELECT)
                                ->required()
                                ->live()
                                ->columnSpanFull(),

                            // 6.A — Location (vacation / special privilege / solo parent / adoption / etc.)
                            Forms\Components\Fieldset::make('6.A — In case of Vacation / Special Privilege Leave')
                                ->schema([
                                    Forms\Components\Radio::make('details_location')
                                        ->label('Location')
                                        ->options(LeaveApplication::LOCATION_SELECT)
                                        ->inline()
                                        ->live(),
                                    Forms\Components\TextInput::make('details_location_specific')
                                        ->label('Specify')
                                        ->nullable()
                                        ->visible(fn (Get $get): bool => filled($get('details_location'))),
                                ])
                                ->visible(fn (Get $get): bool => in_array($get('leave_type'), [
                                    'vacation', 'special_privilege', 'solo_parent', 'adoption',
                                    'mandatory_forced', 'emergency_calamity',
                                ]))
                                ->columnSpanFull(),

                            // 6.B — Sick leave
                            Forms\Components\Fieldset::make('6.B — In case of Sick Leave')
                                ->schema([
                                    Forms\Components\Radio::make('details_sick_leave')
                                        ->label('Type')
                                        ->options(LeaveApplication::SICK_LEAVE_SELECT)
                                        ->inline(),
                                    Forms\Components\Textarea::make('details_sick_leave_specific')
                                        ->label('Illness / Injury / Condition')
                                        ->rows(2)
                                        ->nullable()
                                        ->columnSpanFull(),
                                ])
                                ->visible(fn (Get $get): bool => $get('leave_type') === 'sick')
                                ->columns(1)
                                ->columnSpanFull(),

                            // 6.B — Special Leave Benefits for Women
                            Forms\Components\Fieldset::make('6.B — Special Leave Benefits for Women')
                                ->schema([
                                    Forms\Components\Textarea::make('details_special_benefits_women')
                                        ->label('Illness / Injury (gynecological disorder)')
                                        ->rows(2)
                                        ->nullable()
                                        ->columnSpanFull(),
                                ])
                                ->visible(fn (Get $get): bool => $get('leave_type') === 'special_women')
                                ->columnSpanFull(),

                            // 6.B — Study Leave
                            Forms\Components\Fieldset::make('6.B — In case of Study Leave')
                                ->schema([
                                    Forms\Components\Radio::make('details_study_leave')
                                        ->label('Purpose')
                                        ->options(LeaveApplication::STUDY_LEAVE_SELECT)
                                        ->inline(),
                                ])
                                ->visible(fn (Get $get): bool => $get('leave_type') === 'study')
                                ->columnSpanFull(),

                            // 6.B — Other Purpose
                            Forms\Components\Fieldset::make('6.B — Other Purpose')
                                ->schema([
                                    Forms\Components\Radio::make('details_other_purpose')
                                        ->label('Type')
                                        ->options(LeaveApplication::OTHER_PURPOSE_SELECT)
                                        ->inline(),
                                ])
                                ->visible(fn (Get $get): bool => $get('leave_type') === 'others')
                                ->columnSpanFull(),

                            // 6.C — Inclusive Dates (Repeater) — for non-range leave types
                            Forms\Components\Fieldset::make('6.C — Inclusive Dates & Number of Working Days')
                                ->schema([
                                    Forms\Components\Repeater::make('inclusiveDates')
                                        ->relationship()
                                        ->label('Inclusive Dates')
                                        ->schema([
                                            Forms\Components\DatePicker::make('date')
                                                ->label('Date')
                                                ->required()
                                                ->native(false)
                                                ->disabledDates(Holiday::nonWorkingDates()),
                                            Forms\Components\Select::make('duration')
                                                ->label('Duration')
                                                ->options([
                                                    '1'   => 'Whole Day (1)',
                                                    '0.5' => 'Half Day (0.5)',
                                                ])
                                                ->default('1')
                                                ->required()
                                                ->disabled(fn (Get $get): bool => $get('../../leave_type') !== 'sick')
                                                ->formatStateUsing(fn (Get $get, $state) => $get('../../leave_type') !== 'sick' ? '1' : $state)
                                                ->dehydrated(),
                                        ])
                                        ->columns(2)
                                        ->addActionLabel('Add Date')
                                        ->reorderable(false)
                                        ->live()
                                        ->afterStateUpdated(function (Get $get, Set $set, ?array $state): void {
                                            $total = collect($state ?? [])
                                                ->sum(fn ($item) => (float) ($item['duration'] ?? 0));
                                            $set('days_applied_number', $total > 0 ? $total : null);
                                        })
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('days_applied_number')
                                        ->label('Total Days Applied')
                                        ->numeric()
                                        ->readOnly()
                                        ->suffix('day(s)')
                                        ->placeholder('(auto-computed from dates above)'),
                                ])
                                ->columns(1)
                                ->columnSpanFull()
                                ->visible(fn (Get $get): bool => ! in_array($get('leave_type'), LeaveApplication::RANGE_BASED_LEAVE_TYPES)),

                            // 6.C — From / To Date Range — for maternity, study, vawc, rehabilitation, special women, emergency
                            Forms\Components\Fieldset::make('6.C — Inclusive Dates & Number of Working Days')
                                ->schema([
                                    Forms\Components\DatePicker::make('from')
                                        ->label('From')
                                        ->required()
                                        ->native(false)
                                        ->live()
                                        ->afterStateUpdated(fn (Get $get, Set $set) => static::recomputeDaysFromRange($get, $set)),

                                    Forms\Components\DatePicker::make('to')
                                        ->label('To')
                                        ->required()
                                        ->native(false)
                                        ->live()
                                        ->afterStateUpdated(fn (Get $get, Set $set) => static::recomputeDaysFromRange($get, $set)),

                                    Forms\Components\TextInput::make('days_applied_number')
                                        ->label('Total Days Applied')
                                        ->numeric()
                                        ->readOnly()
                                        ->suffix('day(s)')
                                        ->placeholder('(auto-computed from dates above)'),
                                ])
                                ->columns(3)
                                ->columnSpanFull()
                                ->visible(fn (Get $get): bool => in_array($get('leave_type'), LeaveApplication::RANGE_BASED_LEAVE_TYPES)),

                            // 6.D — Commutation
                            Forms\Components\Fieldset::make('6.D — Commutation')
                                ->schema([
                                    Forms\Components\Radio::make('commutation')
                                        ->label('')
                                        ->options(LeaveApplication::COMMUTATION_SELECT)
                                        ->inline()
                                        ->default('requested')
                                        ->required(),
                                ])
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ]),

            Forms\Components\Wizard\Step::make('Action on Application')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    // 7.A Certification of Leave Credits
                    Forms\Components\Fieldset::make('7.A — Certification of Leave Credits')
                        ->schema([
                            Forms\Components\TextInput::make('certification_leave_credits.vacation_earned')
                                ->label('Total Vacation Leave Earned')
                                ->numeric()
                                ->nullable(),
                            Forms\Components\TextInput::make('certification_leave_credits.sick_earned')
                                ->label('Total Sick Leave Earned')
                                ->numeric()
                                ->nullable(),
                            Forms\Components\TextInput::make('certification_leave_credits.less_vacation')
                                ->label('Less This Application (Vacation)')
                                ->numeric()
                                ->nullable(),
                            Forms\Components\TextInput::make('certification_leave_credits.less_sick')
                                ->label('Less This Application (Sick)')
                                ->numeric()
                                ->nullable(),
                            Forms\Components\TextInput::make('certification_leave_credits.balance_vacation')
                                ->label('Balance (Vacation)')
                                ->numeric()
                                ->nullable(),
                            Forms\Components\TextInput::make('certification_leave_credits.balance_sick')
                                ->label('Balance (Sick)')
                                ->numeric()
                                ->nullable(),
                        ])
                        ->columns(2),

                    // 7.B Recommendation
                    Forms\Components\Fieldset::make('7.B — Recommendation')
                        ->schema([
                            Forms\Components\Radio::make('recommendation')
                                ->label('')
                                ->options(LeaveApplication::RECOMMENDATION_SELECT)
                                ->inline()
                                ->live()
                                ->nullable(),
                            Forms\Components\Textarea::make('recommendation_disapproval_reason')
                                ->label('Reason for Disapproval')
                                ->rows(2)
                                ->nullable()
                                ->columnSpanFull()
                                ->visible(fn (Get $get): bool => $get('recommendation') === 'for_disapproval'),
                        ])
                        ->columns(1),
                ]),

            Forms\Components\Wizard\Step::make('Final Approval')
                ->icon('heroicon-o-check-badge')
                ->schema([
                    // 7.C/D Approved / Disapproved
                    Forms\Components\Fieldset::make('7.C/D — Approved / Disapproved')
                        ->schema([
                            Forms\Components\Radio::make('approval_status')
                                ->label('')
                                ->options(LeaveApplication::APPROVAL_STATUS_SELECT)
                                ->inline()
                                ->live()
                                ->nullable(),
                            Forms\Components\TextInput::make('approval_others_specify')
                                ->label('Specify (Others)')
                                ->nullable()
                                ->visible(fn (Get $get): bool => $get('approval_status') === 'others'),
                        ])
                        ->columns(1),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('authorized_officer_certification')
                            ->label('Authorized Officer (Certification of Leave Credits)')
                            ->nullable(),
                        Forms\Components\TextInput::make('authorized_official_approval')
                            ->label('Authorized Official (Approval)')
                            ->nullable(),
                    ]),
                ]),
        ];
    }

    private static function recomputeDaysFromRange(Get $get, Set $set): void
    {
        $from = $get('from');
        $to   = $get('to');

        if ($from && $to) {
            $days = \Carbon\Carbon::parse($from)->diffInDays(\Carbon\Carbon::parse($to)) + 1;
            $set('days_applied_number', max(1, $days));
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('leave_application_no')
                    ->label('Application No.')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('profile.full_name')
                    ->label('Employee')
                    ->searchable(['profiles.surname', 'profiles.first_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('leave_type')
                    ->label('Leave Type')
                    ->formatStateUsing(fn (string $state): string => LeaveApplication::LEAVE_TYPE_SELECT[$state] ?? $state)
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('days_applied_number')
                    ->label('Duration')
                    ->getStateUsing(function (LeaveApplication $record): float {
                        $total = (float) $record->inclusiveDates()->sum('duration');
                        if ($total > 0) {
                            return $total;
                        }
                        if ($record->from && $record->to) {
                            return (float) ($record->from->diffInDays($record->to) + 1);
                        }

                        return 0;
                    })
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
                        : 'Pending'
                    )
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'with_pay'    => 'success',
                        'without_pay' => 'warning',
                        'disapproved' => 'danger',
                        'others'      => 'gray',
                        default       => 'gray',
                    }),
            ])
            ->defaultSort('date_of_filing', 'desc')
            ->headerActions([
                Tables\Actions\Action::make('print_leave_form')
                    ->label('Print Leave Form')
                    ->icon('heroicon-o-printer')
                    ->form([
                        Forms\Components\Select::make('leave_application_id')
                            ->label('Select Application')
                            ->options(
                                LeaveApplication::with('profile')
                                    ->get()
                                    ->mapWithKeys(fn (LeaveApplication $la) => [
                                        $la->id => ($la->leave_application_no ?? '—') . ' — ' .
                                            ($la->profile?->full_name ?? '—') . ' — ' .
                                            (LeaveApplication::LEAVE_TYPE_SELECT[$la->leave_type] ?? $la->leave_type),
                                    ])
                            )
                            ->searchable()
                            ->required(),
                    ])
                    ->modalSubmitActionLabel('Open Print Preview')
                    ->action(function (array $data, \Livewire\Component $livewire): void {
                        $livewire->redirect(
                            route('leave.print', $data['leave_application_id']),
                            navigate: false
                        );
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('leave_type')
                    ->label('Leave Type')
                    ->options(LeaveApplication::LEAVE_TYPE_SELECT),
                Tables\Filters\SelectFilter::make('approval_status')
                    ->label('Status')
                    ->options(LeaveApplication::APPROVAL_STATUS_SELECT),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLeaveApplications::route('/'),
            'create' => Pages\CreateLeaveApplication::route('/create'),
            'edit'   => Pages\EditLeaveApplication::route('/{record}/edit'),
        ];
    }
}
