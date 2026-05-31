<?php

namespace App\Http\Controllers\Hris\Dtr;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Profile;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Renders the printable Daily Time Record (DTR) for a single profile.
 *
 * Accepts either an explicit (start_date, end_date) date range OR a
 * (month, year) pair; defaults to the current month if neither is supplied.
 *
 * Extracted from the route closure formerly at routes/web.php (audit R-1).
 * Route name `dtr.print` preserved — referenced from AttendanceResource.
 */
class PrintProfileDtrController extends Controller
{
    public function __invoke(Request $request, Profile $profile): View
    {
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = Carbon::parse($request->input('start_date'));
            $end = Carbon::parse($request->input('end_date'));
        } else {
            $month = (int) $request->input('month', now()->month);
            $year = (int) $request->input('year', now()->year);
            $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $end = $start->copy()->endOfMonth();
        }

        $attendances = Attendance::where('employee_number', $profile->employee_number)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($a) => Carbon::parse($a->attendance_date)->toDateString());

        return view('dtr-print', compact('profile', 'start', 'end', 'attendances'));
    }
}
