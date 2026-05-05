<?php

namespace App\Livewire\Gsms;

use Livewire\Component;
use App\Models\LandStructure;
use App\Models\TaxDeclaration;

class TaxDecTable extends Component
{
    public LandStructure $record;

    public $taxDecs;

    public function mount($record)
    {
       $this->taxDecs = TaxDeclaration::where('land_structure_id', $record->id)->get();
    }

    public function render()
    {
        return view('livewire.gsms.tax-dec-table');
    }
}
