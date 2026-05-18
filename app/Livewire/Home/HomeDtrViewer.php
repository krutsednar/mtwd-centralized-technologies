<?php

namespace App\Livewire\Home;

use App\Models\Attendance;
use App\Models\Profile;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Livewire\Component;

class HomeDtrViewer extends Component
{
    public int $month;
    public int $year;

    public function mount(): void
    {
        $this->month = now()->month;
        $this->year  = now()->year;
    }

    public function render(): \Illuminate\View\View
    {
        $user    = auth()->user();
        $profile = Profile::where('employee_number', $user->employee_number)->first();

        $start = Carbon::createFromDate($this->year, $this->month, 1)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $attendances = collect();
        if ($profile) {
            $attendances = Attendance::where('employee_number', $profile->employee_number)
                ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
                ->get()
                ->keyBy(fn ($a) => Carbon::parse($a->attendance_date)->toDateString());
        }

        $days = collect(CarbonPeriod::create($start, $end)->toArray());

        $months = collect(range(1, 12))->mapWithKeys(
            fn ($m) => [$m => Carbon::createFromDate(2000, $m, 1)->format('F')]
        );

        $years = collect(range(now()->year - 5, now()->year + 1))->mapWithKeys(
            fn ($y) => [$y => $y]
        );

        return view('livewire.home.dtr-viewer', compact('profile', 'days', 'attendances', 'months', 'years'));
    }
}
