<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EducationalBackground extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable();
    }

    public $table = 'educational_backgrounds';

    protected $dates = [
        'from',
        'to',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'profile_id',
        'level',
        'school_name',
        'degree_course',
        'year_graduated',
        'highest_grade',
        'from',
        'to',
        'honors',
        'tor',
        'diploma',
    ];

    public $orderable = [
        'id',
        'profile.employee_number',
        'level',
        'school_name',
        'degree_course',
        'year_graduated',
        'highest_grade',
        'from',
        'to',
        'honors',
    ];

    public $filterable = [
        'id',
        'profile.employee_number',
        'level',
        'school_name',
        'degree_course',
        'year_graduated',
        'highest_grade',
        'from',
        'to',
        'honors',
    ];

    public const LEVEL_SELECT = [
        'Primary'                 => 'Primary',
        'Elementary'              => 'Elementary',
        'Secondary - High School'               => 'High School',
        'Secondary - Junior High School'               => 'Junior High School',
        'Secondary - Senior High School'               => 'Senior High School',
        'College'                 => 'College',
        'Graduate Studies'        => 'Graduate Studies',
        'Vocational/Trade Course' => 'Vocational/Trade Course',
        'NA'                      => 'NA',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function getLevelLabelAttribute($value)
    {
        return static::LEVEL_SELECT[$this->level] ?? null;
    }

}
