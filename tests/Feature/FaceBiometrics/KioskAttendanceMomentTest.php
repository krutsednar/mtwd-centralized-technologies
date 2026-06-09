<?php

namespace Tests\Feature\FaceBiometrics;

use App\Services\FaceBiometrics\AttendanceCooldown;
use App\Services\FaceBiometrics\KioskAttendanceService;
use Illuminate\Support\Carbon;

/**
 * resolveAttendanceMoment() is the guard behind the "morning-in landed on
 * yesterday's afternoon-out" fix: the device timestamp is trusted only when it
 * is a sane, same-server-day value; otherwise the server clock is authoritative.
 */
function kioskService(): KioskAttendanceService
{
    return new KioskAttendanceService(new AttendanceCooldown);
}

it('honors a sane same-day capture time', function () {
    $serverNow = Carbon::parse('2026-06-09 09:00:00', 'Asia/Manila');
    $captured = Carbon::parse('2026-06-09 08:59:30', 'Asia/Manila');

    expect(kioskService()->resolveAttendanceMoment($captured, $serverNow)->toDateTimeString())
        ->toBe('2026-06-09 08:59:30');
});

it('falls back to the server clock when the capture is a previous day', function () {
    // The exact bug: a today scan whose device says yesterday must NOT write to
    // yesterday's row — it lands on today via the server clock.
    $serverNow = Carbon::parse('2026-06-09 09:00:00', 'Asia/Manila');
    $captured = Carbon::parse('2026-06-08 16:55:00', 'Asia/Manila');

    expect(kioskService()->resolveAttendanceMoment($captured, $serverNow)->toDateTimeString())
        ->toBe('2026-06-09 09:00:00');
});

it('falls back to the server clock when the capture is in the future', function () {
    $serverNow = Carbon::parse('2026-06-09 09:00:00', 'Asia/Manila');
    $captured = Carbon::parse('2026-06-09 09:30:00', 'Asia/Manila');

    expect(kioskService()->resolveAttendanceMoment($captured, $serverNow)->toDateTimeString())
        ->toBe('2026-06-09 09:00:00');
});

it('tolerates a small clock skew that is barely in the future', function () {
    $serverNow = Carbon::parse('2026-06-09 09:00:00', 'Asia/Manila');
    $captured = Carbon::parse('2026-06-09 09:01:00', 'Asia/Manila'); // 1 min, within the 2 min tolerance

    expect(kioskService()->resolveAttendanceMoment($captured, $serverNow)->toDateTimeString())
        ->toBe('2026-06-09 09:01:00');
});

it('normalizes a UTC-offset capture to the HRIS day before deciding', function () {
    // 00:30Z is 08:30 in Manila — same server day, so it is honored and converted.
    $serverNow = Carbon::parse('2026-06-09 09:00:00', 'Asia/Manila');
    $captured = Carbon::parse('2026-06-09T00:30:00+00:00');

    $moment = kioskService()->resolveAttendanceMoment($captured, $serverNow);

    expect($moment->toDateTimeString())->toBe('2026-06-09 08:30:00')
        ->and($moment->getTimezone()->getName())->toBe('Asia/Manila');
});

it('falls back when an odd offset resolves to a different HRIS day', function () {
    // 02:00 at +14:00 is 2026-06-08 12:00Z = 2026-06-08 20:00 in Manila — a
    // previous server day, so the device time is rejected for the server clock.
    $serverNow = Carbon::parse('2026-06-09 09:00:00', 'Asia/Manila');
    $captured = Carbon::parse('2026-06-09T02:00:00+14:00');

    expect(kioskService()->resolveAttendanceMoment($captured, $serverNow)->toDateTimeString())
        ->toBe('2026-06-09 09:00:00');
});
