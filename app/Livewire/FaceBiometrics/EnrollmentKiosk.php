<?php

namespace App\Livewire\FaceBiometrics;

use App\Exceptions\FaceBiometricException;
use App\Models\FaceBiometrics\FaceAuditLog;
use App\Models\FaceBiometrics\FaceEmbedding;
use App\Models\FaceBiometrics\FaceProfile;
use App\Models\Profile;
use App\Services\FaceBiometricService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class EnrollmentKiosk extends Component
{
    public ?int $profileId = null;

    public int $currentFrame = 0;

    public int $totalFrames = 5;

    public array $capturedFrames = [];

    public array $frameErrors = [];

    public bool $enrollmentComplete = false;

    public bool $enrollmentFailed = false;

    public string $enrollmentError = '';

    public ?string $employeeName = null;

    public array $poses = [
        'Look straight at the camera',
        'Tilt slightly to your LEFT',
        'Tilt slightly to your RIGHT',
        'Look slightly UPWARD',
        'Put on glasses (if applicable), or look straight again',
    ];

    public function mount(): void
    {
        $this->profileId = (int) request()->query('profile_id');
        if ($this->profileId) {
            $profile = Profile::find($this->profileId);
            $this->employeeName = $profile?->full_name;
        }
    }

    public function captureFrame(string $photo): void
    {
        if ($this->currentFrame >= $this->totalFrames) {
            return;
        }

        $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $photo);

        $svc = app(FaceBiometricService::class);

        try {
            $result = $svc->extract($base64, true);
        } catch (FaceBiometricException $e) {
            $this->frameErrors[$this->currentFrame] = match ($e->reason) {
                'no_face' => 'No face detected. Adjust position and try again.',
                'multiple_faces' => 'Multiple faces detected. Ensure only you are visible.',
                'low_quality' => 'Image quality too low. Ensure good lighting.',
                default => 'Extraction failed: '.$e->getMessage(),
            };

            return;
        }

        $this->capturedFrames[$this->currentFrame] = [
            'embedding' => $result['embedding'],
            'quality' => $result['quality'] ?? 0.0,
            'liveness' => $result['liveness'] ?? 0.0,
        ];

        unset($this->frameErrors[$this->currentFrame]);
        $this->currentFrame++;

        if ($this->currentFrame === $this->totalFrames) {
            $this->finalizeEnrollment();
        }
    }

    private function finalizeEnrollment(): void
    {
        if (! $this->profileId) {
            $this->enrollmentFailed = true;
            $this->enrollmentError = 'No profile selected.';

            return;
        }

        try {
            DB::transaction(function () {
                $profile = Profile::findOrFail($this->profileId);

                $existingProfile = FaceProfile::where('profile_id', $this->profileId)->first();
                $existingSource = $existingProfile?->enrollment_source;
                $hasSeeded = $existingProfile?->is_enrolled && $existingSource === 'profile_picture';

                $faceProfile = FaceProfile::updateOrCreate(
                    ['profile_id' => $this->profileId],
                    []
                );

                $qualities = [];
                foreach ($this->capturedFrames as $slot => $frame) {
                    FaceEmbedding::insertVector(
                        $faceProfile->id,
                        $slot + 1,
                        $frame['embedding'],
                        $frame['quality'],
                        'webcam',
                        null
                    );

                    FaceAuditLog::create([
                        'profile_id' => $this->profileId,
                        'event' => 'enroll',
                        'quality_score' => $frame['quality'],
                        'liveness_score' => $frame['liveness'],
                        'source' => 'webcam',
                        'reason' => 'webcam_enrollment_frame_'.($slot + 1),
                        'kiosk_id' => request()->ip(),
                        'created_at' => now(),
                    ]);

                    $qualities[] = $frame['quality'];
                }

                $avgQuality = array_sum($qualities) / count($qualities);
                $source = $hasSeeded ? 'mixed' : 'webcam';
                $templateCount = count($this->capturedFrames) + ($hasSeeded ? 1 : 0);

                $faceProfile->update([
                    'is_enrolled' => true,
                    'enrolled_at' => now(),
                    'enrollment_quality_score' => $avgQuality,
                    'template_count' => $templateCount,
                    'enrollment_source' => $source,
                ]);
            });

            $this->enrollmentComplete = true;
        } catch (\Throwable $e) {
            Log::error('EnrollmentKiosk finalizeEnrollment failed: '.$e->getMessage());
            $this->enrollmentFailed = true;
            $this->enrollmentError = 'Enrollment failed: '.$e->getMessage();
        }
    }

    public function resetEnrollment(): void
    {
        $this->currentFrame = 0;
        $this->capturedFrames = [];
        $this->frameErrors = [];
        $this->enrollmentComplete = false;
        $this->enrollmentFailed = false;
        $this->enrollmentError = '';
    }

    public function render()
    {
        return view('livewire.face-biometrics.enrollment-kiosk')
            ->layout('components.layouts.kiosk');
    }
}
