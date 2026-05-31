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

it('rejects a request with a mismatched nonce', function () {
    $component = Livewire::test(AttendanceKiosk::class);
    $kioskId = $component->get('kioskId');

    // Tamper the stored nonce so it no longer matches the component state.
    Cache::put("face_kiosk_nonce_{$kioskId}", 'someone-elses-nonce');

    $component->call('verifyAndRecord', 'data:image/jpeg;base64,'.base64_encode('x'));

    $component
        ->assertSet('modalType', 'fail')
        ->assertSet('failReason', 'replay_rejected');
});

it('rejects a stale request when the nonce is gone and no scan is in flight', function () {
    $component = Livewire::test(AttendanceKiosk::class);
    $kioskId = $component->get('kioskId');

    // Wipe the nonce without setting an in-flight marker — simulates an
    // expired nonce (e.g. tab open overnight) rather than a sibling tap.
    Cache::forget("face_kiosk_nonce_{$kioskId}");

    $component->call('verifyAndRecord', 'data:image/jpeg;base64,'.base64_encode('x'));

    $component
        ->assertSet('modalType', 'fail')
        ->assertSet('failReason', 'replay_rejected');
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
