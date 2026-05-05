<?php

namespace App\Livewire\Gsms;

use App\Models\HeavyEquipment;
use App\Models\HeavyEquipmentInsurancePolicy;
use Livewire\Component;

class HeavyEquipmentInsurances extends Component
{
    public HeavyEquipment $record;

    public $policies;

    public function mount($record)
    {
       $this->policies = HeavyEquipmentInsurancePolicy::where('heavy_equipment_id', $record->id)->get();
    }

    public function render()
    {
        return view('livewire.gsms.heavy-equipment-insurances');
    }
}
