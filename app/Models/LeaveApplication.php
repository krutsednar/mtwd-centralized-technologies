<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LeaveApplication extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public const LEAVE_TYPE_SELECT = [
        'vacation' => 'Vacation Leave',
        'mandatory_forced' => 'Mandatory / Forced Leave',
        'sick' => 'Sick Leave',
        'maternity' => 'Maternity Leave',
        'paternity' => 'Paternity Leave',
        'special_privilege' => 'Special Privilege Leave',
        'solo_parent' => 'Solo Parent Leave',
        'study' => 'Study Leave',
        'vawc' => 'VAWC Leave',
        'rehabilitation' => 'Rehabilitation Privilege',
        'special_women' => 'Special Leave Benefits for Women',
        'emergency_calamity' => 'Emergency / Calamity Leave',
        'adoption' => 'Adoption Leave',
        'wellness' => 'Wellness Leave',
        'others' => 'Others',
    ];

    /** Wellness Leave (MTWD-specific) — annual cap and consecutive-day cap. */
    public const WELLNESS_MAX_DAYS_PER_YEAR = 5;

    public const WELLNESS_MAX_CONSECUTIVE_WORKING_DAYS = 3;

    /**
     * Applicant positions/designations whose Leave Application 7.C is approved by
     * the General Manager. Everyone else routes to the configured designated
     * signatory. Matched case-insensitively as a substring of the position.
     *
     * @var list<string>
     */
    public const GM_APPROVAL_DESIGNATIONS = [
        'Division Manager',
        'Acting Division Manager',
        'OIC Division Manager',
        'Department Manager',
        'Acting Department Manager',
        'OIC Department Manager',
        'Assistant General Manager',
        'Acting Assistant General Manager',
        'OIC Assistant General Manager',
    ];

    public const LOCATION_SELECT = [
        'within_philippines' => 'Within the Philippines',
        'abroad' => 'Abroad',
    ];

    public const SICK_LEAVE_SELECT = [
        'in_hospital' => 'In Hospital',
        'out_patient' => 'Out Patient',
    ];

    public const STUDY_LEAVE_SELECT = [
        'completion_masters' => 'Completion of Master\'s Degree',
        'bar_board_review' => 'BAR / Board Examination Review',
        'other' => 'Other',
    ];

    public const OTHER_PURPOSE_SELECT = [
        'monetization' => 'Monetization of Leave Credits',
        'terminal_leave' => 'Terminal Leave',
    ];

    /**
     * Filing rule + documentary requirements per leave type, from the
     * "Instructions and Requirements" on page 2 of CS Form No. 6 (Revised 2020).
     *
     * @var array<string, array{filing: string, documents: list<string>}>
     */
    public const LEAVE_REQUIREMENTS = [
        'vacation' => [
            'filing' => 'File at least five (5) days in advance whenever possible. Indicate location (within the Philippines / abroad) for travel authority and clearance.',
            'documents' => [],
        ],
        'mandatory_forced' => [
            'filing' => 'The annual five-day vacation leave is forfeited if not taken during the year.',
            'documents' => [],
        ],
        'sick' => [
            'filing' => 'File immediately upon return. If filed in advance or exceeding five (5) days, a medical certificate is required.',
            'documents' => ['Medical certificate (when filed in advance or over 5 days); an affidavit if no medical consultation was availed of.'],
        ],
        'maternity' => [
            'filing' => '105 days.',
            'documents' => [
                "Proof of pregnancy (e.g. ultrasound or doctor's certificate with expected date of delivery).",
                'Accomplished Notice of Allocation of Maternity Leave Credits (CS Form No. 6a), if needed.',
            ],
        ],
        'paternity' => [
            'filing' => '7 days.',
            'documents' => ["Proof of child's delivery (birth certificate, medical certificate, and marriage contract)."],
        ],
        'special_privilege' => [
            'filing' => 'File/approve at least one (1) week prior to availment, except in emergencies (3 days). Indicate location (within the Philippines / abroad) for travel authority and clearance.',
            'documents' => [],
        ],
        'solo_parent' => [
            'filing' => 'File at least five (5) days in advance whenever possible. 7 days.',
            'documents' => ['Updated Solo Parent Identification Card.'],
        ],
        'study' => [
            'filing' => 'Up to 6 months.',
            'documents' => [
                "Agency's internal requirements, if any.",
                'Contract between the agency head (or authorized representative) and the employee.',
            ],
        ],
        'vawc' => [
            'filing' => 'File in advance or immediately upon return. 10 days.',
            'documents' => ['One of: BPO (barangay); TPO/PPO (court); a Punong Barangay/Kagawad/Prosecutor/Clerk of Court certification; or a police report plus a medical certificate.'],
        ],
        'rehabilitation' => [
            'filing' => 'File within one (1) week from the accident (unless a longer period is warranted). Up to 6 months.',
            'documents' => [
                'Letter request supported by relevant reports (e.g. police report).',
                'Medical certificate on the injuries, treatment, and need for rest/recuperation/rehabilitation.',
                'Written concurrence of a government physician if the attending physician is a private practitioner.',
            ],
        ],
        'special_women' => [
            'filing' => 'File at least five (5) days prior to the gynecological surgery (or immediately upon return in emergencies). Up to 2 months.',
            'documents' => ['Medical certificate by the attending surgeon with clinical summary, histopathological report, operative technique, duration of surgery, and estimated recuperation period.'],
        ],
        'emergency_calamity' => [
            'filing' => "Maximum of five (5) straight or staggered working days within thirty (30) days of the calamity; once a year. The head of office verifies the employee's eligibility (residence within the declared calamity area, etc.).",
            'documents' => [],
        ],
        'adoption' => [
            'filing' => '',
            'documents' => ['Authenticated copy of the Pre-Adoptive Placement Authority issued by the DSWD.'],
        ],
        'wellness' => [
            'filing' => 'MTWD: cannot fall on a Monday; maximum 5 days per calendar year; maximum 3 consecutive working days.',
            'documents' => [],
        ],
        'others' => [
            'filing' => '',
            'documents' => [],
        ],
    ];

    /**
     * Documentary requirements for the "Other purpose" sub-types (6.B).
     *
     * @var array<string, list<string>>
     */
    public const OTHER_PURPOSE_REQUIREMENTS = [
        'monetization' => ['Letter request to the head of agency stating valid and justifiable reasons (for monetization of 50% or more of accumulated leave credits).'],
        'terminal_leave' => ["Proof of the employee's resignation, retirement, or separation from the service."],
    ];

    /** Leave types that use a date range (from/to) instead of individual inclusive dates. */
    public const RANGE_BASED_LEAVE_TYPES = [
        'maternity',
        'study',
        'rehabilitation',
        'special_women',
    ];

    public const COMMUTATION_SELECT = [
        'requested' => 'Requested',
        'not_requested' => 'Not Requested',
    ];

    public const RECOMMENDATION_SELECT = [
        'for_approval' => 'For Approval',
        'for_disapproval' => 'For Disapproval',
    ];

    public const APPROVAL_STATUS_SELECT = [
        'with_pay' => 'With Pay',
        'without_pay' => 'Without Pay',
        'others' => 'Others (specify)',
        'disapproved' => 'Disapproved',
    ];

    protected $fillable = [
        'leave_application_no',
        'profile_id',
        'date_of_filing',
        'position',
        'salary',
        'leave_type',
        'details_location',
        'details_location_specific',
        'details_sick_leave',
        'details_sick_leave_specific',
        'details_special_benefits_women',
        'details_study_leave',
        'details_other_purpose',
        'days_applied_number',
        'from',
        'to',
        'commutation',
        'supporting_documents',
        'certification_leave_credits',
        'recommendation',
        'recommendation_disapproval_reason',
        'approval_status',
        'approval_others_specify',
        'authorized_officer_certification',
        'authorized_official_approval',
        'certification_hr_staff_profile_id',
        'certification_hr_chief_profile_id',
        'recommendation_signatory_profile_id',
        'approval_signatory_profile_id',
    ];

    protected function casts(): array
    {
        return [
            'date_of_filing' => 'date',
            'from' => 'date',
            'to' => 'date',
            'certification_leave_credits' => 'array',
            'supporting_documents' => 'array',
            'salary' => 'decimal:2',
            'days_applied_number' => 'decimal:1',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (LeaveApplication $model): void {
            $count = static::whereYear('created_at', Carbon::now()->year)->count() + 1;
            $model->leave_application_no = 'LA'
                .Carbon::now()->format('Ym')
                .'-'
                .str_pad($count, 4, '0', STR_PAD_LEFT);
        });
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function inclusiveDates(): HasMany
    {
        return $this->hasMany(LeaveInclusiveDates::class, 'leave_application_id');
    }

    /**
     * Medical certificate required for sick leave that exceeds 5 days OR is filed
     * in advance (before the leave is taken), per CS Form No. 6 page-2 instructions.
     */
    public function getRequiresMedicalCertificateAttribute(): bool
    {
        if ($this->leave_type !== 'sick') {
            return false;
        }

        if ((float) $this->days_applied_number > 5) {
            return true;
        }

        $start = $this->inclusiveDates()->min('date') ?? $this->from;

        return $start !== null
            && $this->date_of_filing !== null
            && Carbon::parse($start)->startOfDay()->greaterThan(Carbon::parse($this->date_of_filing)->startOfDay());
    }

    /**
     * Whether the application requires a clearance from money/property/work-related
     * accountabilities — leave of 30+ calendar days or terminal leave (CS Form No.
     * 6 page-2 footnote; CSC MC No. 2, s. 1985).
     */
    public function getRequiresClearanceAttribute(): bool
    {
        return (float) $this->days_applied_number >= 30
            || $this->details_other_purpose === 'terminal_leave';
    }

    /**
     * Filing rule + documentary requirements for a leave type (and its 6.B "other
     * purpose", when applicable), from CS Form No. 6 page 2.
     *
     * @return array{filing: string, documents: list<string>}
     */
    public static function requirementsFor(?string $leaveType, ?string $otherPurpose = null): array
    {
        $base = static::LEAVE_REQUIREMENTS[$leaveType] ?? ['filing' => '', 'documents' => []];

        if ($leaveType === 'others' && $otherPurpose && isset(static::OTHER_PURPOSE_REQUIREMENTS[$otherPurpose])) {
            $base['documents'] = array_merge($base['documents'], static::OTHER_PURPOSE_REQUIREMENTS[$otherPurpose]);
        }

        return $base;
    }

    /**
     * Whether the selected leave type (and 6.B other purpose) has documentary
     * attachment requirements. Drives the visibility of the Supporting Documents
     * upload — it stays optional even when shown.
     */
    public static function requiresSupportingDocuments(?string $leaveType, ?string $otherPurpose = null): bool
    {
        return ! empty(static::requirementsFor($leaveType, $otherPurpose)['documents']);
    }

    /**
     * Whether HR has already acted on the application (certified credits,
     * recommended, or approved). Employees may only edit/withdraw while this is
     * false (i.e. still pending in the HRIS panel).
     */
    public function isHrActioned(): bool
    {
        return filled($this->recommendation)
            || filled($this->approval_status)
            || ! empty(array_filter($this->certification_leave_credits ?? []));
    }

    public function isPendingHrAction(): bool
    {
        return ! $this->isHrActioned();
    }

    public function getLeaveTypeLabelAttribute(): string
    {
        return static::LEAVE_TYPE_SELECT[$this->leave_type] ?? $this->leave_type;
    }

    // ── Signatory relations (7.A / 7.B / 7.C) ────────────────────────────────

    public function certificationHrStaff(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'certification_hr_staff_profile_id');
    }

    public function certificationHrChief(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'certification_hr_chief_profile_id');
    }

    public function recommendationSignatory(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'recommendation_signatory_profile_id');
    }

    public function approvalSignatory(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'approval_signatory_profile_id');
    }

    /**
     * Resolve the four "Details of Action" signatories for an applicant.
     *
     * Org-unit heads come from Supervisor Management (Division head / OIC, via
     * Division::getActiveSignatory()); the two designated individuals come from
     * HRIS Configuration (HrSetting). Returns a map of column => profile id (or
     * null), suitable for filling/overriding on the application.
     *
     * @return array<string, int|null>
     */
    public static function resolveSignatories(Profile $profile, ?string $position = null): array
    {
        // 7.A signatory 1 — designated HR employee who manages leave (config).
        $hrStaffId = HrSetting::get('leave.hr_leave_administrator_profile_id');

        // 7.A signatory 2 — HR Division Chief: head/OIC of the configured HR unit.
        $hrChiefId = null;
        if ($hrDivisionId = HrSetting::get('leave.hr_division_id')) {
            $hrChiefId = Division::find($hrDivisionId)?->getActiveSignatory()?->id;
        }

        // 7.B — the applicant's own division head/OIC (Supervisor Management).
        $recommendationId = $profile->division?->getActiveSignatory()?->id;

        // 7.C — General Manager (OGM head/OIC) for managerial applicants, else the
        // configured designated approving signatory.
        if (static::isManagerialPosition($position)) {
            $approvalId = Division::where('type', Division::TYPE_OGM)->first()?->getActiveSignatory()?->id;
        } else {
            $approvalId = HrSetting::get('leave.designated_approver_profile_id');
        }

        return [
            'certification_hr_staff_profile_id' => $hrStaffId ? (int) $hrStaffId : null,
            'certification_hr_chief_profile_id' => $hrChiefId,
            'recommendation_signatory_profile_id' => $recommendationId,
            'approval_signatory_profile_id' => $approvalId ? (int) $approvalId : null,
        ];
    }

    /**
     * Whether a position string routes 7.C approval to the General Manager.
     * Matched case-insensitively as a substring against GM_APPROVAL_DESIGNATIONS.
     */
    public static function isManagerialPosition(?string $position): bool
    {
        if (blank($position)) {
            return false;
        }

        $normalized = Str::squish(Str::lower($position));

        foreach (static::GM_APPROVAL_DESIGNATIONS as $designation) {
            if (str_contains($normalized, Str::lower($designation))) {
                return true;
            }
        }

        return false;
    }

    /**
     * The role label for the applicant's division signatory (7.B), reflecting
     * whether an OIC is currently acting.
     */
    public static function divisionSignatoryRole(?Division $division): string
    {
        if ($division && $division->oic_active && $division->oic_profile_id) {
            return 'Officer-in-Charge';
        }

        return 'Division Chief';
    }

    // ── Wellness Leave rules (pure, unit-testable) ───────────────────────────

    /**
     * Validation errors for a set of Wellness Leave dates. Empty array = valid.
     *
     * @param  list<string|\DateTimeInterface>  $dates  the inclusive dates applied for
     * @param  int  $alreadyUsedThisYear  wellness days already taken this calendar year
     * @param  list<string>  $nonWorkingDates  Y-m-d holiday strings (Holiday::nonWorkingDates())
     * @return list<string>
     */
    public static function wellnessValidationErrors(array $dates, int $alreadyUsedThisYear = 0, array $nonWorkingDates = []): array
    {
        $parsed = collect($dates)
            ->filter()
            ->map(fn ($d) => Carbon::parse($d)->startOfDay())
            ->unique(fn (Carbon $d) => $d->toDateString())
            ->sortBy(fn (Carbon $d) => $d->timestamp)
            ->values();

        $errors = [];

        foreach ($parsed as $date) {
            if ($date->isMonday()) {
                $errors[] = 'Wellness Leave cannot be filed on a Monday ('.$date->format('M d, Y').').';
                break;
            }
        }

        if (($alreadyUsedThisYear + $parsed->count()) > static::WELLNESS_MAX_DAYS_PER_YEAR) {
            $errors[] = 'Wellness Leave is limited to '.static::WELLNESS_MAX_DAYS_PER_YEAR
                .' day(s) per calendar year ('.$alreadyUsedThisYear.' already used this year).';
        }

        if (static::maxConsecutiveWorkingDays($parsed->all(), $nonWorkingDates) > static::WELLNESS_MAX_CONSECUTIVE_WORKING_DAYS) {
            $errors[] = 'Wellness Leave cannot exceed '.static::WELLNESS_MAX_CONSECUTIVE_WORKING_DAYS.' consecutive working days.';
        }

        return $errors;
    }

    /**
     * Longest run of consecutive working days among the given dates. Weekends and
     * holidays (in $nonWorkingDates) break a run.
     *
     * @param  list<string|\DateTimeInterface>  $dates
     * @param  list<string>  $nonWorkingDates  Y-m-d strings
     */
    public static function maxConsecutiveWorkingDays(array $dates, array $nonWorkingDates = []): int
    {
        $parsed = collect($dates)
            ->filter()
            ->map(fn ($d) => Carbon::parse($d)->startOfDay())
            ->unique(fn (Carbon $d) => $d->toDateString())
            ->sortBy(fn (Carbon $d) => $d->timestamp)
            ->values();

        if ($parsed->isEmpty()) {
            return 0;
        }

        $holidays = array_flip($nonWorkingDates);
        $isWorkingDay = fn (Carbon $d): bool => ! $d->isWeekend() && ! isset($holidays[$d->toDateString()]);
        $nextWorkingDay = function (Carbon $d) use ($isWorkingDay): Carbon {
            $next = $d->copy()->addDay();
            while (! $isWorkingDay($next)) {
                $next->addDay();
            }

            return $next;
        };

        $max = 1;
        $run = 1;

        for ($i = 1; $i < $parsed->count(); $i++) {
            if ($parsed[$i]->toDateString() === $nextWorkingDay($parsed[$i - 1])->toDateString()) {
                $run++;
            } else {
                $run = 1;
            }
            $max = max($max, $run);
        }

        return $max;
    }

    /**
     * Wellness Leave days an employee has already taken in a calendar year
     * (counts inclusive dates), optionally excluding one application (when editing).
     */
    public static function wellnessDaysUsedInYear(int $profileId, int $year, ?int $excludeId = null): int
    {
        return LeaveInclusiveDates::query()
            ->whereYear('date', $year)
            ->whereHas('leaveApplication', function ($query) use ($profileId, $excludeId): void {
                $query->where('profile_id', $profileId)
                    ->where('leave_type', 'wellness')
                    ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));
            })
            ->count();
    }
}
