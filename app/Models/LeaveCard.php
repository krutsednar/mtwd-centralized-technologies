<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LeaveCard extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public const CATEGORY_SELECT = [
        // Non-leave categories
        'earned_leave'        => 'Earned Leave',
        'tardiness_undertime' => 'Tardiness / Undertime',
        'excess_breaktime'    => 'Excess Breaktime',
        'personal_pass_slip'  => 'Personal Pass Slip',
        'adjustments'         => 'Adjustments',
        // Leave types (from LeaveApplication)
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

    protected $fillable = [
        'profile_id',
        'date_applied',
        'ref_code',
        'category',
        'period_covered',
        'duration',
        'vl_earned',
        'vl_with_pay',
        'vl_without_pay',
        'sl_earned',
        'sl_with_pay',
        'sl_without_pay',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date_applied'        => 'date',
            'vl_earned'           => 'decimal:6',
            'vl_with_pay'         => 'decimal:6',
            'vl_without_pay'      => 'decimal:6',
            'sl_earned'           => 'decimal:6',
            'sl_with_pay'         => 'decimal:6',
            'sl_without_pay'      => 'decimal:6',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
