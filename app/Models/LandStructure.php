<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    public function landStructureType()
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
