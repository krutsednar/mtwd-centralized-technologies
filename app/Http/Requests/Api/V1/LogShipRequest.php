<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates POST /api/v1/kiosks/{kioskId}/logs (BUILD_PROMPT §3.4).
 */
class LogShipRequest extends FormRequest
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
            'entries' => ['required', 'array', 'max:500'],
            'entries.*.ts' => ['nullable', 'date'],
            'entries.*.level' => ['nullable', 'string', 'max:16'],
            'entries.*.message' => ['required', 'string'],
            'entries.*.context' => ['nullable', 'array'],
        ];
    }
}
