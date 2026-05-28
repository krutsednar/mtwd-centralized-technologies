<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TripTicket extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }

    public $table = 'trip_tickets';

    protected $fillable = [
        'ticket_no',
        'date',
        'vehicle_id',
        'profile_id',
        'destination',
        'purpose',
        'office_departure',
        'destination_arrival',
        'destination_departure',
        'office_arrival',
        'distance_travelled',
        'beginning_balance',
        'purchase',
        'consumed',
        'ending_balance',
        'oil_grease_lub_issued',
        'speedometer_reading',
        'actual_distance_travelled',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'office_departure' => 'datetime',
            'destination_arrival' => 'datetime',
            'destination_departure' => 'datetime',
            'office_arrival' => 'datetime',
        ];
    }

    public function profiles(): BelongsToMany
    {
        return $this->belongsToMany(Profile::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
