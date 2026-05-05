<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Child extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable();
    }

    public $table = 'children';

    protected $fillable = [
        'profile_id',
        'name',
        'date_of_birth',
    ];

    public $orderable = [
        'id',
        'profile.employee_number',
        'name',
        'date_of_birth',
    ];

    protected $dates = [
        'date_of_birth',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public $filterable = [
        'id',
        'profile.employee_number',
        'name',
        'date_of_birth',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

}
