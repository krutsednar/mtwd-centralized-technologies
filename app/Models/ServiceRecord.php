<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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

    protected function casts(): array
    {
        return [
            'from' => 'date',
            'to' => 'date',
            'salary' => 'decimal:2',
        ];
    }

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

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
