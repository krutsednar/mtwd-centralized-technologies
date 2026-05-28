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
 * Bulk-prints DTRs for Job-Order profiles in a division for a chosen
 * semi-monthly cutoff (1st: prev-month-26 → 10th; 2nd: 11th → 25th).
 *
 * Month, year, and cutoff supplied via query string; cutoff defaults to
 * 'first'. Profiles are filtered to status='Job Order' and sorted by
 * surname; attendances are grouped by employee number and keyed by date
 * string for fast lookup in the print view.
 *
 * Extracted from the route closure formerly at routes/web.php (audit R-1).
 * Route name `dtr.print.jo` preserved — referenced from AttendanceResource.
 */
class PrintJobOrderDtrController extends Controller
{
    public function __invoke(Request $request, Division $division): View
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $cutoff = $request->input('cutoff', 'first');

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
    }
}
