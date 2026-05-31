<?php

namespace App\Http\Controllers\Hris\Dtr;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Division;
use App\Models\Profile;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Bulk-prints DTRs for every non–Job-Order profile in a division.
 *
 * Month and year supplied via query string; defaults to the current month.
 * Profiles are sorted by surname; attendances are grouped by employee
 * number and keyed by date string for fast lookup in the print view.
 *
 * Extracted from the route closure formerly at routes/web.php (audit R-1).
 * Route name `dtr.print.division` preserved — referenced from
 * AttendanceResource.
 */
class PrintDivisionDtrController extends Controller
{
    public function __invoke(Request $request, Division $division): View
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

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
    }
}
