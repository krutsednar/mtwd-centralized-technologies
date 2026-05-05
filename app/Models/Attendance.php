<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Attendance extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }

    public $table = 'attendances';

    protected $fillable = [
        'profile_id',
        'employee_number',
        'attendance_date',
        'morning_in',
        'morning_out',
        'afternoon_in',
        'afternoon_out',
        'ot_in',
        'ot_out',
        'remote_id',
        'is_synced',
        'synced_at',
    ];

    protected $dates = [
        'attendance_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public $orderable = [
        'id',
        'employee_number',
        'attendance_date',
        'morning_in',
        'morning_out',
        'afternoon_in',
        'afternoon_out',
        'ot_in',
        'ot_out',
    ];

    public $filterable = [
        'id',
        'employee_number',
        'attendance_date',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
