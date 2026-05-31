<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }

    public $table = 'vehicles';

    protected function casts(): array
    {
        return [
            'date_acquired' => 'date',
            'insurance_expiration_date' => 'date',
            'or_expiration' => 'date',
        ];
    }

    protected $fillable = [
        'vehicle_type_id',
        'brand',
        'model',
        'serial_number',
        'chasis_no',
        'engine_no',
        'plate_no',
        'date_acquired',
        'par_no',
        'custodian',
        'division_id',
        'value',
        'certificate_of_registration',
        'status',
        'cr_file',
        'chasis_file',
        'description',
        'remarks',
    ];

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function officialReceipts(): HasMany
    {
        return $this->hasMany(VehicleOfficialReceipt::class);
    }

    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(VehicleInsurancePolicy::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class, 'custodian', 'id');
    }

    public function drivers(): BelongsToMany
    {
        return $this->belongsToMany(Driver::class);
    }

    public function vehicleName(): string
    {
        return trim("{$this->brand} {$this->model} - {$this->plate_no}");
    }

    public function getVehicleNameAttribute(): string
    {
        return trim("{$this->brand} {$this->model} - {$this->plate_no}");
    }
}
