<?php

namespace Tests\Feature\FaceBiometrics;

use App\Livewire\FaceBiometrics\AttendanceKiosk;
use App\Services\FaceBiometricService;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    // Isolate cache state per test. The array driver is in-memory and
    // discarded with the container at the end of each test, so kiosk nonce
    // keys cannot leak into the developer's local cache table.
    config(['cache.default' => 'array']);
});

it('processes the first scan after the nonce expired overnight', function () {
    // Regression test for the "fails to capture attendance before 7 AM" bug:
    // a kiosk left open past the nonce's cache TTL (an overnight/weekend idle
    // gap) loses its nonce. That is NOT a replay — the early-shift employee's
    // scan must still be recorded, not rejected as `replay_rejected`.
    $this->mock(FaceBiometricService::class)
        ->shouldReceive('verify')
        ->once()
        ->andThrow(new \RuntimeException('mocked failure'));

    $component = Livewire::test(AttendanceKiosk::class);
    $kioskId = $component->get('kioskId');

    // Wipe the nonce without setting an in-flight marker — simulates an
    // expired nonce (tab open overnight) rather than a sibling tap.
    Cache::forget("face_kiosk_nonce_{$kioskId}");

    $component->call('verifyAndRecord', 'data:image/jpeg;base64,'.base64_encode('x'));

    // service_error proves we got past the nonce gate and reached the service
    // layer instead of being dropped with `replay_rejected`.
    $component
        ->assertSet('modalType', 'fail')
        ->assertSet('failReason', 'service_error');
});

it('processes a scan whose nonce was superseded when no scan is in flight', function () {
    // A mismatched nonce with no in-flight marker is a stale tab (e.g. another
    // tab refreshed the nonce), not a concurrent double-tap. Let it through.
    $this->mock(FaceBiometricService::class)
        ->shouldReceive('verify')
        ->once()
        ->andThrow(new \RuntimeException('mocked failure'));

    $component = Livewire::test(AttendanceKiosk::class);
    $kioskId = $component->get('kioskId');

    Cache::put("face_kiosk_nonce_{$kioskId}", 'someone-elses-nonce');

    $component->call('verifyAndRecord', 'data:image/jpeg;base64,'.base64_encode('x'));

    $component
        ->assertSet('modalType', 'fail')
        ->assertSet('failReason', 'service_error');
});

it('still swallows a duplicate tap when the nonce mismatches and a scan is in flight', function () {
    $component = Livewire::test(AttendanceKiosk::class);
    $kioskId = $component->get('kioskId');

    // A genuine concurrent double-tap: the sibling scan consumed the nonce and
    // is still mid-flight (in-flight marker present) while this tap carries a
    // now-superseded nonce. This must stay guarded.
    Cache::put("face_kiosk_nonce_{$kioskId}", 'someone-elses-nonce');
    Cache::put("face_kiosk_inflight_{$kioskId}", now()->toIso8601String(), 20);

    $component->call('verifyAndRecord', 'data:image/jpeg;base64,'.base64_encode('x'));

    $component
        ->assertSet('modalType', null)
        ->assertSet('failReason', null);
});

it('silently ignores a duplicate tap while a sibling scan is in flight', function () {
    $component = Livewire::test(AttendanceKiosk::class);
    $kioskId = $component->get('kioskId');

    // Simulate the state during a slow service call: the first tap consumed
    // the nonce and published the in-flight marker; the second tap arrives
    // while the marker is still present.
    Cache::forget("face_kiosk_nonce_{$kioskId}");
    Cache::put("face_kiosk_inflight_{$kioskId}", now()->toIso8601String(), 20);

    $component->call('verifyAndRecord', 'data:image/jpeg;base64,'.base64_encode('x'));

    // No modal, no failReason — the duplicate tap is swallowed.
    $component
        ->assertSet('modalType', null)
        ->assertSet('failReason', null);
});

it('passes the nonce gate when the nonce matches and reaches the service layer', function () {
    // Stub the service to throw a generic Throwable so we can verify the
    // request reached the service call (i.e. the gate let it through)
    // without needing a real biometric match.
    $this->mock(FaceBiometricService::class)
        ->shouldReceive('verify')
        ->once()
        ->andThrow(new \RuntimeException('mocked failure'));

    $component = Livewire::test(AttendanceKiosk::class);

    $component->call('verifyAndRecord', 'data:image/jpeg;base64,'.base64_encode('x'));

    // service_error means we hit the generic Throwable catch — proves we
    // got past the nonce gate (otherwise failReason would be replay_rejected).
    $component
        ->assertSet('modalType', 'fail')
        ->assertSet('failReason', 'service_error');
});

it('clears the in-flight marker even when the service call fails', function () {
    $this->mock(FaceBiometricService::class)
        ->shouldReceive('verify')
        ->once()
        ->andThrow(new \RuntimeException('mocked failure'));

    $component = Livewire::test(AttendanceKiosk::class);
    $kioskId = $component->get('kioskId');

    $component->call('verifyAndRecord', 'data:image/jpeg;base64,'.base64_encode('x'));

    // The finally block must run regardless of how the body returned.
    expect(Cache::get("face_kiosk_inflight_{$kioskId}"))->toBeNull();
});
