<?php

use App\Models\Attendance;
use App\Models\LeaveApplication;
use App\Models\Profile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('/attendance-mode', 'attendancemode');

Route::get('/biometrics/{phase}', function ($phase) {
    return view('biometrics', ['phase' => $phase]);
})->name('biometrics');

Route::get('/dtr/print/{profile}', function (Profile $profile) {
    if (request()->filled('start_date') && request()->filled('end_date')) {
        $start = Carbon::parse(request('start_date'));
        $end = Carbon::parse(request('end_date'));
    } else {
        $month = (int) request('month', now()->month);
        $year = (int) request('year', now()->year);
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
    }

    $attendances = Attendance::where('employee_number', $profile->employee_number)
        ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
        ->get()
        ->keyBy(fn ($a) => Carbon::parse($a->attendance_date)->toDateString());

    return view('dtr-print', compact('profile', 'start', 'end', 'attendances'));
})->name('dtr.print');

Route::get('/dtr/print/division/{division}', function (\App\Models\Division $division) {
    $month = (int) request('month', now()->month);
    $year = (int) request('year', now()->year);

    $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
    $end = $start->copy()->endOfMonth();

    $profiles = Profile::where('division_id', $division->id)
        ->where('status', '!=', 'Job Order')
        ->orderBy('surname')
        ->get();

    $allAttendances = Attendance::whereIn('employee_number', $profiles->pluck('employee_number'))
        ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
        ->get()
        ->groupBy('employee_number')
        ->map(fn ($records) => $records->keyBy(fn ($a) => Carbon::parse($a->attendance_date)->toDateString()));

    return view('dtr-bulk-print', compact('division', 'profiles', 'month', 'year', 'allAttendances'));
})->name('dtr.print.division');

Route::get('/dtr/print/jo/{division}', function (\App\Models\Division $division) {
    $month = (int) request('month', now()->month);
    $year = (int) request('year', now()->year);
    $cutoff = request('cutoff', 'first');

    if ($cutoff === 'first') {
        $start = Carbon::createFromDate($year, $month, 1)->subMonthNoOverflow()->setDay(26);
        $end = Carbon::createFromDate($year, $month, 10);
    } else {
        $start = Carbon::createFromDate($year, $month, 11);
        $end = Carbon::createFromDate($year, $month, 25);
    }

    $profiles = Profile::where('division_id', $division->id)
        ->where('status', 'Job Order')
        ->orderBy('surname')
        ->get();

    $allAttendances = Attendance::whereIn('employee_number', $profiles->pluck('employee_number'))
        ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
        ->get()
        ->groupBy('employee_number')
        ->map(fn ($records) => $records->keyBy(fn ($a) => Carbon::parse($a->attendance_date)->toDateString()));

    return view('dtr-jo-bulk-print', compact('division', 'profiles', 'month', 'year', 'cutoff', 'start', 'end', 'allAttendances'));
})->name('dtr.print.jo');

Route::get('/leave/print/{leaveApplication}', function (LeaveApplication $leaveApplication) {
    $leaveApplication->load('profile.division');

    return view('leave-print', compact('leaveApplication'));
})->name('leave.print');

// ── Face Biometrics v2 (parallel system — do not modify legacy routes above) ──
Route::prefix('face-biometrics')->name('face-biometrics.')->group(function () {
    Route::get('/', \App\Livewire\FaceBiometrics\AttendanceKiosk::class)->name('index');
    Route::middleware(['auth'])->group(function () {
        Route::get('/enroll', \App\Livewire\FaceBiometrics\EnrollmentKiosk::class)->name('enroll');
        Route::get('/health', [\App\Http\Controllers\FaceBiometrics\HealthController::class, '__invoke'])->name('health');
        Route::get('/audit', \App\Livewire\FaceBiometrics\AuditLog::class)->name('audit');
    });
});
