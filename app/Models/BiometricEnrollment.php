<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BiometricEnrollment extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    protected $table = 'biometric_enrollments';

    protected $fillable = [
        'profile_id',
        'image_1',
        'image_2',
        'image_3',
        'compreface_face_ids',
        'enrolled_at',
    ];

    protected $casts = [
        'compreface_face_ids' => 'array',
        'enrolled_at'         => 'datetime',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
