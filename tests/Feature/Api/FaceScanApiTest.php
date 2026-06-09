<?php

namespace Tests\Feature\Api;

use App\Services\FaceBiometrics\KioskAttendanceService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // Deterministic token + in-memory cache so tests never touch the real
    // cache table or the configured production token.
    config([
        'cache.default' => 'array',
        'face_biometrics.kiosk_api_token' => 'test-kiosk-token',
    ]);
});

/**
 * @return array<string, mixed>
 */
function validScanBody(array $overrides = []): array
{
    return array_merge([
        'kiosk_id' => 'kiosk-lobby-01',
        'embedding' => array_fill(0, 512, 0.0123),
        'liveness_score' => 0.97,
        'quality_score' => 0.81,
        'model_version' => 'buffalo_l.w600k_r50.int8.v1',
        'captured_at' => '2026-06-02T08:15:30+08:00',
        'scan_uuid' => 'b1c1d1e1-0000-4000-8000-000000000001',
    ], $overrides);
}

it('rejects a face-scan with no bearer token', function () {
    $this->postJson('/api/v1/attendance/face-scan', validScanBody())
        ->assertStatus(401)
        ->assertExactJson(['message' => 'Unauthenticated.']);
});

it('rejects a face-scan with a wrong bearer token', function () {
    $this->withToken('not-the-token')
        ->postJson('/api/v1/attendance/face-scan', validScanBody())
        ->assertStatus(401);
});

it('rejects a face-scan when no server token is configured (fails closed)', function () {
    config(['face_biometrics.kiosk_api_token' => '']);

    $this->withToken('anything')
        ->postJson('/api/v1/attendance/face-scan', validScanBody())
        ->assertStatus(401);
});

it('422s an invalid face-scan body', function () {
    $this->withToken('test-kiosk-token')
        ->postJson('/api/v1/attendance/face-scan', validScanBody(['embedding' => [1, 2, 3]]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['embedding']);
});

it('records a matched scan and returns the §3.1 shape', function () {
    $payload = [
        'matched' => true,
        'profile' => ['id' => 208, 'employee_number' => '91-0024', 'full_name' => 'Dominador Ubiña Taquiga'],
        'score' => 0.62,
        'margin' => 0.21,
        'reason' => 'ok',
        'attendance' => ['recorded' => true, 'phase' => 'morning_in', 'time' => '08:15 AM', 'duplicate' => false],
    ];

    $this->mock(KioskAttendanceService::class)
        ->shouldReceive('process')->once()->andReturn($payload);

    $this->withToken('test-kiosk-token')
        ->postJson('/api/v1/attendance/face-scan', validScanBody())
        ->assertOk()
        ->assertExactJson($payload);
});

it('returns a no-match result', function () {
    $payload = [
        'matched' => false,
        'profile' => null,
        'score' => 0.30,
        'margin' => 0.02,
        'reason' => 'score_below_threshold',
        'attendance' => null,
    ];

    $this->mock(KioskAttendanceService::class)
        ->shouldReceive('process')->once()->andReturn($payload);

    $this->withToken('test-kiosk-token')
        ->postJson('/api/v1/attendance/face-scan', validScanBody())
        ->assertOk()
        ->assertJsonPath('matched', false)
        ->assertJsonPath('attendance', null);
});

it('returns a duplicate result when all phases are recorded', function () {
    $payload = [
        'matched' => true,
        'profile' => ['id' => 208, 'employee_number' => '91-0024', 'full_name' => 'Dominador Ubiña Taquiga'],
        'score' => 0.62,
        'margin' => 0.21,
        'reason' => 'ok',
        'attendance' => ['recorded' => false, 'phase' => 'afternoon_out', 'time' => '05:01 PM', 'duplicate' => true],
    ];

    $this->mock(KioskAttendanceService::class)
        ->shouldReceive('process')->once()->andReturn($payload);

    $this->withToken('test-kiosk-token')
        ->postJson('/api/v1/attendance/face-scan', validScanBody())
        ->assertOk()
        ->assertJsonPath('attendance.duplicate', true)
        ->assertJsonPath('attendance.recorded', false);
});

it('returns a cooldown result when the employee logged within the window', function () {
    $payload = [
        'matched' => true,
        'profile' => ['id' => 208, 'employee_number' => '91-0024', 'full_name' => 'Dominador Ubiña Taquiga'],
        'score' => 0.62,
        'margin' => 0.21,
        'reason' => 'ok',
        'attendance' => [
            'recorded' => false,
            'phase' => 'morning_out',
            'time' => '',
            'duplicate' => false,
            'cooldown' => true,
            'retry_after_seconds' => 95,
            'message' => 'You have recently logged. Please try again after a few minutes.',
        ],
    ];

    $this->mock(KioskAttendanceService::class)
        ->shouldReceive('process')->once()->andReturn($payload);

    $this->withToken('test-kiosk-token')
        ->postJson('/api/v1/attendance/face-scan', validScanBody())
        ->assertOk()
        ->assertJsonPath('attendance.cooldown', true)
        ->assertJsonPath('attendance.recorded', false)
        ->assertJsonPath('attendance.retry_after_seconds', 95)
        ->assertJsonPath('attendance.message', 'You have recently logged. Please try again after a few minutes.');
});

it('replays an idempotent scan from cache without re-matching', function () {
    // Pre-seed the result the original scan produced. The real service must
    // short-circuit on the cached scan_uuid and never reach pgvector (which is
    // unavailable under the sqlite test DB), proving §9 idempotency.
    $body = validScanBody(['scan_uuid' => 'dup-uuid-123']);
    $cached = [
        'matched' => true,
        'profile' => ['id' => 1, 'employee_number' => '00-0001', 'full_name' => 'Cached Person'],
        'score' => 0.7,
        'margin' => 0.3,
        'reason' => 'ok',
        'attendance' => ['recorded' => true, 'phase' => 'morning_in', 'time' => '08:00 AM', 'duplicate' => false],
    ];
    Cache::put('kiosk_scan_dup-uuid-123', $cached, now()->addHour());

    $this->withToken('test-kiosk-token')
        ->postJson('/api/v1/attendance/face-scan', $body)
        ->assertOk()
        ->assertExactJson($cached);
});
