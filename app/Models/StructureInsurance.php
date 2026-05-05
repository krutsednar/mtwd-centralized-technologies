<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StructureInsurance extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable();
    }

    public $table = 'structure_insurances';

    protected $fillable = [
        'policy_no',
        'date_issued',
        'expiration',
        'attachment',
        'land_structure_id',
    ];

     protected function casts(): array
    {
        return [
            'date_issued' => 'date',
            'expiration' => 'date',
        ];
    }

    public function landStructure()
    {
        return $this->belongsTo(LandStructure::class);
    }
}
