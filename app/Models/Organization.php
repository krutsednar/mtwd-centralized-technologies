<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Organization extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable();
    }

    public $table = 'organizations';

    protected $dates = [
        'from',
        'to',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'profile_id',
        'organization_name',
        'organization_address',
        'position_title',
        'from',
        'to',
    ];

    public $orderable = [
        'id',
        'profile.employee_number',
        'organization_name',
        'organization_address',
        'position_title',
        'from',
        'to',
    ];

    public $filterable = [
        'id',
        'profile.employee_number',
        'organization_name',
        'organization_address',
        'position_title',
        'from',
        'to',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

}
