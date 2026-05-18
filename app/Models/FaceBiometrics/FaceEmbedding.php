<?php

namespace App\Models\FaceBiometrics;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class FaceEmbedding extends Model
{
    public $table = 'face_embeddings';

    protected $fillable = [
        'face_profile_id',
        'slot',
        'quality_score',
        'source',
        'source_path',
        'captured_at',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
    ];

    public function faceProfile(): BelongsTo
    {
        return $this->belongsTo(FaceProfile::class);
    }

    public static function insertVector(
        int $faceProfileId,
        int $slot,
        array $vec,
        float $quality,
        string $source,
        ?string $path
    ): void {
        $vectorLiteral = '['.implode(',', $vec).']';

        // Expand search_path so PostgreSQL can resolve the vector type from public schema
        DB::statement('SET search_path TO mct_devdb, public');

        DB::statement(
            'INSERT INTO mct_devdb.face_embeddings
                (face_profile_id, slot, quality_score, source, source_path, captured_at, embedding, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), ?::vector, NOW(), NOW())
             ON CONFLICT (face_profile_id, slot) DO UPDATE
               SET quality_score = EXCLUDED.quality_score,
                   source        = EXCLUDED.source,
                   source_path   = EXCLUDED.source_path,
                   captured_at   = EXCLUDED.captured_at,
                   embedding     = EXCLUDED.embedding,
                   updated_at    = NOW()',
            [$faceProfileId, $slot, $quality, $source, $path, $vectorLiteral]
        );

        DB::statement('SET search_path TO mct_devdb');
    }

    public static function searchTopK(array $vec, int $k = 2): array
    {
        $vectorLiteral = '['.implode(',', $vec).']';

        DB::statement('SET search_path TO mct_devdb, public');

        // Use DISTINCT ON to get the best (closest) embedding per profile so a
        // profile with multiple templates does not dominate the top-K — otherwise
        // the margin between #1 and #2 is always tiny because both rows belong
        // to the same person.
        $results = DB::select(
            'SELECT * FROM (
                SELECT DISTINCT ON (fp.profile_id)
                    fe.face_profile_id,
                    fp.profile_id,
                    p.employee_number,
                    CONCAT(p.first_name, \' \', p.surname) AS full_name,
                    1 - (fe.embedding <=> ?::vector) AS score
                FROM mct_devdb.face_embeddings fe
                JOIN mct_devdb.face_profiles fp ON fp.id = fe.face_profile_id
                JOIN mct_devdb.profiles p ON p.id = fp.profile_id
                WHERE fe.embedding IS NOT NULL
                ORDER BY fp.profile_id, fe.embedding <=> ?::vector
            ) AS best_per_profile
            ORDER BY score DESC
            LIMIT ?',
            [$vectorLiteral, $vectorLiteral, $k]
        );

        DB::statement('SET search_path TO mct_devdb');

        return $results;
    }
}
