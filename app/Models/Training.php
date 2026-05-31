<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Training extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }

    public $table = 'trainings';

    // protected $appends = [
    //     'certificate',
    // ];

    protected $dates = [
        'from',
        'to',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'profile_id',
        'title',
        'from',
        'to',
        'number_of_hours',
        'conducted_by',
        'ld_type',
        'attachment',
    ];

    public $orderable = [
        'id',
        'profile.employee_number',
        'title',
        'from',
        'to',
        'number_of_hours',
        'conducted_by',
        'ld_type',
    ];

    public $filterable = [
        'id',
        'profile.employee_number',
        'title',
        'from',
        'to',
        'number_of_hours',
        'conducted_by',
        'ld_type',
    ];

    public const LD_TYPE_SELECT = [
        'Technical' => 'Technical',
        'Values Formation' => 'Values Formation',
        'Supervisory' => 'Supervisory',
        'Leadership' => 'Leadership',
        'Managerial' => 'Managerial',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
