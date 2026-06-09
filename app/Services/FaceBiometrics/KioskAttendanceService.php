<?php

namespace App\Services\FaceBiometrics;

use App\Models\Attendance;
use App\Models\FaceBiometrics\FaceAttendance;
use App\Models\FaceBiometrics\FaceAuditLog;
use App\Models\FaceBiometrics\FaceEmbedding;
use App\Models\Profile;
use App\ValueObjects\FaceBiometrics\VerifyResult;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Server half of the edge-kiosk attendance flow.
 *
 * The .NET kiosk (kiosk-face-biometrics) computes the 512-d ArcFace embedding on
 * the device and POSTs only the vector — never an image — to
 * /api/v1/attendance/face-scan. This service therefore reuses the existing
 * pgvector matching (FaceEmbedding::searchTopK) and the same threshold/margin
 * rule and attendance-recording logic as the in-browser AttendanceKiosk Livewire
 * component, but skips the Python /extract step (which only exists to turn an
 * *image* into an embedding).
 *
 * It deliberately does not modify FaceBiometricService or AttendanceKiosk; the
 * threshold *values* are read from the shared config so there is a single source
 * of truth, and only the small decision rule is mirrored here.
 */
class KioskAttendanceService
{
    private float $matchThreshold;

    private float $matchMargin;

    public function __construct(private AttendanceCooldown $cooldown)
    {
        $this->matchThreshold = (float) config('face_biometrics.match_threshold');
        $this->matchMargin = (float) config('face_biometrics.match_margin');
    }

    /**
     * Verify an edge-computed embedding and record attendance, idempotent on the
     * client-generated scan UUID.
     *
     * Idempotency (BUILD_PROMPT §9): the kiosk re-sends queued scans with the same
     * scan_uuid after an outage. The first call's response is cached so a replay
     * returns the identical result instead of advancing the employee to the next
     * attendance phase. CACHE_STORE=database in production survives restarts.
     *
     * @param  array<int, float>  $embedding  The 512 L2-normalized components.
     * @return array<string, mixed> The /api/v1/attendance/face-scan response payload.
     */
    public function process(
        array $embedding,
        float $liveness,
        float $quality,
        string $kioskId,
        CarbonInterface $capturedAt,
        string $scanUuid
    ): array {
        $cacheKey = "kiosk_scan_{$scanUuid}";

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $result = $this->matchEmbedding($embedding, $liveness, $quality);
        $response = $this->record($result, $kioskId, $capturedAt, $scanUuid);

        Cache::put($cacheKey, $response, now()->addHours(48));

        return $response;
    }

    /**
     * Match an embedding against enrolled templates and apply the threshold/margin
     * rule. Mirrors FaceBiometricService::verify() minus the /extract call.
     *
     * @param  array<int, float>  $embedding
     */
    public function matchEmbedding(array $embedding, float $liveness, float $quality): VerifyResult
    {
        $matches = FaceEmbedding::searchTopK($embedding, 2);

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

        if (! $profile) {
            return new VerifyResult(
                matched: false,
                profile: null,
                score: $topScore,
                secondScore: $secondScore,
                margin: $margin,
                liveness: $liveness,
                quality: $quality,
                reason: 'no_match',
            );
        }

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

    /**
     * Record attendance for a verified match, resolving the next attendance phase.
     * Mirrors AttendanceKiosk::verifyAndRecord()'s recording half (FaceAttendance +
     * legacy Attendance + audit log + faceProfile bookkeeping).
     *
     * @return array<string, mixed> The response payload.
     */
    public function record(VerifyResult $result, string $kioskId, CarbonInterface $capturedAt, string $scanUuid): array
    {
        if (! $result->matched || ! $result->profile) {
            $this->audit(null, 'verify_fail', $result, $kioskId, $result->reason);

            return $this->payload($result, null);
        }

        $profile = $result->profile;

        // The attendance day/time come from this resolved moment, NOT the raw
        // device timestamp, so a kiosk with a skewed clock or timezone (or a
        // stale queued scan) can never land on the wrong day.
        $moment = $this->resolveAttendanceMoment($capturedAt, now());
        $date = $moment->toDateString();

        $existing = FaceAttendance::where('employee_number', $profile->employee_number)
            ->where('attendance_date', $date)
            ->first();

        $field = $this->nextPhaseField($existing);

        if ($field === null) {
            $this->audit($profile->id, 'duplicate', $result, $kioskId, 'all_phases_recorded');

            return $this->payload($result, [
                'recorded' => false,
                'phase' => 'afternoon_out',
                'time' => $this->formatTime($existing?->afternoon_out),
                'duplicate' => true,
                'cooldown' => false,
            ]);
        }

        $remaining = $this->cooldown->secondsRemaining($existing, $moment);

        if ($remaining > 0) {
            $this->audit($profile->id, 'cooldown', $result, $kioskId, 'recent_capture');

            return $this->payload($result, [
                'recorded' => false,
                'phase' => $field,
                'time' => '',
                'duplicate' => false,
                'cooldown' => true,
                'retry_after_seconds' => $remaining,
                'message' => AttendanceCooldown::MESSAGE,
            ]);
        }

        $time = $moment->format('H:i:s');

        FaceAttendance::updateOrCreate(
            ['employee_number' => $profile->employee_number, 'attendance_date' => $date],
            [
                'profile_id' => $profile->id,
                $field => $time,
                'match_score' => $result->score,
                'liveness_score' => $result->liveness,
                'quality_score' => $result->quality,
                'kiosk_id' => $kioskId,
                'verification_method' => 'face_v2',
                'top_match_margin' => $result->margin,
            ]
        );

        Attendance::updateOrCreate(
            ['employee_number' => $profile->employee_number, 'attendance_date' => $date],
            [
                'profile_id' => $profile->id,
                $field => $time,
            ]
        );

        $this->audit($profile->id, 'verify_ok', $result, $kioskId, 'ok');

        $profile->faceProfile?->update([
            'last_verified_at' => now(),
            'last_match_score' => $result->score,
        ]);

        return $this->payload($result, [
            'recorded' => true,
            'phase' => $field,
            'time' => $moment->format('h:i A'),
            'duplicate' => false,
            'cooldown' => false,
        ]);
    }

    /**
     * Resolve the authoritative attendance moment, guarding against kiosk clock
     * or timezone skew.
     *
     * The device-supplied capture time is honored only when, normalized to the
     * HRIS timezone, it falls on the current server day and is not in the future.
     * Otherwise the server clock wins. This keeps a legitimately delayed *same
     * day* offline replay accurate to its real capture time, while ensuring a
     * misconfigured kiosk (wrong clock, wrong timezone, or a stale queued scan)
     * can never record attendance on the wrong day — the failure the in-browser
     * AttendanceKiosk already avoids by always using today()/now(). A morning
     * scan can therefore no longer land on yesterday's afternoon-out.
     */
    public function resolveAttendanceMoment(CarbonInterface $capturedAt, CarbonInterface $serverNow): CarbonInterface
    {
        $captured = $capturedAt->copy()->setTimezone($serverNow->getTimezone());

        if (! $captured->isSameDay($serverNow) || $captured->greaterThan($serverNow->copy()->addMinutes(2))) {
            return $serverNow->copy();
        }

        return $captured;
    }

    /**
     * The next unrecorded attendance phase for the day, or null when every phase
     * is already recorded. Identical ordering to AttendanceKiosk.
     */
    private function nextPhaseField(?FaceAttendance $existing): ?string
    {
        foreach (['morning_in', 'morning_out', 'afternoon_in', 'afternoon_out'] as $field) {
            if (! $existing || empty($existing->{$field})) {
                return $field;
            }
        }

        return null;
    }

    private function formatTime(?string $stored): string
    {
        if (empty($stored)) {
            return '';
        }

        try {
            return Carbon::parse($stored)->format('h:i A');
        } catch (\Throwable) {
            return (string) $stored;
        }
    }

    private function audit(?int $profileId, string $event, VerifyResult $result, string $kioskId, string $reason): void
    {
        // The edge path transmits no image (privacy: embedding-only), so there is
        // no photo hash/blob to persist — unlike the image-based Livewire kiosk.
        FaceAuditLog::create([
            'profile_id' => $profileId,
            'event' => $event,
            'match_score' => $result->score,
            'liveness_score' => $result->liveness,
            'quality_score' => $result->quality,
            'reason' => $reason,
            'photo_hash' => null,
            'photo_path' => null,
            'ip_address' => request()->ip(),
            'kiosk_id' => $kioskId,
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
            'source' => 'kiosk',
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $attendance
     * @return array<string, mixed>
     */
    private function payload(VerifyResult $result, ?array $attendance): array
    {
        return [
            'matched' => $result->matched,
            'profile' => $result->matched && $result->profile ? [
                'id' => $result->profile->id,
                'employee_number' => $result->profile->employee_number,
                'full_name' => $result->profile->full_name,
            ] : null,
            'score' => round($result->score, 6),
            'margin' => round($result->margin, 6),
            'reason' => $result->reason,
            'attendance' => $attendance,
        ];
    }
}
