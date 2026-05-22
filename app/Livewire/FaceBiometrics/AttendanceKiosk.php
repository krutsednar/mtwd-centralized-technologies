<?php

namespace App\Livewire\FaceBiometrics;

use App\Exceptions\FaceBiometricException;
use App\Models\Attendance;
use App\Models\FaceBiometrics\FaceAttendance;
use App\Models\FaceBiometrics\FaceAuditLog;
use App\Models\Profile;
use App\Services\FaceBiometricService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AttendanceKiosk extends Component
{
    public string $kioskId;

    #[Locked]
    public string $nonce = '';

    public ?string $modalType = null;

    public ?string $employeeName = null;

    public ?string $employeeNumber = null;

    public ?string $clockedTime = null;

    public ?string $failReason = null;

    public ?string $phaseRecorded = null;

    public function mount(): void
    {
        $this->kioskId = request()->server('HTTP_X_KIOSK_ID', 'kiosk-'.substr(md5(request()->ip()), 0, 8));
        $this->refreshNonce();
    }

    private function refreshNonce(): void
    {
        $this->nonce = Str::random(40);
        Cache::put("face_kiosk_nonce_{$this->kioskId}", $this->nonce, now()->addHours(8));
    }

    public function verifyAndRecord(string $photo): void
    {
        $stored = Cache::get("face_kiosk_nonce_{$this->kioskId}");
        if (! $stored || $stored !== $this->nonce) {
            $this->modalType = 'fail';
            $this->failReason = 'replay_rejected';
            $this->refreshNonce();

            return;
        }
        Cache::forget("face_kiosk_nonce_{$this->kioskId}");

        $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $photo);
        $bytes = base64_decode($base64);
        $hash = hash('sha256', $bytes);

        $svc = app(FaceBiometricService::class);

        try {
            $result = $svc->verify($base64);
        } catch (FaceBiometricException $e) {
            $this->logAudit(null, $e->reason, null, null, null, $hash, null, $bytes, $e->reason);
            $this->handleFaceException($e);
            $this->refreshNonce();

            return;
        } catch (\Throwable $e) {
            Log::error('FaceKiosk verify error: '.$e->getMessage());
            $this->modalType = 'fail';
            $this->failReason = 'service_error';
            $this->refreshNonce();

            return;
        }

        if (! $result->matched || ! $result->profile) {
            $this->logAudit(null, 'verify_fail', $result->score, $result->liveness, $result->quality, $hash, null, $bytes, $result->reason);
            $this->modalType = 'fail';
            $this->failReason = 'no_match';
            $this->refreshNonce();

            return;
        }

        $profile = $result->profile;
        $today = today()->toDateString();
        $field = $this->resolveNextPhaseField($profile, $today);

        if ($field === null) {
            $this->logAudit($profile->id, 'duplicate', $result->score, $result->liveness, $result->quality, $hash, null, $bytes, 'all_phases_recorded');
            $this->employeeName = $profile->full_name;
            $this->employeeNumber = $profile->employee_number;
            $this->modalType = 'duplicate';
            $this->refreshNonce();

            return;
        }

        $time = now()->format('H:i:s');

        FaceAttendance::updateOrCreate(
            ['employee_number' => $profile->employee_number, 'attendance_date' => $today],
            [
                'profile_id' => $profile->id,
                $field => $time,
                'match_score' => $result->score,
                'liveness_score' => $result->liveness,
                'quality_score' => $result->quality,
                'kiosk_id' => $this->kioskId,
                'verification_method' => 'face_v2',
                'top_match_margin' => $result->margin,
            ]
        );

        Attendance::updateOrCreate(
            ['employee_number' => $profile->employee_number, 'attendance_date' => $today],
            [
                'profile_id' => $profile->id,
                $field => $time,
            ]
        );

        $this->logAudit($profile->id, 'verify_ok', $result->score, $result->liveness, $result->quality, $hash, null, null, 'ok');

        $profile->faceProfile?->update([
            'last_verified_at' => now(),
            'last_match_score' => $result->score,
        ]);

        $this->employeeName = $profile->full_name;
        $this->employeeNumber = $profile->employee_number;
        $this->clockedTime = now()->format('h:i A');
        $this->phaseRecorded = $this->fieldToLabel($field);
        $this->modalType = 'success';
        $this->refreshNonce();
    }

    private function resolveNextPhaseField(Profile $profile, string $today): ?string
    {
        $existing = FaceAttendance::where('employee_number', $profile->employee_number)
            ->where('attendance_date', $today)
            ->first();

        if (! $existing || empty($existing->morning_in)) {
            return 'morning_in';
        }
        if (empty($existing->morning_out)) {
            return 'morning_out';
        }
        if (empty($existing->afternoon_in)) {
            return 'afternoon_in';
        }
        if (empty($existing->afternoon_out)) {
            return 'afternoon_out';
        }

        return null;
    }

    private function fieldToLabel(string $field): string
    {
        return match ($field) {
            'morning_in' => 'Morning In',
            'morning_out' => 'Morning Out',
            'afternoon_in' => 'Afternoon In',
            'afternoon_out' => 'Afternoon Out',
            default => ucwords(str_replace('_', ' ', $field)),
        };
    }

    private function handleFaceException(FaceBiometricException $e): void
    {
        $this->modalType = match ($e->reason) {
            'spoof_suspected' => 'spoof',
            default => 'fail',
        };
        $this->failReason = match ($e->reason) {
            'spoof_suspected' => 'Please try again.',
            'no_face' => 'No face detected. Please look at the camera.',
            'multiple_faces' => 'Multiple faces detected. Please ensure only you are in frame.',
            'low_quality' => 'Image quality too low. Ensure good lighting and face the camera directly.',
            default => 'Verification failed. Please try again.',
        };
    }

    private function logAudit(
        ?int $profileId,
        string $event,
        ?float $score,
        ?float $liveness,
        ?float $quality,
        string $hash,
        ?string $storedPath,
        ?string $rawBytes,
        string $reason
    ): void {
        $persistEvents = ['verify_fail', 'spoof_detected', 'duplicate'];
        $path = null;

        if ($rawBytes && in_array($event, $persistEvents)) {
            $date = today()->toDateString();
            $id = Str::random(16);
            $rel = "face-biometrics/audit/{$date}/{$id}.enc";
            Storage::put($rel, Crypt::encrypt($rawBytes));
            $path = $rel;
        }

        FaceAuditLog::create([
            'profile_id' => $profileId,
            'event' => $event,
            'match_score' => $score,
            'liveness_score' => $liveness,
            'quality_score' => $quality,
            'reason' => $reason,
            'photo_hash' => $hash,
            'photo_path' => $path,
            'ip_address' => request()->ip(),
            'kiosk_id' => $this->kioskId,
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
            'source' => 'kiosk',
            'created_at' => now(),
        ]);
    }

    public function closeModal(): void
    {
        $this->modalType = null;
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.face-biometrics.attendance-kiosk')
            ->layout('components.layouts.kiosk');
    }
}
