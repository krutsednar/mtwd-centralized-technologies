<?php

namespace App\Console\Commands;

use App\Models\LeaveApplication;
use App\Models\LeaveCard;
use App\Models\LeaveInclusiveDates;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateLeaveCardsFromApprovedApplications extends Command
{
    protected $signature   = 'app:populate-leave-cards';
    protected $description = 'Populate leave cards from approved leave applications for past inclusive dates.';

    // Leave types that deduct from Vacation Leave
    private const VL_TYPES = [
        'vacation', 'special_privilege', 'solo_parent', 'adoption', 'emergency_calamity',
    ];

    // Leave types that deduct from Sick Leave
    private const SL_TYPES = [
        'sick', 'special_women',
    ];

    // Leave types that deduct from VL via mandatory deduction
    private const MANDATORY_TYPES = [
        'mandatory_forced',
    ];

    public function handle(): int
    {
        $yesterday = Carbon::yesterday()->toDateString();

        $pendingDates = LeaveInclusiveDates::query()
            ->with('leaveApplication')
            ->where('status', 'pending')
            ->whereDate('date', '<=', $yesterday)
            ->whereHas('leaveApplication', fn ($q) => $q->whereIn('approval_status', ['with_pay', 'without_pay']))
            ->get();

        if ($pendingDates->isEmpty()) {
            $this->info('No pending inclusive dates to process.');

            return self::SUCCESS;
        }

        $processed = 0;

        foreach ($pendingDates as $inclusiveDate) {
            $application = $inclusiveDate->leaveApplication;

            DB::transaction(function () use ($inclusiveDate, $application): void {
                $leaveType     = $application->leave_type;
                $approvalStatus = $application->approval_status;
                $duration      = (float) $inclusiveDate->duration;
                $isWithPay     = $approvalStatus === 'with_pay';

                $vlEarned     = 0;
                $vlWithPay    = 0;
                $vlWithoutPay = 0;
                $slEarned     = 0;
                $slWithPay    = 0;
                $slWithoutPay = 0;

                if ($isWithPay) {
                    if (in_array($leaveType, self::VL_TYPES) || in_array($leaveType, self::MANDATORY_TYPES)) {
                        $vlWithPay = $duration;
                    } elseif (in_array($leaveType, self::SL_TYPES)) {
                        $slWithPay = $duration;
                    }
                } else {
                    // without_pay — track usage but no credit deduction
                    if (in_array($leaveType, self::VL_TYPES) || in_array($leaveType, self::MANDATORY_TYPES)) {
                        $vlWithoutPay = $duration;
                    } elseif (in_array($leaveType, self::SL_TYPES)) {
                        $slWithoutPay = $duration;
                    }
                }

                LeaveCard::create([
                    'profile_id'     => $application->profile_id,
                    'date_applied'   => $inclusiveDate->date,
                    'ref_code'       => $application->leave_application_no,
                    'category'       => $leaveType,
                    'period_covered' => Carbon::parse($inclusiveDate->date)->format('d-H-i'),
                    'duration'       => $duration,
                    'vl_earned'      => $vlEarned,
                    'vl_with_pay'    => $vlWithPay,
                    'vl_without_pay' => $vlWithoutPay,
                    'sl_earned'      => $slEarned,
                    'sl_with_pay'    => $slWithPay,
                    'sl_without_pay' => $slWithoutPay,
                    'remarks'        => 'Auto-populated from approved leave application.',
                ]);

                $inclusiveDate->update(['status' => 'used']);
            });

            $processed++;
        }

        $this->info("Processed {$processed} inclusive date(s) into leave cards.");

        return self::SUCCESS;
    }
}
