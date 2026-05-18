<?php

namespace App\Models\FaceBiometrics;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FaceAttendance extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'face_attendances';

    protected $fillable = [
        'profile_id',
        'employee_number',
        'attendance_date',
        'morning_in',
        'morning_out',
        'afternoon_in',
        'afternoon_out',
        'ot_in',
        'ot_out',
        'remote_id',
        'is_synced',
        'synced_at',
        // v2 extras
        'match_score',
        'liveness_score',
        'quality_score',
        'kiosk_id',
        'verification_method',
        'top_match_margin',
    ];

    protected $dates = [
        'attendance_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public $orderable = [
        'id',
        'employee_number',
        'attendance_date',
        'morning_in',
        'morning_out',
        'afternoon_in',
        'afternoon_out',
        'ot_in',
        'ot_out',
    ];

    public $filterable = [
        'id',
        'employee_number',
        'attendance_date',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
