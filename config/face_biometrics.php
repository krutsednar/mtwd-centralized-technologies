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
];
