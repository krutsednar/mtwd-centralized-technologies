<?php

namespace App\Livewire\Home;

use App\Models\Profile;
use Livewire\Component;

class ProfileViewer extends Component
{
    public string $activeTab = 'primary';

    public function render(): \Illuminate\View\View
    {
        $profile = Profile::with([
            'division',
            'children',
            'educationalBackgrounds',
            'eligibilities',
            'work_experiences',
            'trainings',
            'serviceRecords',
            'organizations',
            'skills',
        ])->where('employee_number', auth()->user()->employee_number)->first();

        return view('livewire.home.profile-viewer', compact('profile'));
    }
}
