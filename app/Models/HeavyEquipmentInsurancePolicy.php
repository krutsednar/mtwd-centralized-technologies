<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HeavyEquipmentInsurancePolicy extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable();
    }

    public $table = 'heavy_equipment_insurance_policies';

    protected $fillable = [
        'policy_no',
        'policy_expiration',
        'policy_file',
        'heavy_equipment_id',
    ];

     protected function casts(): array
    {
        return [
            'policy_expiration' => 'date',
        ];
    }

    public function heavyEquipment()
    {
        return $this->belongsTo(HeavyEquipment::class);
    }
}
