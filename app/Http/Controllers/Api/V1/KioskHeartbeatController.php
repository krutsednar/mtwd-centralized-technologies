<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\HeartbeatRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * POST /api/v1/kiosks/{kioskId}/heartbeat — liveness/health ping from a kiosk
 * (BUILD_PROMPT §3.3). Records the latest status for observability and returns
 * server time + update availability. Auto-update orchestration is a later phase;
 * for now this always reports "no update".
 */
class KioskHeartbeatController extends Controller
{
    public function __invoke(HeartbeatRequest $request, string $kioskId): JsonResponse
    {
        $data = $request->validated();

        Cache::put("kiosk_heartbeat_{$kioskId}", [
            'at' => now()->toIso8601String(),
            'app_version' => $data['app_version'] ?? null,
            'model_version' => $data['model_version'] ?? null,
            'queue_depth' => $data['queue_depth'] ?? null,
            'gpu' => $data['gpu'] ?? null,
        ], now()->addMinutes(10));

        return response()->json([
            'ok' => true,
            'server_time' => now()->toIso8601String(),
            'remote_config' => null,
            'update' => [
                'available' => false,
                'version' => null,
                'url' => null,
            ],
        ]);
    }
}
