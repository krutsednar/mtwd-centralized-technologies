<?php

namespace Tests\Feature\Api;

beforeEach(function () {
    config([
        'cache.default' => 'array',
        'face_biometrics.kiosk_api_token' => 'test-kiosk-token',
    ]);
});

it('acknowledges a heartbeat', function () {
    $this->withToken('test-kiosk-token')
        ->postJson('/api/v1/kiosks/kiosk-lobby-01/heartbeat', [
            'app_version' => '1.0.0',
            'model_version' => 'buffalo_l.w600k_r50.int8.v1',
            'queue_depth' => 0,
            'uptime_seconds' => 3600,
            'gpu' => 'Intel Iris Xe',
        ])
        ->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonStructure(['ok', 'server_time', 'remote_config', 'update' => ['available', 'version', 'url']]);
});

it('rejects an unauthenticated heartbeat', function () {
    $this->postJson('/api/v1/kiosks/kiosk-lobby-01/heartbeat', [])
        ->assertStatus(401);
});

it('accepts a batch of shipped logs with 202', function () {
    $this->withToken('test-kiosk-token')
        ->postJson('/api/v1/kiosks/kiosk-lobby-01/logs', [
            'entries' => [
                ['ts' => '2026-06-02T08:15:30+08:00', 'level' => 'Error', 'message' => 'boom', 'context' => ['k' => 'v']],
                ['ts' => '2026-06-02T08:15:31+08:00', 'level' => 'Information', 'message' => 'ok'],
            ],
        ])
        ->assertStatus(202);
});

it('422s a log batch with no entries', function () {
    $this->withToken('test-kiosk-token')
        ->postJson('/api/v1/kiosks/kiosk-lobby-01/logs', ['entries' => []])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['entries']);
});
