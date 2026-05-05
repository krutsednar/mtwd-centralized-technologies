<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        'vacation'            => 'Vacation Leave',
        'mandatory_forced'    => 'Mandatory / Forced Leave',
        'sick'                => 'Sick Leave',
        'maternity'           => 'Maternity Leave',
        'paternity'           => 'Paternity Leave',
        'special_privilege'   => 'Special Privilege Leave',
        'solo_parent'         => 'Solo Parent Leave',
        'study'               => 'Study Leave',
        'vawc'                => 'VAWC Leave',
        'rehabilitation'      => 'Rehabilitation Privilege',
        'special_women'       => 'Special Leave Benefits for Women',
        'emergency_calamity'  => 'Emergency / Calamity Leave',
        'adoption'            => 'Adoption Leave',
        'others'              => 'Others',
    ];

    public const LOCATION_SELECT = [
        'within_philippines' => 'Within the Philippines',
        'abroad'             => 'Abroad',
    ];

    public const SICK_LEAVE_SELECT = [
        'in_hospital'  => 'In Hospital',
        'out_patient'  => 'Out Patient',
    ];

    public const STUDY_LEAVE_SELECT = [
        'completion_masters' => 'Completion of Master\'s Degree',
        'bar_board_review'   => 'BAR / Board Examination Review',
        'other'              => 'Other',
    ];

    public const OTHER_PURPOSE_SELECT = [
        'monetization'   => 'Monetization of Leave Credits',
        'terminal_leave' => 'Terminal Leave',
    ];

    /** Leave types that use a date range (from/to) instead of individual inclusive dates. */
    public const RANGE_BASED_LEAVE_TYPES = [
        'maternity',
        'study',
        'rehabilitation',
        'special_women',
    ];

    public const COMMUTATION_SELECT = [
        'requested'     => 'Requested',
        'not_requested' => 'Not Requested',
    ];

    public const RECOMMENDATION_SELECT = [
        'for_approval'     => 'For Approval',
        'for_disapproval'  => 'For Disapproval',
    ];

    public const APPROVAL_STATUS_SELECT = [
        'with_pay'    => 'With Pay',
        'without_pay' => 'Without Pay',
        'others'      => 'Others (specify)',
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
        'certification_leave_credits',
        'recommendation',
        'recommendation_disapproval_reason',
        'approval_status',
        'approval_others_specify',
        'authorized_officer_certification',
        'authorized_official_approval',
    ];

    protected function casts(): array
    {
        return [
            'date_of_filing'              => 'date',
            'from'                        => 'date',
            'to'                          => 'date',
            'certification_leave_credits' => 'array',
            'salary'                      => 'decimal:2',
            'days_applied_number'         => 'decimal:1',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (LeaveApplication $model): void {
            $count = static::whereYear('created_at', Carbon::now()->year)->count() + 1;
            $model->leave_application_no = 'LA'
                . Carbon::now()->format('Ym')
                . '-'
                . str_pad($count, 4, '0', STR_PAD_LEFT);
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

    /** Medical certificate required when sick leave exceeds 5 days. */
    public function getRequiresMedicalCertificateAttribute(): bool
    {
        return $this->leave_type === 'sick' && $this->days_applied_number > 5;
    }

    public function getLeaveTypeLabelAttribute(): string
    {
        return static::LEAVE_TYPE_SELECT[$this->leave_type] ?? $this->leave_type;
    }
}
