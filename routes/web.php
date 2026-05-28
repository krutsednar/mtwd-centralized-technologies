<?php

use App\Http\Controllers\Hris\Dtr\PrintDivisionDtrController;
use App\Http\Controllers\Hris\Dtr\PrintJobOrderDtrController;
use App\Http\Controllers\Hris\Dtr\PrintProfileDtrController;
use App\Http\Controllers\Hris\Leave\PrintLeaveFormController;
use App\Http\Controllers\Hris\ServiceRecord\PrintServiceRecordController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

/**
 * Shared /edit-profile redirect — kept as a defensive fallback.
 *
 * Panel user menus now link directly to their own /<panel>/edit-profile
 * via route() helpers (see *PanelProvider::userMenuItems). This route
 * exists only to handle direct visits / bookmarks to /edit-profile,
 * forwarding to the correct panel-specific page based on the referrer.
 */
Route::get('/edit-profile', function () {
    $previous = url()->previous('');

    if (str_contains($previous, '/gsms')) {
        return redirect('/gsms/edit-profile');
    }

    if (str_contains($previous, '/hris')) {
        return redirect('/hris/edit-profile');
    }

    if (str_contains($previous, '/home')) {
        return redirect('/home/edit-profile');
    }

    return redirect('/admin/edit-profile');
})->middleware('auth');

// ── HRIS Print Endpoints (controllers under App\Http\Controllers\Hris) ──
// Route names are referenced from Filament resources — DO NOT rename.
Route::get('/dtr/print/{profile}', PrintProfileDtrController::class)
    ->name('dtr.print');

Route::get('/dtr/print/division/{division}', PrintDivisionDtrController::class)
    ->name('dtr.print.division');

Route::get('/dtr/print/jo/{division}', PrintJobOrderDtrController::class)
    ->name('dtr.print.jo');

Route::get('/service-record/print/{profile}', PrintServiceRecordController::class)
    ->name('service-record.print');

Route::get('/leave/print/{leaveApplication}', PrintLeaveFormController::class)
    ->name('leave.print');

// ── Face Biometrics v2 (parallel system — do not modify legacy routes above) ──
Route::prefix('face-biometrics')->name('face-biometrics.')->group(function () {
    Route::get('/', \App\Livewire\FaceBiometrics\AttendanceKiosk::class)->name('index');
    Route::middleware(['auth'])->group(function () {
        Route::get('/enroll', \App\Livewire\FaceBiometrics\EnrollmentKiosk::class)->name('enroll');
        Route::get('/health', [\App\Http\Controllers\FaceBiometrics\HealthController::class, '__invoke'])->name('health');
        Route::get('/audit', \App\Livewire\FaceBiometrics\AuditLog::class)->name('audit');
    });
});
