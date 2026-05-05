<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaxDeclaration extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable();
    }

    public $table = 'tax_declarations';

    protected $fillable = [
        'tax_dec_no',
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

    public function landStructure()
    {
        return $this->belongsTo(LandStructure::class);
    }
}
