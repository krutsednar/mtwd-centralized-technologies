<?php

return [
    'service_url' => env('FACE_BIO_SERVICE_URL', 'http://127.0.0.1:7870'),
    'service_token' => env('FACE_BIO_SERVICE_TOKEN', ''),
    'match_threshold' => (float) env('FACE_BIO_MATCH_THRESHOLD', 0.42),
    'match_margin' => (float) env('FACE_BIO_MATCH_MARGIN', 0.04),
    'liveness_threshold' => (float) env('FACE_BIO_LIVENESS_THRESHOLD', 0.85),
    'quality_threshold' => (float) env('FACE_BIO_QUALITY_THRESHOLD', 0.55),
    'enroll_quality_threshold' => (float) env('FACE_BIO_ENROLL_QUALITY_THRESHOLD', 0.45),
    'max_upload_mb' => (int) env('FACE_BIO_MAX_UPLOAD_MB', 20),

    // Edge-kiosk integration (kiosk-face-biometrics .NET client → /api/v1).
    // The kiosk authenticates with this bearer token; an empty value fails closed
    // (every request is rejected). The model_version is echoed in audit/attendance
    // records so server-stored vectors can be traced to the producing edge model.
    'kiosk_api_token' => env('FACE_BIO_KIOSK_TOKEN', ''),
    'model_version' => env('FACE_BIO_MODEL_VERSION', 'buffalo_l.w600k_r50.int8.v1'),

    // Minimum gap between two accepted attendance captures for the same employee
    // on the same day. A capture landing inside this window is refused with the
    // "recently logged" message instead of advancing to the next phase — this
    // stops the auto-capture loop from recording the next phase moments after the
    // previous one. Shared by the web (Livewire) kiosk and the /api/v1 edge kiosk.
    'attendance_cooldown_seconds' => (int) env('FACE_BIO_ATTENDANCE_COOLDOWN_SECONDS', 120),
];
