<?php

namespace App\Models;

use Carbon\Carbon;
use DateTimeInterface;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HeavyEquipment extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable();
    }

    public $table = 'heavy_equipments';

    protected function casts(): array
    {
        return [
            'date_acquired' => 'date',
            'insurance_expiration' => 'date',
            'or_expiration' => 'date',
        ];
    }

    protected $fillable = [
        'heavy_equipment_type_id',
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

    public function heavyEquipmentType()
    {
        return $this->belongsTo(HeavyEquipmentType::class);
    }

    public function officialReceipts(): HasMany
    {
        return $this->hasMany(HeavyEquipmentOfficialReceipt::class);
    }

    public function insurancePolicies(): HasMany
    {
        return $this->hasMany(HeavyEquipmentInsurancePolicy::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function profile()
    {
        return $this->belongsTo(Profile::class, 'custodian', 'id');
    }
}
