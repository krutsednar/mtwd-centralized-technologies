<?php

namespace App\Livewire\Gsms;

use App\Models\Vehicle;
use App\Models\VehicleInsurancePolicy;
use Livewire\Component;

class VehicleInsurance extends Component
{
    public Vehicle $record;

    public $policies;

    public function mount($record)
    {
        $this->policies = VehicleInsurancePolicy::where('vehicle_id', $record->id)->get();
    }

    public function render()
    {
        return view('livewire.gsms.vehicle-insurance');
    }
}
