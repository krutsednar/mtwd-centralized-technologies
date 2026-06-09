<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates POST /api/v1/kiosks/{kioskId}/heartbeat (BUILD_PROMPT §3.3). All
 * fields are optional so a heartbeat is never rejected for a missing metric.
 */
class HeartbeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'app_version' => ['nullable', 'string', 'max:32'],
            'model_version' => ['nullable', 'string', 'max:128'],
            'queue_depth' => ['nullable', 'integer', 'min:0'],
            'last_scan_at' => ['nullable', 'date'],
            'uptime_seconds' => ['nullable', 'integer', 'min:0'],
            'gpu' => ['nullable', 'string', 'max:128'],
        ];
    }
}
