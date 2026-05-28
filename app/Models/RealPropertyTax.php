<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RealPropertyTax extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }

    public $table = 'real_property_taxes';

    protected $fillable = [
        'or_no',
        'date_issued',
        'attachment',
        'land_structure_id',
    ];

    protected function casts(): array
    {
        return [
            'date_issued' => 'date',
        ];
    }

    public function landStructure(): BelongsTo
    {
        return $this->belongsTo(LandStructure::class);
    }
}
