<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Equipment extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable();
    }

    public $table = 'equipment';

    protected $fillable = [
        'brand',
        'model',
        'serial_number',
        'date_acquired',
        'par_no',
        'custodian',
        'division_id',
        'value',
        'desc',
        'location',
        'status',
        'equipment_type_id',
    ];

    public function equipmentType()
    {
        return $this->belongsTo(EquipmentType::class);
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
