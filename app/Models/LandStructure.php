<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LandStructure extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }

    public $table = 'land_structures';

    protected $fillable = [
        'land_structure_type_id',
        'property_name',
        'lot_area',
        'date_acquired',
        'date_established',
        'address',
        'title_no',
        'title_file',
        'photo',
        'status',
    ];

    public function landStructureType(): BelongsTo
    {
        return $this->belongsTo(LandStructureType::class);
    }

    public function realPropertyTaxes(): HasMany
    {
        return $this->hasMany(RealPropertyTax::class);
    }

    public function taxDeclarations(): HasMany
    {
        return $this->hasMany(TaxDeclaration::class);
    }

    public function structureInsurances(): HasMany
    {
        return $this->hasMany(StructureInsurance::class);
    }
}
