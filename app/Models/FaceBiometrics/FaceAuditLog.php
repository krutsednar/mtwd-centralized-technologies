<?php

namespace App\Models\FaceBiometrics;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceAuditLog extends Model
{
    public $table = 'face_audit_log';

    public $timestamps = false;

    protected $dates = ['created_at'];

    protected $fillable = [
        'profile_id',
        'event',
        'match_score',
        'liveness_score',
        'quality_score',
        'reason',
        'photo_hash',
        'photo_path',
        'ip_address',
        'kiosk_id',
        'user_agent',
        'source',
        'created_at',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
