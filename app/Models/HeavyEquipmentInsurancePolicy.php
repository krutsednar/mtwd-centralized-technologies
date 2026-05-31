<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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

    public function heavyEquipment(): BelongsTo
    {
        return $this->belongsTo(HeavyEquipment::class);
    }
}
