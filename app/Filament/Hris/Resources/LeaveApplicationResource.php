<?php

namespace App\Filament\Hris\Resources;

use App\Filament\Hris\Resources\LeaveApplicationResource\Pages;
use App\Models\Holiday;
use App\Models\LeaveApplication;
use App\Models\Profile;
use App\Models\ServiceRecord;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

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
                ->schema(static::applicationDetailsSchema(withSignatoryResolution: true)),

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

                    // 7.A Signatories — two (auto-resolved, overridable)
                    Forms\Components\Fieldset::make('7.A — Certifying Signatories')
                        ->schema([
                            Forms\Components\Select::make('certification_hr_staff_profile_id')
                                ->label('Designated HR Employee (Leave)')
                                ->options(static::profileOptions())
                                ->searchable()
                                ->nullable()
                                ->helperText('Auto-filled from HRIS Configuration — override if needed.'),
                            Forms\Components\Select::make('certification_hr_chief_profile_id')
                                ->label('HR Division Chief')
                                ->options(static::profileOptions())
                                ->searchable()
                                ->nullable()
                                ->helperText('Head/OIC of the HR division (Supervisor Management).'),
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
                            Forms\Components\Select::make('recommendation_signatory_profile_id')
                                ->label('Recommending Officer — Division Head / OIC')
                                ->options(static::profileOptions())
                                ->searchable()
                                ->nullable()
                                ->helperText("The applicant's division head or OIC (Supervisor Management).")
                                ->columnSpanFull(),
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
                            Forms\Components\Select::make('approval_signatory_profile_id')
                                ->label('Approving Official — General Manager / Designated Signatory')
                                ->options(static::profileOptions())
                                ->searchable()
                                ->nullable()
                                ->helperText('General Manager for managerial applicants; otherwise the designated approver from HRIS Configuration.')
                                ->columnSpanFull(),
                        ])
                        ->columns(1),
                ]),
        ];
    }

    /**
     * Sections 1–6.D of CS Form No. 6 — the employee-fillable portion. Shared by
     * the HRIS resource (wizard step 1) and the Home self-service resource so the
     * two never drift. When $withSignatoryResolution is true, selecting an
     * employee also pre-fills the 7.A/7.B/7.C signatories (HRIS only).
     *
     * @return array<int, \Filament\Forms\Components\Component>
     */
    public static function applicationDetailsSchema(bool $withSignatoryResolution = false, ?int $lockedProfileId = null): array
    {
        // Home self-service locks the whole employee header (employee, name, date,
        // position, department, salary) — the employee only fills 6.A–6.D. HR keeps
        // these editable in the HRIS panel.
        $locked = $lockedProfileId !== null;

        return [
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\Select::make('profile_id')
                    ->label('Employee')
                    ->options(static::profileOptions())
                    ->searchable()
                    ->required($lockedProfileId === null)
                    ->disabled($lockedProfileId !== null)
                    ->dehydrated()
                    ->default($lockedProfileId)
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
                    ->afterStateUpdated(function (Get $get, Set $set, ?int $state) use ($withSignatoryResolution): void {
                        if (! $state) {
                            return;
                        }
                        $profile = Profile::with('division')->find($state);
                        if (! $profile) {
                            return;
                        }
                        $set('_name', $profile->full_name);
                        $set('_department', $profile->division?->name ?? '');

                        $position = null;
                        $latestServiceRecord = ServiceRecord::where('profile_id', $state)
                            ->orderByDesc('from')
                            ->first();
                        if ($latestServiceRecord) {
                            $position = $latestServiceRecord->position;
                            $set('position', $latestServiceRecord->position);
                            $set('salary', $latestServiceRecord->salary);
                        }

                        if ($withSignatoryResolution) {
                            foreach (LeaveApplication::resolveSignatories($profile, $position) as $field => $value) {
                                $set($field, $value);
                            }
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
                    ->disabled($locked)
                    ->dehydrated()
                    ->required($lockedProfileId === null),
            ]),

            Forms\Components\Grid::make(3)->schema([
                Forms\Components\TextInput::make('position')
                    ->label('Position')
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) use ($withSignatoryResolution): void {
                        if (! $withSignatoryResolution) {
                            return;
                        }
                        $profile = Profile::with('division')->find($get('profile_id'));
                        if ($profile) {
                            $resolved = LeaveApplication::resolveSignatories($profile, $state);
                            $set('approval_signatory_profile_id', $resolved['approval_signatory_profile_id']);
                        }
                    })
                    ->disabled($locked)
                    ->dehydrated()
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
                    ->disabled($locked)
                    ->dehydrated()
                    ->nullable(),
            ]),

            Forms\Components\Fieldset::make('6. Details of Application')
                ->schema([
                    Forms\Components\Select::make('leave_type')
                        ->label('Type of Leave')
                        ->options(LeaveApplication::LEAVE_TYPE_SELECT)
                        ->required()
                        ->live()
                        ->helperText(fn (Get $get): ?string => $get('leave_type') === 'wellness'
                            ? 'Wellness Leave: no Mondays · max 5 days/year · max 3 consecutive working days.'
                            : null)
                        ->columnSpanFull(),

                    // Filing rule & documentary requirements for the selected type (CS Form No. 6, page 2)
                    Forms\Components\Placeholder::make('_requirements')
                        ->label('Filing Rule & Documentary Requirements')
                        ->visible(fn (Get $get): bool => filled($get('leave_type')))
                        ->content(function (Get $get): HtmlString {
                            $req = LeaveApplication::requirementsFor($get('leave_type'), $get('details_other_purpose'));

                            $html = '';
                            if ($req['filing'] !== '') {
                                $html .= '<p>'.e($req['filing']).'</p>';
                            }
                            if (! empty($req['documents'])) {
                                $html .= '<p class="mt-1 font-medium">Attach:</p><ul class="list-disc ms-5">';
                                foreach ($req['documents'] as $doc) {
                                    $html .= '<li>'.e($doc).'</li>';
                                }
                                $html .= '</ul>';
                            }
                            if ($html === '') {
                                $html = '<p class="text-gray-500">No specific documentary requirement.</p>';
                            }

                            return new HtmlString('<div class="text-sm text-gray-700 dark:text-gray-300 space-y-1">'.$html.'</div>');
                        })
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
                                ->inline()
                                ->live(),
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
                                            '1' => 'Whole Day (1)',
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

                    // 6.C — From / To Date Range — for maternity, study, rehabilitation, special women
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

                    // Documentary requirements (CS Form No. 6, page 2) — shown only for
                    // leave types that need attachments; never required.
                    Forms\Components\FileUpload::make('supporting_documents')
                        ->label('Supporting Documents')
                        ->visible(fn (Get $get): bool => LeaveApplication::requiresSupportingDocuments($get('leave_type'), $get('details_other_purpose')))
                        ->multiple()
                        ->reorderable()
                        ->openable()
                        ->downloadable()
                        ->disk('public')
                        ->directory('leave_documents')
                        ->maxSize(20480)
                        ->helperText('Attach the documentary requirements for the selected leave type (PDF or image).')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ];
    }

    private static function recomputeDaysFromRange(Get $get, Set $set): void
    {
        $from = $get('from');
        $to = $get('to');

        if ($from && $to) {
            $days = Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1;
            $set('days_applied_number', max(1, $days));
        }
    }

    /**
     * Employee select options (memoized per request).
     *
     * @return array<int, string>
     */
    public static function profileOptions(): array
    {
        static $options = null;

        return $options ??= Profile::query()
            ->orderBy('surname')
            ->get()
            ->mapWithKeys(fn (Profile $p) => [$p->id => $p->employee_number.' '.$p->full_name])
            ->toArray();
    }

    /**
     * Wellness Leave validation errors for a raw form state. Empty when the leave
     * type is not "wellness" or the application is valid. Shared by both panels.
     *
     * @param  array<string, mixed>  $formState
     * @return list<string>
     */
    public static function wellnessErrors(array $formState, ?int $excludeId = null): array
    {
        if (($formState['leave_type'] ?? null) !== 'wellness') {
            return [];
        }

        $dates = collect($formState['inclusiveDates'] ?? [])
            ->pluck('date')
            ->filter()
            ->values()
            ->all();

        $profileId = $formState['profile_id'] ?? null;
        $year = ! empty($dates) ? Carbon::parse($dates[0])->year : now()->year;

        $alreadyUsed = $profileId
            ? LeaveApplication::wellnessDaysUsedInYear((int) $profileId, $year, $excludeId)
            : 0;

        return LeaveApplication::wellnessValidationErrors($dates, $alreadyUsed, Holiday::nonWorkingDates());
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
                        'with_pay' => 'success',
                        'without_pay' => 'warning',
                        'disapproved' => 'danger',
                        'others' => 'gray',
                        default => 'gray',
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
                                        $la->id => ($la->leave_application_no ?? '—').' — '.
                                            ($la->profile?->full_name ?? '—').' — '.
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
            'index' => Pages\ListLeaveApplications::route('/'),
            'create' => Pages\CreateLeaveApplication::route('/create'),
            'edit' => Pages\EditLeaveApplication::route('/{record}/edit'),
        ];
    }
}
