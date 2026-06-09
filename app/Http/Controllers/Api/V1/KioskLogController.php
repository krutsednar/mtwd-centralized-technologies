<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LogShipRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * POST /api/v1/kiosks/{kioskId}/logs — accept a batch of structured log entries
 * shipped from a kiosk (BUILD_PROMPT §3.4). Entries are written to the server log
 * channel, tagged with the kiosk id. Returns 202 Accepted.
 */
class KioskLogController extends Controller
{
    public function __invoke(LogShipRequest $request, string $kioskId): Response
    {
        foreach ($request->validated()['entries'] as $entry) {
            Log::log(
                $this->mapLevel($entry['level'] ?? 'info'),
                '[kiosk:'.$kioskId.'] '.($entry['message'] ?? ''),
                [
                    'kiosk_id' => $kioskId,
                    'ts' => $entry['ts'] ?? null,
                    'context' => $entry['context'] ?? null,
                ],
            );
        }

        return response()->noContent(Response::HTTP_ACCEPTED);
    }

    /**
     * Map a kiosk log level (Serilog-style names) to a PSR-3 / Monolog level.
     */
    private function mapLevel(string $level): string
    {
        return match (strtolower($level)) {
            'fatal', 'critical' => 'critical',
            'error' => 'error',
            'warning', 'warn' => 'warning',
            'information', 'info' => 'info',
            'debug' => 'debug',
            'verbose', 'trace' => 'debug',
            default => 'info',
        };
    }
}
