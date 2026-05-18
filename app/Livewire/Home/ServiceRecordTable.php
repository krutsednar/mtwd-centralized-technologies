<?php

namespace App\Livewire\Home;

use App\Models\Profile;
use App\Models\ServiceRecord;
use Livewire\Component;

class ServiceRecordTable extends Component
{
    public function render(): \Illuminate\View\View
    {
        $profile = Profile::where('employee_number', auth()->user()->employee_number)->first();

        $serviceRecords = collect();
        $totalYears     = '—';

        if ($profile) {
            $serviceRecords = ServiceRecord::where('profile_id', $profile->id)
                ->orderBy('from')
                ->get();

            $totalDays = $serviceRecords->sum(function ($sr) {
                if (! $sr->from || ! $sr->to) {
                    return 0;
                }
                return $sr->from->diffInDays($sr->to);
            });

            $years      = intdiv($totalDays, 365);
            $months     = intdiv($totalDays % 365, 30);
            $totalYears = "{$years}yrs {$months}mos";
        }

        return view('livewire.home.service-record-table', compact('profile', 'serviceRecords', 'totalYears'));
    }
}
