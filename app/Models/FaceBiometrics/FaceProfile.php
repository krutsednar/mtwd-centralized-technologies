<?php

namespace App\Models\FaceBiometrics;

use App\Models\Profile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaceProfile extends Model
{
    public $table = 'face_profiles';

    protected $fillable = [
        'profile_id',
        'is_enrolled',
        'enrolled_at',
        'enrollment_quality_score',
        'template_count',
        'enrollment_source',
        'last_verified_at',
        'last_match_score',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_enrolled' => 'boolean',
            'enrolled_at' => 'datetime',
            'last_verified_at' => 'datetime',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function embeddings(): HasMany
    {
        return $this->hasMany(FaceEmbedding::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(FaceAttendance::class, 'profile_id', 'profile_id');
    }

    public function scopeEnrolled($query)
    {
        return $query->where('is_enrolled', true);
    }

    public function scopeUnenrolled($query)
    {
        return $query->where('is_enrolled', false);
    }
}
