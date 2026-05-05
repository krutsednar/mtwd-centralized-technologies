<?php

namespace App\Livewire\Hris;

use Livewire\Component;

class AttendanceMode extends Component
{

    public function selectPhase($phase)
    {
        $this->dispatch('phaseSelected', phase: $phase)->to(AttendanceManager::class);
    }

    public function render()
    {
        return view('livewire.hris.attendance-mode');
    }
}
