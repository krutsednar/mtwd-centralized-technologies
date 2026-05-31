<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

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

    public function heavyEquipment(): BelongsTo
    {
        return $this->belongsTo(HeavyEquipment::class);
    }
}
