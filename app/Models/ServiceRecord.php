<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceRecord extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable();
    }

    public $table = 'service_records';

    protected $dates = [
        'from',
        'to',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'from' => 'date',
        'to' => 'date',
        'salary' => 'decimal:2', // Optional: good practice for currency
    ];

    protected $fillable = [
        'profile_id',
        'from',
        'to',
        'status',
        'code',
        'agency',
        'position',
        'sg',
        'increment',
        'salary',
        'allowance',
        'remarks',
        'other_remarks',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

}
