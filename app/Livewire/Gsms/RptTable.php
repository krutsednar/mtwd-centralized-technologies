<?php

namespace App\Livewire\Gsms;

use App\Models\LandStructure;
use App\Models\RealPropertyTax;
use Livewire\Component;

class RptTable extends Component
{
    public LandStructure $record;

    public $taxes;

    public function mount($record)
    {
       $this->taxes = RealPropertyTax::where('land_structure_id', $record->id)->get();
    }

    public function render()
    {
        return view('livewire.gsms.rpt-table');
    }
}
