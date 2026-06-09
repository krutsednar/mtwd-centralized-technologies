<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates POST /api/v1/attendance/face-scan (BUILD_PROMPT §3.1). A validation
 * failure yields Laravel's native 422 {message, errors} body, which the .NET
 * client maps to its api.validation error.
 */
class FaceScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authentication is enforced by the EnsureKioskToken middleware.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kiosk_id' => ['required', 'string', 'max:64'],
            'embedding' => ['required', 'array', 'size:512'],
            'embedding.*' => ['numeric'],
            'liveness_score' => ['required', 'numeric', 'between:0,1'],
            'quality_score' => ['required', 'numeric', 'between:0,1'],
            'model_version' => ['required', 'string', 'max:128'],
            'captured_at' => ['required', 'date'],
            'scan_uuid' => ['required', 'string', 'max:64'],
        ];
    }
}
