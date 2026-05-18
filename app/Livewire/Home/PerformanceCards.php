<?php

namespace App\Livewire\Home;

use App\Models\IndividualPerformance;
use App\Models\Profile;
use Livewire\Component;

class PerformanceCards extends Component
{
    public function render(): \Illuminate\View\View
    {
        $profile = Profile::where('employee_number', auth()->user()->employee_number)->first();

        $performances = collect();
        if ($profile) {
            $performances = IndividualPerformance::where('profile_id', $profile->id)
                ->orderByDesc('ipc_year')
                ->get();
        }

        return view('livewire.home.performance-cards', compact('profile', 'performances'));
    }
}
