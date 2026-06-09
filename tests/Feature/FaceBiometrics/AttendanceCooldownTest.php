<?php

namespace Tests\Feature\FaceBiometrics;

use App\Models\FaceBiometrics\FaceAttendance;
use App\Services\FaceBiometrics\AttendanceCooldown;
use Illuminate\Support\Carbon;

beforeEach(function () {
    config(['face_biometrics.attendance_cooldown_seconds' => 120]);
});

/**
 * Build an unsaved FaceAttendance — the cooldown rule reads attributes only, so
 * these never touch the (pgvector-only) database.
 *
 * @param  array<string, string>  $phases
 */
function cooldownFaceAttendance(array $phases, string $date = '2026-06-08'): FaceAttendance
{
    return new FaceAttendance(array_merge(['attendance_date' => $date], $phases));
}

it('is inactive when the employee has no record yet', function () {
    expect((new AttendanceCooldown)->isActive(null, now()))->toBeFalse();
});

it('is inactive when no phase has been recorded', function () {
    expect((new AttendanceCooldown)->isActive(cooldownFaceAttendance([]), now()))->toBeFalse();
});

it('is active within two minutes of the last capture', function () {
    $att = cooldownFaceAttendance(['morning_in' => '08:00:00']);
    $reference = Carbon::parse('2026-06-08 08:00:30');

    $cooldown = new AttendanceCooldown;

    expect($cooldown->isActive($att, $reference))->toBeTrue()
        ->and($cooldown->secondsRemaining($att, $reference))->toBe(90);
});

it('is inactive once the window has elapsed', function () {
    $att = cooldownFaceAttendance(['morning_in' => '08:00:00']);
    $reference = Carbon::parse('2026-06-08 08:02:30');

    expect((new AttendanceCooldown)->isActive($att, $reference))->toBeFalse();
});

it('measures from the most recent phase when several are recorded', function () {
    // morning_in is hours old but morning_out is 30s old → still cooling down.
    $att = cooldownFaceAttendance(['morning_in' => '08:00:00', 'morning_out' => '12:00:00']);
    $reference = Carbon::parse('2026-06-08 12:00:30');

    expect((new AttendanceCooldown)->isActive($att, $reference))->toBeTrue();
});

it('honors a configured cooldown window', function () {
    config(['face_biometrics.attendance_cooldown_seconds' => 300]);

    $att = cooldownFaceAttendance(['morning_in' => '08:00:00']);
    $reference = Carbon::parse('2026-06-08 08:04:00'); // 240s elapsed, window is 300s

    expect((new AttendanceCooldown)->isActive($att, $reference))->toBeTrue();
});
