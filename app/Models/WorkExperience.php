<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkExperience extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable();
    }

    public $table = 'work_experiences';

    protected $casts = [
        'government' => 'boolean',
    ];

    protected $dates = [
        'from',
        'to',
        'created_at',
        'updated_at',
        'deleted_at',
        'coe',
    ];

    protected $fillable = [
        'profile_id',
        'from',
        'to',
        'position_title',
        'agency',
        'monthly_salary',
        'salary_grade',
        'appointment_status',
        'government',
    ];

    public $filterable = [
        'id',
        'profile.employee_number',
        'from',
        'to',
        'position_title',
        'agency',
        'monthly_salary',
        'salary_grade',
        'appointment_status',
    ];

    public $orderable = [
        'id',
        'profile.employee_number',
        'from',
        'to',
        'position_title',
        'agency',
        'monthly_salary',
        'salary_grade',
        'appointment_status',
        'government',
    ];

    public const APPOINTMENT_STATUS_SELECT = [
        'Permanent'           => 'Permanent',
        'Casual'              => 'Casual',
        'Contract of Service' => 'Contract of Service',
        'Job Order'           => 'Job Order',
        'Part-time'           => 'Part-time',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function getAppointmentStatusLabelAttribute($value)
    {
        return static::APPOINTMENT_STATUS_SELECT[$this->appointment_status] ?? null;
    }

}
