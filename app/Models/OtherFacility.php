<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OtherFacility extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable();
    }

    public $table = 'other_facilities';

    protected $fillable = [
        'property_name',
        'address',
        'tex_dec_no',
        'rpt_or_no',
        'rpt_date_issued',
        'title_no',
        'title_file',
        'tax_dec_file',
        'rtp_file',
        'photo',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'rpt_date_issued' => 'date',
        ];
    }

}
