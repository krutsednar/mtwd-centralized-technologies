<?php

namespace App\Services;

use App\Exceptions\FaceBiometricException;
use App\Models\FaceBiometrics\FaceAuditLog;
use App\Models\FaceBiometrics\FaceEmbedding;
use App\Models\FaceBiometrics\FaceProfile;
use App\Models\Profile;
use App\ValueObjects\FaceBiometrics\VerifyResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FaceBiometricService
{
    private string $url;

    private string $token;

    private float $matchThreshold;

    private float $matchMargin;

    private float $livenessThreshold;

    private float $qualityThreshold;

    private float $enrollQualityThreshold;

    public function __construct()
    {
        $this->url = config('face_biometrics.service_url');
        $this->token = config('face_biometrics.service_token');
        $this->matchThreshold = config('face_biometrics.match_threshold');
        $this->matchMargin = config('face_biometrics.match_margin');
        $this->livenessThreshold = config('face_biometrics.liveness_threshold');
        $this->qualityThreshold = config('face_biometrics.quality_threshold');
        $this->enrollQualityThreshold = config('face_biometrics.enroll_quality_threshold');
    }

    public function extract(string $base64OrJpegBytes, bool $isEnrollment = false): array
    {
        $isBase64 = base64_encode(base64_decode($base64OrJpegBytes, true)) === $base64OrJpegBytes;
        $bytes = $isBase64 ? base64_decode($base64OrJpegBytes) : $base64OrJpegBytes;

        $query = $isEnrollment ? '?enrollment=1' : '';

        try {
            $response = Http::timeout(30)
                ->withToken($this->token)
                ->attach('file', $bytes, 'photo.jpg')
                ->post("{$this->url}/extract{$query}");
        } catch (\Throwable $e) {
            throw new FaceBiometricException('extract_failed', 'Python service unreachable: '.$e->getMessage());
        }

        if ($response->successful()) {
            return $response->json();
        }

        $reason = $response->json('detail.reason') ?? $response->json('detail') ?? 'extract_failed';

        throw new FaceBiometricException(
            (string) $reason,
            "Extract failed ({$response->status()}): {$reason}"
        );
    }

    public function extractFromPath(string $absolutePath, bool $isEnrollment = true): array
    {
        if (! file_exists($absolutePath)) {
            throw new FaceBiometricException('file_not_found', "File not found: {$absolutePath}");
        }

        $bytes = file_get_contents($absolutePath);

        return $this->extract($bytes, $isEnrollment);
    }

    public function enroll(int $faceProfileId, int $slot, array $embedding, float $quality, string $source, ?string $path): void
    {
        FaceEmbedding::insertVector($faceProfileId, $slot, $embedding, $quality, $source, $path);
    }

    public function search(array $embedding, int $k = 2): array
    {
        return FaceEmbedding::searchTopK($embedding, $k);
    }

    public function verify(string $photoBase64): VerifyResult
    {
        $result = $this->extract($photoBase64, false);

        $embedding = $result['embedding'];
        $liveness = (float) ($result['liveness'] ?? 0.0);
        $quality = (float) ($result['quality'] ?? 0.0);

        $matches = $this->search($embedding, 2);

        if (empty($matches)) {
            return new VerifyResult(
                matched: false,
                profile: null,
                score: 0.0,
                secondScore: 0.0,
                margin: 0.0,
                liveness: $liveness,
                quality: $quality,
                reason: 'no_match',
            );
        }

        $top = $matches[0];
        $second = $matches[1] ?? null;

        $topScore = (float) $top->score;
        $secondScore = $second ? (float) $second->score : 0.0;
        $margin = $topScore - $secondScore;

        if ($topScore < $this->matchThreshold || $margin < $this->matchMargin) {
            return new VerifyResult(
                matched: false,
                profile: null,
                score: $topScore,
                secondScore: $secondScore,
                margin: $margin,
                liveness: $liveness,
                quality: $quality,
                reason: $topScore < $this->matchThreshold ? 'score_below_threshold' : 'margin_too_small',
            );
        }

        $profile = Profile::find($top->profile_id);

        return new VerifyResult(
            matched: true,
            profile: $profile,
            score: $topScore,
            secondScore: $secondScore,
            margin: $margin,
            liveness: $liveness,
            quality: $quality,
            reason: 'ok',
        );
    }

    public function health(): bool
    {
        try {
            $response = Http::timeout(1)
                ->withToken($this->token)
                ->get("{$this->url}/health");

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    public function bulkSeedFromProfilePictures(?Collection $profiles = null, ?callable $progress = null): array
    {
        if ($profiles === null) {
            $profiles = Profile::whereNotNull('picture')
                ->whereDoesntHave('faceProfile', fn ($q) => $q->where('is_enrolled', true))
                ->get();
        }

        $enrolled = 0;
        $skipped = 0;
        $failed = 0;
        $failures = [];

        foreach ($profiles as $profile) {
            if (! $profile->picture || ! Storage::disk('public')->exists($profile->picture)) {
                $skipped++;
                if ($progress) {
                    $progress('skip', $profile, 'no_picture');
                }

                continue;
            }

            try {
                \Illuminate\Support\Facades\DB::transaction(function () use ($profile, &$enrolled) {
                    $absolutePath = Storage::disk('public')->path($profile->picture);
                    $result = $this->extractFromPath($absolutePath, true);

                    $embedding = $result['embedding'];
                    $quality = (float) ($result['quality'] ?? 0.0);

                    $faceProfile = FaceProfile::updateOrCreate(
                        ['profile_id' => $profile->id],
                        [
                            'is_enrolled' => true,
                            'enrolled_at' => now(),
                            'enrollment_quality_score' => $quality,
                            'template_count' => 1,
                            'enrollment_source' => 'profile_picture',
                        ]
                    );

                    FaceEmbedding::insertVector(
                        $faceProfile->id,
                        1,
                        $embedding,
                        $quality,
                        'profile_picture',
                        $profile->picture
                    );

                    FaceAuditLog::create([
                        'profile_id' => $profile->id,
                        'event' => 'enroll_seed',
                        'quality_score' => $quality,
                        'source' => 'profile_picture',
                        'reason' => 'bulk_seed',
                        'created_at' => now(),
                    ]);

                    $enrolled++;
                });
            } catch (\Throwable $e) {
                $failed++;
                $failures[] = [
                    'profile_id' => $profile->id,
                    'employee_number' => $profile->employee_number,
                    'reason' => $e->getMessage(),
                ];

                Log::warning("FaceBiometricService: seed failed for profile {$profile->id}: ".$e->getMessage());
            }

            if ($progress) {
                $progress('done', $profile, null);
            }
        }

        return compact('enrolled', 'skipped', 'failed', 'failures');
    }
}
