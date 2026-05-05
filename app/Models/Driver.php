<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Driver extends Model
{
    use SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable();
    }

    protected $fillable = [
        'profile_id',
        'license_no',
        'type',
        'restrictions',
        'expiration',
        'date_approved',
        'primary_vehicle',
        'dl_file',
        'som_file',
        'memo_file',
    ];

    protected function casts(): array
    {
        return [
            'date_approved' => 'date',
            'expiration' => 'date',
        ];
    }

    public function primaryVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'primary_vehicle');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class);
    }
}
