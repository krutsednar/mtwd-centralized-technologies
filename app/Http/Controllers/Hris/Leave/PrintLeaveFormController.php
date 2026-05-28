<?php

namespace App\Http\Controllers\Hris\Leave;

use App\Http\Controllers\Controller;
use App\Models\LeaveApplication;
use Illuminate\Contracts\View\View;

/**
 * Renders the printable Leave Application form for a single record.
 *
 * Extracted from the route closure formerly at routes/web.php (audit R-1).
 * Route name `leave.print` preserved — referenced from Filament resources.
 */
class PrintLeaveFormController extends Controller
{
    public function __invoke(LeaveApplication $leaveApplication): View
    {
        $leaveApplication->load('profile.division');

        return view('leave-print', compact('leaveApplication'));
    }
}
