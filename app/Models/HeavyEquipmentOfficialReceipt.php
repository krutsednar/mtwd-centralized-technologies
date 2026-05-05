<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HeavyEquipmentOfficialReceipt extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable();
    }

    public $table = 'heavy_equipment_official_receipts';

    protected $fillable = [
        'or_no',
        'or_expiration',
        'or_file',
        'heavy_equipment_id',
    ];

    protected function casts(): array
    {
        return [
            'or_expiration' => 'date',
        ];
    }

    public function heavyEquipment()
    {
        return $this->belongsTo(HeavyEquipment::class);
    }
}
