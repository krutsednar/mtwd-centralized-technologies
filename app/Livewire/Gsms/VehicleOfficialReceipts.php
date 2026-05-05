<?php

namespace App\Livewire\Gsms;

use App\Models\Vehicle;
use App\Models\VehicleOfficialReceipt;
use Livewire\Component;

class VehicleOfficialReceipts extends Component
{
    public Vehicle $record;

    public $receipts;

    public function mount($record)
    {
       $this->receipts = VehicleOfficialReceipt::where('vehicle_id', $record->id)->get();
    }

    public function render()
    {
        return view('livewire.gsms.vehicle-official-receipts');
    }
}
