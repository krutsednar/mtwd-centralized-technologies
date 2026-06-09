<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FaceScanRequest;
use App\Services\FaceBiometrics\KioskAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * POST /api/v1/attendance/face-scan — verify an edge-computed embedding and
 * record attendance in one round-trip (BUILD_PROMPT §3.1).
 */
class FaceScanController extends Controller
{
    public function __invoke(FaceScanRequest $request, KioskAttendanceService $service): JsonResponse
    {
        $data = $request->validated();

        // captured_at is the device's capture time. The service honors it as the
        // attendance moment only when it is sane (same server day, not in the
        // future) and otherwise falls back to the server clock, so a skewed kiosk
        // can never record on the wrong day (§9). Normalize to the HRIS clock here
        // so the comparison and any honored value share one wall-clock.
        $capturedAt = Carbon::parse($data['captured_at'])->setTimezone(config('app.timezone'));

        $payload = $service->process(
            embedding: array_map('floatval', $data['embedding']),
            liveness: (float) $data['liveness_score'],
            quality: (float) $data['quality_score'],
            kioskId: $data['kiosk_id'],
            capturedAt: $capturedAt,
            scanUuid: $data['scan_uuid'],
        );

        return response()->json($payload);
    }
}
