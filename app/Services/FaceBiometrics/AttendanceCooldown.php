<?php

namespace App\Services\FaceBiometrics;

use App\Models\FaceBiometrics\FaceAttendance;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The shared "recently logged" attendance cooldown rule.
 *
 * Both the in-browser AttendanceKiosk (Livewire) and the edge-kiosk
 * /api/v1/attendance/face-scan path must refuse a capture that lands within the
 * cooldown window of the employee's most recent capture that day. Centralizing
 * the rule here keeps the two paths in lockstep and keeps it unit testable
 * without the (pgvector-only) database.
 */
class AttendanceCooldown
{
    public const MESSAGE = 'You have recently logged. Please try again after a few minutes.';

    /** @var list<string> */
    private const PHASE_FIELDS = ['morning_in', 'morning_out', 'afternoon_in', 'afternoon_out'];

    /**
     * Whether a capture at $reference falls inside the cooldown window opened by
     * the employee's most recent recorded phase that day.
     */
    public function isActive(?FaceAttendance $existing, CarbonInterface $reference): bool
    {
        return $this->secondsRemaining($existing, $reference) > 0;
    }

    /**
     * Seconds the employee must still wait before another capture is accepted, or
     * 0 when there is no recent capture / the window has already elapsed.
     */
    public function secondsRemaining(?FaceAttendance $existing, CarbonInterface $reference): int
    {
        $last = $this->lastCaptureAt($existing);

        if ($last === null) {
            return 0;
        }

        $elapsed = abs($reference->getTimestamp() - $last->getTimestamp());

        return (int) max(0, $this->windowSeconds() - $elapsed);
    }

    /**
     * The most recent recorded phase that day as a datetime, or null when no
     * phase has been recorded yet.
     */
    public function lastCaptureAt(?FaceAttendance $existing): ?Carbon
    {
        if ($existing === null) {
            return null;
        }

        $date = $existing->attendance_date instanceof CarbonInterface
            ? $existing->attendance_date->toDateString()
            : (string) $existing->attendance_date;

        $latest = null;

        foreach (self::PHASE_FIELDS as $field) {
            $value = $existing->{$field};

            if (empty($value)) {
                continue;
            }

            $candidate = Carbon::parse("{$date} {$value}");

            if ($latest === null || $candidate->greaterThan($latest)) {
                $latest = $candidate;
            }
        }

        return $latest;
    }

    public function windowSeconds(): int
    {
        return (int) config('face_biometrics.attendance_cooldown_seconds', 120);
    }
}
