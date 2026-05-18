<?php

namespace App\Jobs\FaceBiometrics;

use App\Models\FaceBiometrics\FaceAuditLog;
use App\Models\FaceBiometrics\FaceEmbedding;
use App\Models\FaceBiometrics\FaceProfile;
use App\Models\Profile;
use App\Services\FaceBiometricService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BulkSeedFromProfilePicturesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(
        public readonly array $profileIds,
        public readonly bool $overwriteExisting = false,
    ) {}

    public function handle(FaceBiometricService $svc): void
    {
        $enrolled = 0;
        $skipped = 0;
        $failed = 0;
        $failures = [];

        foreach ($this->profileIds as $profileId) {
            $profile = Profile::find($profileId);

            if (! $profile) {
                $skipped++;

                continue;
            }

            if (! $profile->picture || ! Storage::disk('public')->exists($profile->picture)) {
                $skipped++;
                Log::info("BulkSeed: skipping profile {$profileId} — no picture on disk");

                continue;
            }

            // Check existing enrollment
            $existing = FaceProfile::where('profile_id', $profileId)->first();
            if ($existing?->is_enrolled && ! $this->overwriteExisting) {
                $skipped++;

                continue;
            }

            try {
                DB::transaction(function () use ($profile, $svc, &$enrolled) {
                    $absolutePath = Storage::disk('public')->path($profile->picture);
                    $result = $svc->extractFromPath($absolutePath, true);

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
                        'reason' => 'bulk_seed_job',
                        'created_at' => now(),
                    ]);

                    $enrolled++;
                });
            } catch (\Throwable $e) {
                $failed++;
                $failures[] = [
                    'profile_id' => $profileId,
                    'employee_number' => $profile->employee_number,
                    'reason' => $e->getMessage(),
                ];

                Log::warning("BulkSeedJob: failed profile {$profileId} ({$profile->employee_number}): ".$e->getMessage());

                FaceAuditLog::create([
                    'profile_id' => $profileId,
                    'event' => 'enroll_seed',
                    'reason' => 'bulk_seed_failed: '.substr($e->getMessage(), 0, 200),
                    'source' => 'profile_picture',
                    'created_at' => now(),
                ]);
            }
        }

        Log::info("BulkSeedJob completed: enrolled={$enrolled}, skipped={$skipped}, failed={$failed}", $failures);
    }
}
