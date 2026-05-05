<?php

namespace App\Livewire\Hris;

use Livewire\Component;

class AttendanceManager extends Component
{

    public function selectPhase($phase)
    {
        // This routes the user to /biometrics/morning_in, etc.
        return redirect()->to('/biometrics/' . $phase);
    }

    public function render()
    {
        return view('livewire.hris.attendance-manager');
    }
}
