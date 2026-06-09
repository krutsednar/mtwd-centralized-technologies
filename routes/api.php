<?php

use App\Http\Controllers\Api\V1\FaceScanController;
use App\Http\Controllers\Api\V1\KioskHeartbeatController;
use App\Http\Controllers\Api\V1\KioskLogController;
use App\Http\Controllers\Api\V1\ProfilePhotoController;
use App\Http\Middleware\EnsureKioskToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Edge-kiosk integration API (kiosk-face-biometrics .NET client)
|--------------------------------------------------------------------------
|
| Versioned, stateless JSON surface consumed by the on-device face kiosk.
| Authenticated by a static bearer token (EnsureKioskToken) — not Sanctum —
| since these are unattended machine-to-machine calls. The inline
| throttle:120,1 yields the §3 429 + Retry-After contract without a named
| limiter. This file is additive; it does not touch the web/Livewire kiosk.
|
*/

Route::prefix('v1')
    ->middleware([EnsureKioskToken::class, 'throttle:120,1'])
    ->group(function () {
        Route::post('attendance/face-scan', FaceScanController::class);
        Route::post('kiosks/{kioskId}/heartbeat', KioskHeartbeatController::class);
        Route::post('kiosks/{kioskId}/logs', KioskLogController::class);
        Route::get('profiles/{profile}/photo', ProfilePhotoController::class);
    });
