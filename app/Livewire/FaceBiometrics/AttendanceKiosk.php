<?php

namespace App\Livewire\FaceBiometrics;

use App\Exceptions\FaceBiometricException;
use App\Models\Attendance;
use App\Models\FaceBiometrics\FaceAttendance;
use App\Models\FaceBiometrics\FaceAuditLog;
use App\Services\FaceBiometrics\AttendanceCooldown;
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

        // A kiosk runs unattended and is routinely left open across an overnight
        // idle gap that exceeds a single shift (observed gaps of 10+ hours, and
        // 50+ hours over weekends). Keep the nonce alive long enough to span a
        // normal overnight gap so the first scan of the day still matches. An
        // expired nonce is no longer fatal — verifyAndRecord() now processes it
        // instead of rejecting — but a generous TTL avoids the needless miss.
        Cache::put("face_kiosk_nonce_{$this->kioskId}", $this->nonce, now()->addHours(24));
    }

    public function verifyAndRecord(string $photo): void
    {
        $nonceKey = "face_kiosk_nonce_{$this->kioskId}";
        $inFlightKey = "face_kiosk_inflight_{$this->kioskId}";

        $stored = Cache::get($nonceKey);

        // The only scan we must refuse is a genuine concurrent double-tap: the
        // first tap consumed the nonce and is still mid-processing, so the
        // in-flight marker is present. Swallow that duplicate silently — no
        // modal, no double record.
        //
        // A non-matching nonce *without* an in-flight marker is NOT a replay.
        // It is a legitimate scan whose nonce we simply no longer hold: the
        // kiosk sat idle past the cache TTL (an overnight or weekend gap), the
        // cache was flushed by a deploy/restart, or the tab was reloaded. The
        // old code rejected these as `replay_rejected`, which silently dropped
        // the first employee's attendance every morning until the kiosk was
        // "primed" — the bug behind "fails to capture before 7 AM". Those scans
        // must be processed, so fall through and re-issue a nonce afterwards.
        if ($stored !== $this->nonce && Cache::get($inFlightKey)) {
            return;
        }

        // Consume the nonce AND publish an in-flight marker. The marker is
        // cleared in the finally block below so any state — success, failure,
        // exception — releases the gate for the next scan.
        Cache::forget($nonceKey);
        Cache::put($inFlightKey, now()->toIso8601String(), now()->addSeconds(20));

        try {
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

            $existing = FaceAttendance::where('employee_number', $profile->employee_number)
                ->where('attendance_date', $today)
                ->first();

            $field = $this->resolveNextPhaseField($existing);

            if ($field === null) {
                $this->logAudit($profile->id, 'duplicate', $result->score, $result->liveness, $result->quality, $hash, null, $bytes, 'all_phases_recorded');
                $this->employeeName = $profile->full_name;
                $this->employeeNumber = $profile->employee_number;
                $this->modalType = 'duplicate';
                $this->refreshNonce();

                return;
            }

            if (app(AttendanceCooldown::class)->isActive($existing, now())) {
                $this->logAudit($profile->id, 'cooldown', $result->score, $result->liveness, $result->quality, $hash, null, $bytes, 'recent_capture');
                $this->employeeName = $profile->full_name;
                $this->employeeNumber = $profile->employee_number;
                $this->modalType = 'cooldown';
                $this->failReason = AttendanceCooldown::MESSAGE;
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
        } finally {
            Cache::forget($inFlightKey);
        }
    }

    private function resolveNextPhaseField(?FaceAttendance $existing): ?string
    {
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
        $persistEvents = ['verify_fail', 'spoof_suspected', 'duplicate'];
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
