<?php

namespace App\Models;

use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Eligibility extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable();
    }

    public $table = 'eligibilities';

    public const ELIGIBILITY_SELECT = [
        'None'  => 'None',
        'CS Sub-Professional'  => 'CS Sub-Professional',
        'CS Professional'  => 'CS Professional',
        'Barangay Official Eligibility (RA 7160)'  => 'Barangay Official Eligibility (RA 7160)',
        'Skills Eligibility - Category II (CSC MC 11, s. 1996, as Amended)'  => 'Skills Eligibility - Category II (CSC MC 11, s. 1996, as Amended)',
        'Electronic Data Processing Specialist Eligibility (CSC Res. 90-083)'  => 'Electronic Data Processing Specialist Eligibility (CSC Res. 90-083)',
        'TESDA National Certificate'  => 'TESDA National Certificate',
        'Honor Graduate Eligibility (PD 907)'  => 'Honor Graduate Eligibility (PD 907)',
        'RA1080- (Lawyer)'  => 'RA1080- (Lawyer)',
        'RA1080- (Architect)'  => 'RA1080- (Architect)',
        'RA1080- (Certified Public Accountant)'  => 'RA1080- (Certified Public Accountant)',
        'RA1080- (Chemical Engineer)'  => 'RA1080- (Chemical Engineer)',
        'RA1080- (Chemical Technician)'  => 'RA1080- (Chemical Technician)',
        'RA1080- (Chemist)'  => 'RA1080- (Chemist)',
        'RA1080- (Civil Engineer)'  => 'RA1080- (Civil Engineer)',
        'RA1080- (Criminologist)'  => 'RA1080- (Criminologist)',
        'RA1080- (Electronics Engineer)'  => 'RA1080- (Electronics Engineer)',
        'RA1080- (Electronics Technician)'  => 'RA1080- (Electronics Technician)',
        'RA1080- (Forester)'  => 'RA1080- (Forester)',
        'RA1080- (Geodetic Engineer)'  => 'RA1080- (Geodetic Engineer)',
        'RA1080- (Guidance Counselor)'  => 'RA1080- (Guidance Counselor)',
        'RA1080- (Master Plumber)'  => 'RA1080- (Master Plumber)',
        'RA1080- (Mechanical Engineer)'  => 'RA1080- (Mechanical Engineer)',
        'RA1080- (Medical Technologist)'  => 'RA1080- (Medical Technologist)',
        'RA1080- (Metallurgical Engineer)'  => 'RA1080- (Metallurgical Engineer)',
        'RA1080- (Nurse)'  => 'RA1080- (Nurse)',
        'RA1080- (Professional Electrical Engineer)'  => 'RA1080- (Professional Electrical Engineer)',
        'RA1080- (Professional Teacher)'  => 'RA1080- (Professional Teacher)',
        'RA1080- (Psychologist)'  => 'RA1080- (Psychologist)',
        'RA1080- (Psychometrician)'  => 'RA1080- (Psychometrician)',
        'RA1080- (Real Estate Appraiser)'  => 'RA1080- (Real Estate Appraiser)',
        'RA1080- (Real Estate Broker)'  => 'RA1080- (Real Estate Broker)',
        'RA1080- (Electrical Engineer)'  => 'RA1080- (Electrical Engineer)',
        'RA1080- (Master Electrician)'  => 'RA1080- (Master Electrician)',
        'RA1080- (Sanitary Engineer)'  => 'RA1080- (Sanitary Engineer)',
        'RA1080- (Social Worker)'  => 'RA1080- (Social Worker)',
        'Others'  => 'Others',

    ];

    protected $dates = [
        'date_of_examination',
        'date_issued',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $fillable = [
        'profile_id',
        'eligibility',
        'rating',
        'date_of_examination',
        'place_of_examination',
        'license_no',
        'date_issued',
        'attachment',
    ];

    public $orderable = [
        'id',
        'profile.employee_number',
        'eligibility',
        'rating',
        'date_of_examination',
        'place_of_examination',
        'license_no',
        'date_issued',
    ];

    public $filterable = [
        'id',
        'profile.employee_number',
        'eligibility',
        'rating',
        'date_of_examination',
        'place_of_examination',
        'license_no',
        'date_issued',
    ];


    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    public function getEligibilityLabelAttribute($value)
    {
        return static::ELIGIBILITY_SELECT[$this->eligibility] ?? null;
    }


}
