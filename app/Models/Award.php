<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Award extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable();
    }

    public $table = 'awards';

    public const CATEGORY_SELECT = [
        'Individual' => 'Individual',
        'Group'      => 'Group',
    ];

    protected $dates = [
        'date_received',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'profile_id',
        'date_received',
        'particulars',
        'awards_received',
        'category',
    ];

    public $orderable = [
        'id',
        'profile.employee_number',
        'date_received',
        'particulars',
        'awards_received',
        'category',
    ];

    public $filterable = [
        'id',
        'profile.employee_number',
        'date_received',
        'particulars',
        'awards_received',
        'category',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

}
