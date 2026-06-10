<?php

namespace App\Models;

use App\Models\FaceBiometrics\FaceProfile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Profile extends Model
{
    use HasFactory, SoftDeletes;
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }

    public $table = 'profiles';

    // protected $appends = [
    //     'id_pic',
    // ];

    public const SEX_SELECT = [
        'M' => 'Male',
        'F' => 'Female',
    ];

    public const CITIZENSHIP_SELECT = [
        'Filipino' => 'Filipino',
        'Others' => 'Others',
    ];

    protected $fillable = [
        'employee_number',
        'surname',
        'first_name',
        'middle_name',
        'suffix',
        'date_of_birth',
        'place_of_birth',
        'sex',
        'citizenship',
        'email',
        'mobile_number',
        'present_address',
        'gsis_id_no',
        'pagibig_id_no',
        'philhealth_no',
        'sss_no',
        'tin_no',
        'spouse_surname',
        'spouse_first_name',
        'spouse_middle_name',
        'father_surname',
        'father_first_name',
        'father_middle_name',
        'mother_surname',
        'mother_first_name',
        'mother_middle_name',
        'is_active',
        'profile',
        'picture',
        'status',
        'pds',
        'division_id',
        'face_enrolled',
        'face_descriptors',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'face_descriptors' => 'array',
        ];
    }

    public function getSexLabelAttribute($value)
    {
        return static::SEX_SELECT[$this->sex] ?? null;
    }

    public function getCitizenshipLabelAttribute($value)
    {
        return static::CITIZENSHIP_SELECT[$this->citizenship] ?? null;
    }

    public function children(): HasMany
    {
        return $this->hasMany(Child::class);
    }

    public function educationalBackgrounds(): HasMany
    {
        return $this->hasMany(EducationalBackground::class);
    }

    public function eligibilities(): HasMany
    {
        return $this->hasMany(Eligibility::class);
    }

    public function workExperiences(): HasMany
    {
        return $this->hasMany(WorkExperience::class);
    }

    /**
     * @deprecated Use workExperiences() instead. Kept for backward compatibility
     *             while call sites are migrated; will be removed in a future PR.
     */
    public function work_experiences(): HasMany
    {
        return $this->workExperiences();
    }

    public function individualPerformances(): HasMany
    {
        return $this->hasMany(IndividualPerformance::class);
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class)->orderBy('from', 'desc');
    }

    public function awards(): HasMany
    {
        return $this->hasMany(Award::class);
    }

    public function disciplinaryActions(): HasMany
    {
        return $this->hasMany(DisciplinaryAction::class);
    }

    /**
     * @deprecated Use disciplinaryActions() instead. Kept for backward
     *             compatibility while call sites are migrated; will be
     *             removed in a future PR.
     */
    public function disciplinary_actions(): HasMany
    {
        return $this->disciplinaryActions();
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    public function recognitions(): HasMany
    {
        return $this->hasMany(Recognition::class);
    }

    public function serviceRecords(): HasMany
    {
        return $this->hasMany(ServiceRecord::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(Skill::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function faceProfile(): HasOne
    {
        return $this->hasOne(FaceProfile::class);
    }

    /** The employee profile for the signed-in Home-panel user (linked by employee number). */
    public static function forCurrentUser(): ?self
    {
        $user = auth()->user();

        return $user ? static::query()->where('employee_number', $user->employee_number)->first() : null;
    }

    public function getFullNameAttribute(): string
    {
        $middle = $this->middle_name ? ' '.$this->middle_name : '';
        $suffix = $this->suffix ? ', '.$this->suffix : '';

        return trim("{$this->first_name}{$middle} {$this->surname}{$suffix}");
    }

    public function fullName(): string
    {
        $middle = $this->middle_name ? ' '.$this->middle_name : '';
        $suffix = $this->suffix ? ', '.$this->suffix : '';

        return trim("{$this->first_name}{$middle} {$this->surname}{$suffix}");
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }

    public function leaveCards(): HasMany
    {
        return $this->hasMany(LeaveCard::class);
    }

    public function tripTickets(): BelongsToMany
    {
        return $this->belongsToMany(TripTicket::class);
    }
}
