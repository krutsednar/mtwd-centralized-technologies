<?php

namespace App\Livewire\Gsms;

use Livewire\Component;
use App\Models\LandStructure;
use App\Models\StructureInsurance;

class StructureInsuranceTable extends Component
{
    public LandStructure $record;

    public $insurances;

    public function mount($record)
    {
       $this->insurances = StructureInsurance::where('land_structure_id', $record->id)->get();
    }

    public function render()
    {
        return view('livewire.gsms.structure-insurance-table');
    }
}
