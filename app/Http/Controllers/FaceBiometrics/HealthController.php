<?php

namespace App\Http\Controllers\FaceBiometrics;

use App\Http\Controllers\Controller;
use App\Services\FaceBiometricService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(FaceBiometricService $svc): JsonResponse
    {
        $pythonOk = $svc->health();

        try {
            DB::select('SELECT 1 FROM pg_extension WHERE extname = ?', ['vector']);
            $pgvectorOk = true;
        } catch (\Throwable) {
            $pgvectorOk = false;
        }

        $modelsCount = 0;
        if ($pythonOk) {
            try {
                $detail = app('Illuminate\Http\Client\Factory')::timeout(2)
                    ->withToken(config('face_biometrics.service_token'))
                    ->get(config('face_biometrics.service_url').'/health')
                    ->json();
                $modelsCount = $detail['models_loaded'] ?? 0;
                if (is_bool($modelsCount)) {
                    $modelsCount = $modelsCount ? 3 : 0;
                }
            } catch (\Throwable) {
                $modelsCount = 0;
            }
        }

        return response()->json([
            'python_service' => $pythonOk,
            'pgvector' => $pgvectorOk,
            'models_loaded_count_estimate' => $modelsCount,
        ]);
    }
}
