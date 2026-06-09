<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates edge-kiosk requests to the /api/v1 surface with a static bearer
 * token (config('face_biometrics.kiosk_api_token')).
 *
 * This is machine-to-machine auth for unattended kiosks, intentionally lighter
 * than Sanctum: no per-user session, no DB lookup, no migration. The token is
 * compared in constant time. An unset/empty configured token fails closed so a
 * misconfigured server never accepts unauthenticated scans.
 *
 * The 401 body mirrors the framework default ({"message": "Unauthenticated."})
 * so the .NET client's HttpStatusCode.Unauthorized mapping behaves identically
 * against the real API and the dev mock server.
 */
class EnsureKioskToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('face_biometrics.kiosk_api_token');
        $provided = (string) $request->bearerToken();

        if ($expected === '' || $provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
